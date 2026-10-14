<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AuthProvider;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\AdminUser;
use App\Services\AuthService;
use Illuminate\Database\Seeder;

/**
 * Local development accounts. Every seeded password is "password".
 */
class AdminUserSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public function run(AuthService $auth): void
    {
        $approved = [
            ['Sistem Yöneticisi', 'super@ankara.edu.tr', Role::SuperAdmin],
            ['Bölüm Yöneticisi', 'yonetici@ankara.edu.tr', Role::Admin],
            ['Program Editörü', 'editor@ankara.edu.tr', Role::Editor],
            ['Ahmet Yılmaz', 'ahmet.yilmaz@ankara.edu.tr', Role::Instructor],
            ['Selin Aydın', 'selin.aydin@ankara.edu.tr', Role::Teacher],
        ];

        foreach ($approved as [$name, $email, $role]) {
            $user = new AdminUser([
                'name' => $name,
                'email' => $email,
                'password' => self::PASSWORD,
                'auth_provider' => AuthProvider::Local,
            ]);
            $user->forceFill([
                'role' => $role,
                'status' => UserStatus::Approved,
                'email_verified_at' => now(),
            ])->save();
        }

        $superAdmin = AdminUser::query()->where('email', 'super@ankara.edu.tr')->firstOrFail();

        $auth->register('Aday Öğretmen', 'aday.ogretmen@example.com', self::PASSWORD, Role::Teacher, 'Yarı zamanlı ders vermek istiyorum.');
        $auth->register('Misafir Kullanıcı', 'misafir@example.com', self::PASSWORD);

        $rejected = $auth->register('Reddedilen Kullanıcı', 'reddedilen@example.com', self::PASSWORD, Role::Editor);
        $auth->reject($rejected, $superAdmin, 'Kurum dışı başvuru.');
    }
}
