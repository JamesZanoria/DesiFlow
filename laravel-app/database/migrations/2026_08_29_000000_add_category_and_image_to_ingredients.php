<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Both columns are nullable: every ingredient that already exists gets
     * NULL for both rather than a fabricated default, so the UI can
     * distinguish "genuinely uncategorized / no photo yet" from a real
     * value, and the admin can fill them in through the Edit Ingredient
     * modal at their own pace instead of this migration guessing for them.
     */
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->string('category', 50)->nullable()->after('ingredient_name');
            $table->string('image_url')->nullable()->after('shelf_life_days');
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn(['category', 'image_url']);
        });
    }
};