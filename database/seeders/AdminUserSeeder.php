<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Tạo mới hoặc cập nhật nếu đã tồn tại email này
        User::updateOrCreate(
            ['email' => 'admin@aquatic.local'], // email cố định để login test
            [
                'name'     => 'System Admin',
                'password' => Hash::make('admin123'), // mật khẩu test
                'role'     => 'admin',
                'status'   => 'active',               // nhớ là bạn đã có cột status
                'avatar'   => null,
                'bio'      => 'Default system administrator',
            ]
        );
    }
}
