<?php
// Mở thẻ PHP, bắt đầu file PHP

use Illuminate\Database\Migrations\Migration; // Import class Migration: khung chuẩn để tạo/rollback DB schema
use Illuminate\Database\Schema\Blueprint;     // Import Blueprint: object dùng để khai báo cột/index trong bảng
use Illuminate\Support\Facades\Schema;       // Import Schema facade: gọi Schema::create/drop/... để thao tác DB

return new class extends Migration            // Tạo 1 anonymous class (không đặt tên) kế thừa Migration và trả về cho Laravel
{
    public function up(): void               // Hàm chạy khi bạn php artisan migrate (áp dụng thay đổi DB)
    {
        Schema::create('users', function (Blueprint $table) { // Tạo bảng tên "users", $table dùng để khai báo các cột
            $table->id();                   // Tạo cột id: BIGINT UNSIGNED, AUTO_INCREMENT, PRIMARY KEY
            $table->string('name');         // Tạo cột name: VARCHAR(255), NOT NULL
            $table->string('email')->unique(); // Tạo cột email: VARCHAR(255), NOT NULL + UNIQUE (không trùng email)

            // Thêm dòng này để fix lỗi seed   // Ghi chú: seed/factory Laravel hay có field email_verified_at
            $table->timestamp('email_verified_at')->nullable(); // Tạo cột email_verified_at: TIMESTAMP, cho phép NULL (chưa verify)

            $table->string('password');     // Tạo cột password: VARCHAR(255), NOT NULL (lưu hash mật khẩu)

            // Thêm đúng như file SQL         // Ghi chú: bạn muốn role giống SQL design ban đầu
            $table->enum('role', ['user', 'expert', 'admin']) // Tạo cột role: ENUM chỉ nhận 3 giá trị user/expert/admin
                  ->default('user');        // Đặt giá trị mặc định = 'user' nếu khi tạo user không truyền role
            $table->string('avatar')->nullable(); // Tạo cột avatar: VARCHAR(255), NULL (đường dẫn/URL ảnh đại diện)
            $table->text('bio')->nullable();      // Tạo cột bio: TEXT, NULL (mô tả/tiểu sử dài)

            // Thêm dòng này để hỗ trợ "remember me" // Ghi chú: phục vụ tính năng "remember me" khi login web
            $table->rememberToken();        // Tạo cột remember_token: VARCHAR(100), NULL (token lưu phiên đăng nhập lâu)

            $table->timestamps();           // Tạo 2 cột created_at và updated_at: TIMESTAMP, Laravel tự quản lý
        });
    }

    public function down(): void             // Hàm chạy khi rollback (php artisan migrate:rollback)
    {
        Schema::dropIfExists('users');      // Xóa bảng users nếu tồn tại (để rollback an toàn)
    }
};