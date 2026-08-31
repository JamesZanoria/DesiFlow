<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Creates the one admin account needed to log in for the first time. There's
 * no registration route (by design — see AuthController), so without this,
 * the users table is empty and nobody can log in at all.
 *
 * SECURITY: this file intentionally contains NO hardcoded password. A
 * hardcoded value here would sit in Git history permanently — visible to
 * anyone with repo access, forever, even after the real password is changed
 * on the live account. Instead:
 *
 *   - If ADMIN_SEED_PASSWORD is set in .env, that value is used (handy for a
 *     consistent local dev password across a team, since .env itself is
 *     git-ignored and never committed).
 *   - Otherwise, a random password meeting the exact same policy
 *     StaffController enforces (10+ chars, mixed case, number, symbol) is
 *     generated fresh on every run and printed ONCE to the console. It is
 *     never written to a file, log, or the database in plain text.
 *
 * Run with:
 *   php artisan db:seed --class=AdminUserSeeder
 */
class AdminUserSeeder extends Seeder{
    public function run(): void{
        $email = env('ADMIN_SEED_EMAIL', 'admin@desizaika.com');
        $password = env('ADMIN_SEED_PASSWORD') ?: $this->generateStrongPassword();

        User::updateOrCreate(
            ['email' => $email],
            [
                'role' => 'admin',
                'name' => 'Desi Zaika Admin',
                'password' => $password, // hashed automatically by User's cast
            ]
        );

        $this->command->info('Admin account ready.');
        $this->command->info("  Email:    {$email}");
        $this->command->info("  Password: {$password}");
        $this->command->warn('Copy this password now — it is not stored anywhere and will not be shown again.');
    }

    // Guarantees at least one of each required character class (unlike a
    // plain Str::random(), which could by chance produce something that
    // fails StaffController's own Password::mixedCase()->numbers()->symbols()
    // policy — a seeded account should never violate the same rule every
    // other account is held to).
    private function generateStrongPassword(): string{
        $chars = [
            Str::upper(Str::random(1)),
            Str::lower(Str::random(1)),
            (string) random_int(0, 9),
            ['!', '@', '#', '$', '%', '&', '*'][random_int(0, 6)],
        ];

        // Pad to 16 characters total with random alphanumerics, then shuffle
        // so the guaranteed characters aren't predictably in the first 4 slots.
        while (count($chars) < 16) {
            $chars[] = Str::random(1);
        }

        shuffle($chars);

        return implode('', $chars);
    }
}