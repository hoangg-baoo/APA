<?php

namespace App\Policies;

use App\Models\Tank;

use App\Models\User;

class TankPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }
    // before(): hàm đặc biệt của Policy, chạy TRƯỚC mọi hàm khác (view/update/delete...)
    // - Nếu user là admin -> trả true => cho phép TẤT CẢ quyền (bỏ qua các check bên dưới)
    // - Nếu không phải admin -> trả null => Laravel sẽ tiếp tục chạy hàm view/update/delete tương ứng
    // Lý do dùng before: khỏi phải viết "isAdmin || owner" lặp lại ở mọi hàm

    public function view(User $user, Tank $tank): bool
    {
        return (int)$user->id === (int)$tank->user_id;
    }
    // view(): kiểm tra user có quyền xem tank hay không
    // - Chỉ owner mới xem được: user.id phải bằng tank.user_id
    // - Ép (int) để tránh trường hợp kiểu dữ liệu khác nhau (string vs int) gây sai so sánh

    public function update(User $user, Tank $tank): bool
    {
        return (int)$user->id === (int)$tank->user_id;
    }
    // update(): kiểm tra user có quyền sửa tank hay không
    // - Chỉ owner được sửa (admin đã được allow ở before)

    public function delete(User $user, Tank $tank): bool
    {
        return (int)$user->id === (int)$tank->user_id;
    }
    // delete(): kiểm tra user có quyền xóa tank hay không
    // - Chỉ owner được xóa (admin đã được allow ở before)
}