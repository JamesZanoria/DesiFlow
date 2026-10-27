<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        $hasOrderNumber = Schema::hasColumn('orders', 'order_number');
        $hasCode = Schema::hasColumn('orders', 'code');
        $hasNotes = Schema::hasColumn('orders', 'notes');

        Schema::table('orders', function (Blueprint $table) use ($hasOrderNumber, $hasCode, $hasNotes) {
            if (! $hasOrderNumber) {
                $table->string('order_number', 40)->nullable()->unique('orders_order_number_unique');
            }

            if (! $hasCode) {
                $table->string('code', 10)->nullable()->unique('orders_code_unique');
            }

            if (! $hasNotes) {
                $table->string('notes', 150)->nullable();
            }

            $table->integer('cashier_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        $hasOrderNumber = Schema::hasColumn('orders', 'order_number');
        $hasCode = Schema::hasColumn('orders', 'code');
        $hasNotes = Schema::hasColumn('orders', 'notes');

        Schema::table('orders', function (Blueprint $table) use ($hasOrderNumber, $hasCode, $hasNotes) {
            if ($hasOrderNumber) {
                $table->dropUnique('orders_order_number_unique');
                $table->dropColumn('order_number');
            }

            if ($hasCode) {
                $table->dropUnique('orders_code_unique');
                $table->dropColumn('code');
            }

            if ($hasNotes) {
                $table->dropColumn('notes');
            }
        });
    }
};
