<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The first attempt at this migration failed with a unique-violation on
     * "mango lassi" — meaning duplicate menu items (same name, different
     * casing/spacing) already exist in the table right now, most likely
     * inserted earlier while duplicate prevention wasn't actually wired up
     * yet. A unique index can't be created on top of data that already
     * violates it, so this version merges existing duplicates first:
     *
     *   1. For each group of item_name duplicates (case-insensitive,
     *      whitespace-trimmed), keep the row with the lowest menu_item_id
     *      (i.e. the original, first-created one) as the "keeper".
     *   2. Repoint any recipe_ingredients / order_items rows that reference
     *      one of the other, duplicate rows onto the keeper's id instead —
     *      this has to happen before the duplicate rows can be deleted,
     *      since both tables have a foreign key on menu_item_id.
     *   3. Delete the now-unreferenced duplicate rows.
     *   4. Only then create the unique index, exactly as before.
     *
     * Everything runs inside one transaction: if anything fails partway,
     * the whole migration rolls back rather than leaving data half-merged.
     *
     * Caveat worth a manual check afterwards: if two duplicate "mango lassi"
     * rows each had their *own* recipe (different recipe_ingredients rows),
     * merging onto one menu_item_id means that item now has the combined
     * recipe lines from both — review Menu Management → Inventory/Recipe
     * for any item this migration reports as merged, and remove any
     * now-redundant recipe lines by hand if needed.
     */
    public function up(): void
    {
        DB::transaction(function () {
            $this->mergeDuplicateMenuItems();

            DB::statement(
                'CREATE UNIQUE INDEX menu_items_item_name_unique ON menu_items (LOWER(TRIM(item_name)))'
            );
        });
    }

    public function down(): void
    {
        // The duplicate-merge step is not reversible (the removed duplicate
        // rows are gone), so down() only removes the index. Restoring merged
        // data, if ever needed, would have to come from a database backup.
        DB::statement('DROP INDEX IF EXISTS menu_items_item_name_unique');
    }

    private function mergeDuplicateMenuItems(): void
    {
        $duplicateGroups = DB::table('menu_items')
            ->selectRaw('LOWER(TRIM(item_name)) as normalized_name')
            ->groupBy('normalized_name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('normalized_name');

        foreach ($duplicateGroups as $normalizedName) {
            $rows = DB::table('menu_items')
                ->whereRaw('LOWER(TRIM(item_name)) = ?', [$normalizedName])
                ->orderBy('menu_item_id')
                ->get(['menu_item_id', 'item_name']);

            $keeperId = $rows->first()->menu_item_id;
            $duplicates = $rows->slice(1);

            foreach ($duplicates as $duplicate) {
                DB::table('recipe_ingredients')
                    ->where('menu_item_id', $duplicate->menu_item_id)
                    ->update(['menu_item_id' => $keeperId]);

                DB::table('order_items')
                    ->where('menu_item_id', $duplicate->menu_item_id)
                    ->update(['menu_item_id' => $keeperId]);

                DB::table('menu_items')
                    ->where('menu_item_id', $duplicate->menu_item_id)
                    ->delete();
            }

            info("Merged duplicate menu item \"{$normalizedName}\": kept menu_item_id {$keeperId}, removed " . $duplicates->count() . ' duplicate row(s).');
        }
    }
};