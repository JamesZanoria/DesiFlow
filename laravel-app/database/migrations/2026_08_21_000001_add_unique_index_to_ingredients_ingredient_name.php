<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Same pattern as the menu_items migration: ingredients has no uniqueness
     * guarantee on ingredient_name today, so this merges any existing
     * duplicates first (keeping the lowest ingredient_id per name, repointing
     * recipe_ingredients and stock_movements onto it, then removing the
     * duplicate row) before creating a case-insensitive, whitespace-trimmed
     * unique index.
     *
     * Caveat worth a manual check afterwards: merging combines each duplicate
     * ingredient's stock_movements history onto one row, which changes that
     * ingredient's computed current_stock (the sum of its ledger). Review
     * Inventory → Stock Movement Ledger for any ingredient this migration
     * merges, to confirm the combined stock total still makes sense.
     */
    public function up(): void
    {
        DB::transaction(function () {
            $this->mergeDuplicateIngredients();

            DB::statement(
                'CREATE UNIQUE INDEX ingredients_ingredient_name_unique ON ingredients (LOWER(TRIM(ingredient_name)))'
            );
        });
    }

    public function down(): void
    {
        // The duplicate-merge step is not reversible (the removed duplicate
        // rows are gone), so down() only removes the index.
        DB::statement('DROP INDEX IF EXISTS ingredients_ingredient_name_unique');
    }

    private function mergeDuplicateIngredients(): void
    {
        $duplicateGroups = DB::table('ingredients')
            ->selectRaw('LOWER(TRIM(ingredient_name)) as normalized_name')
            ->groupBy('normalized_name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('normalized_name');

        foreach ($duplicateGroups as $normalizedName) {
            $rows = DB::table('ingredients')
                ->whereRaw('LOWER(TRIM(ingredient_name)) = ?', [$normalizedName])
                ->orderBy('ingredient_id')
                ->get(['ingredient_id']);

            $keeperId = $rows->first()->ingredient_id;
            $duplicates = $rows->slice(1);

            foreach ($duplicates as $duplicate) {
                DB::table('recipe_ingredients')
                    ->where('ingredient_id', $duplicate->ingredient_id)
                    ->update(['ingredient_id' => $keeperId]);

                DB::table('stock_movements')
                    ->where('ingredient_id', $duplicate->ingredient_id)
                    ->update(['ingredient_id' => $keeperId]);

                DB::table('ingredients')
                    ->where('ingredient_id', $duplicate->ingredient_id)
                    ->delete();
            }

            info("Merged duplicate ingredient \"{$normalizedName}\": kept ingredient_id {$keeperId}, removed " . $duplicates->count() . ' duplicate row(s).');
        }
    }
};