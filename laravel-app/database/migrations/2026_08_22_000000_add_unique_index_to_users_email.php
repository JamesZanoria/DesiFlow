<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Same pattern as the menu_items and ingredients migrations: users has no
     * uniqueness guarantee on email today, so this merges any existing
     * duplicates first (keeping the lowest user_id per email, repointing
     * orders.cashier_id and stock_movements.user_id onto it, then removing the
     * duplicate row) before creating a case-insensitive, whitespace-trimmed
     * unique index. Without this merge step, a unique index cannot be created
     * on top of data that already violates it.
     *
     * Caveat worth a manual check afterwards: if two duplicate accounts (e.g.
     * "Owner@DesiZaika.com" and "owner@desizaika.com ") each processed real
     * orders or recorded real stock movements under their own user_id, merging
     * onto one account combines that history under a single name in the
     * Transaction History and Stock Movement Ledger going forward. Review
     * Staff Management for any account this migration reports as merged.
     */
    public function up(): void
    {
        DB::transaction(function () {
            $this->mergeDuplicateUsers();

            DB::statement(
                'CREATE UNIQUE INDEX users_email_unique ON users (LOWER(TRIM(email)))'
            );
        });
    }

    public function down(): void
    {
        // The duplicate-merge step is not reversible (the removed duplicate
        // rows are gone), so down() only removes the index. Restoring merged
        // data, if ever needed, would have to come from a database backup.
        DB::statement('DROP INDEX IF EXISTS users_email_unique');
    }

    private function mergeDuplicateUsers(): void
    {
        $duplicateGroups = DB::table('users')
            ->selectRaw('LOWER(TRIM(email)) as normalized_email')
            ->groupBy('normalized_email')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('normalized_email');

        foreach ($duplicateGroups as $normalizedEmail) {
            $rows = DB::table('users')
                ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
                ->orderBy('user_id')
                ->get(['user_id', 'email']);

            $keeperId = $rows->first()->user_id;
            $duplicates = $rows->slice(1);

            foreach ($duplicates as $duplicate) {
                DB::table('orders')
                    ->where('cashier_id', $duplicate->user_id)
                    ->update(['cashier_id' => $keeperId]);

                DB::table('stock_movements')
                    ->where('user_id', $duplicate->user_id)
                    ->update(['user_id' => $keeperId]);

                DB::table('users')
                    ->where('user_id', $duplicate->user_id)
                    ->delete();
            }

            info("Merged duplicate user \"{$normalizedEmail}\": kept user_id {$keeperId}, removed " . $duplicates->count() . ' duplicate row(s).');
        }
    }
};