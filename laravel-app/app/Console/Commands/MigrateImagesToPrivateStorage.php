<?php

namespace App\Console\Commands;

use App\Models\Ingredient;
use App\Models\MenuItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * One-time migration for images uploaded before the switch from the public
 * disk to the private 'local' disk (see HandlesProtectedImageUploads).
 * Existing image_url values still point at the old /storage/{type}/{file}
 * public-disk URL format; this moves each underlying file into private
 * storage and rewrites the column to the new route-based URL, so images
 * uploaded before this change keep working instead of silently 404ing the
 * moment this ships.
 
 * Safe to re-run: any record whose image_url already points at the new
 * /files/ route is left untouched.
 
 * Run once with: php artisan images:migrate-to-private
 */
class MigrateImagesToPrivateStorage extends Command{
    protected $signature = 'images:migrate-to-private';
    protected $description = 'Move existing menu item / ingredient images from the public disk to private storage';

    public function handle(): int{
        $this->migrate(MenuItem::class, 'menu_item_id', 'menu-items');
        $this->migrate(Ingredient::class, 'ingredient_id', 'ingredients');

        return self::SUCCESS;
    }

    private function migrate(string $modelClass, string $keyColumn, string $type): void{
        $records = $modelClass::whereNotNull('image_url')
            ->where('image_url', 'not like', '%/files/%')
            ->get();

        if ($records->isEmpty()) {
            $this->info("{$type}: nothing to migrate.");

            return;
        }

        $this->info("{$type}: migrating {$records->count()} image(s)...");

        foreach ($records as $record) {
            $oldRelativePath = ltrim(parse_url($record->image_url, PHP_URL_PATH) ?? '', '/');
            $oldRelativePath = preg_replace('#^storage/#', '', $oldRelativePath);

            if (! Storage::disk('public')->exists($oldRelativePath)) {
                $this->warn("  Skipped {$record->{$keyColumn}}: file not found on public disk ({$oldRelativePath}).");

                continue;
            }

            $filename = basename($oldRelativePath);
            $newRelativePath = "{$type}/{$filename}";

            Storage::disk('local')->put(
                $newRelativePath,
                Storage::disk('public')->get($oldRelativePath)
            );

            $record->update([
                'image_url' => route('files.show', ['type' => $type, 'filename' => $filename]),
            ]);

            $this->line("  Migrated #{$record->{$keyColumn}}.");
        }
    }
}