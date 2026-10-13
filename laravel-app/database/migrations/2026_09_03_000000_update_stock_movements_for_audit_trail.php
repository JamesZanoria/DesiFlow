<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // waste_reason was already being used as a general remarks field for
        // every movement type (see the old "Initial stock recorded on
        // ingredient creation" / "Marked as waste, past shelf life" text),
        // not just spoilage. Renaming it to notes makes that honest.
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->renameColumn('waste_reason', 'notes');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            // Optional DR#/invoice number for a supplier delivery, set once at
            // creation and never edited afterward (see
            // InventoryController::updateMovement()).
            $table->string('reference_number', 100)->nullable()->after('notes');

            // Required justification captured when a movement's quantity or
            // date is corrected after the fact — kept separate from notes so
            // a correction never overwrites the entry's original remarks.
            $table->string('edit_reason', 150)->nullable()->after('reference_number');

            // Set on the ORIGINAL entry the moment it's voided, so it can't be
            // voided or edited a second time (see
            // InventoryController::destroyMovement()/updateMovement()).
            $table->timestampTz('voided_at')->nullable()->after('edit_reason');

            // Set on the reversing entry itself, pointing back at the
            // movement it voids — lets the ledger link "Voided Entry #123"
            // back to the original row without parsing the notes text.
            $table->foreignId('voids_movement_id')->nullable()->after('voided_at')
                ->constrained('stock_movements', 'movement_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voids_movement_id');
            $table->dropColumn(['reference_number', 'edit_reason', 'voided_at']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->renameColumn('notes', 'waste_reason');
        });
    }
};