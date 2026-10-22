<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Chapter;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('Demo users can only be seeded in the local environment.');
        }

        $chapter = Chapter::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if ($chapter === null) {
            $chapter = Chapter::factory()->create([
                'name' => 'BNP Demo Chapter',
                'state' => 'Karnataka',
            ]);
        }

        $accounts = [
            ['Demo Super Admin', 'admin.demo@bnp-cafe.test', UserRole::SuperAdmin],
            ['Demo Sub Admin', 'subadmin.demo@bnp-cafe.test', UserRole::SubAdmin],
            ['Demo Member', 'member.demo@bnp-cafe.test', UserRole::Member],
            ['Demo Guest', 'guest.demo@bnp-cafe.test', UserRole::Guest],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            $user = User::query()->firstOrNew(['email' => $email]);
            $user->forceFill([
                'name' => $name,
                'password' => 'BnpCafeDemo2026!',
                'email_verified_at' => now(),
                'password_set_at' => now(),
                'role' => $role,
            ])->save();

            $user->administeredChapters()->sync(
                $role === UserRole::SubAdmin ? [$chapter->id] : [],
            );
        }

        $this->command?->info('Local demo accounts are ready. Sign in with the credentials documented in frontend/README.md.');
    }
}
