<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\AttachPlantToTankRequest;

use App\Models\Tank;

use App\Models\TankPlant;

class TankPlantController extends BaseApiController
// Controller API quản lý TankPlant: attach plant, remove, restore (soft delete)
{
    public function store(AttachPlantToTankRequest $request, Tank $tank)
    {
        // store(): POST /api/tanks/{tank}/plants -> gắn 1 plant vào tank

        $this->authorize('update', $tank);
        // authorize update trên tank: chỉ owner/admin mới được thêm cây vào bể
        // gọi TankPolicy@update (owner check)

        $data = $request->validated();
        // Lấy dữ liệu đã validate từ AttachPlantToTankRequest (thường có plant_id, optional planted_at/position/note)

        $tankPlant = TankPlant::withTrashed()
            // withTrashed(): query cả record đã soft delete (deleted_at != null)
            // mục tiêu: nếu trước đó đã gắn cây rồi nhưng bị "remove" (soft delete) thì có thể restore lại

            ->where('tank_id', $tank->id)
            // Chỉ tìm trong đúng tank hiện tại

            ->where('plant_id', $data['plant_id'])
            // Chỉ tìm đúng plant_id mà user muốn attach

            ->first();
            // Lấy record đầu tiên (nếu tồn tại)

        if ($tankPlant) {
            // Nếu đã từng tồn tại (kể cả bị soft delete)

            if ($tankPlant->trashed()) {
                // trashed(): true nếu record đang bị soft delete

                $tankPlant->restore();
                // restore(): khôi phục record (set deleted_at = null)
            }

            $update = [];
            // $update: mảng field cần update (chỉ update những field client gửi lên)

            if (array_key_exists('planted_at', $data)) $update['planted_at'] = $data['planted_at'];
            // Nếu client có gửi planted_at thì update planted_at

            if (array_key_exists('position', $data))   $update['position'] = $data['position'];
            // Nếu client có gửi position thì update position

            if (array_key_exists('note', $data))       $update['note'] = $data['note'];
            // Nếu client có gửi note thì update note

            if ($update) $tankPlant->update($update);
            // Nếu có field nào cần update thì update vào DB
        } else {
            // Nếu chưa từng tồn tại -> tạo mới TankPlant

            $tankPlant = TankPlant::create([
                // create(): tạo record tank_plants mới (cần TankPlant::$fillable có các field này)

                'tank_id'    => $tank->id,
                // tank_id lấy từ tank route (không cho client tự set)

                'plant_id'   => $data['plant_id'],
                // plant_id lấy từ request

                'planted_at' => $data['planted_at'] ?? null,
                // planted_at optional: nếu không có thì null

                'position'   => $data['position'] ?? null,
                // position optional

                'note'       => $data['note'] ?? null,
                // note optional
            ]);
        }

        $tankPlant->load('plant');
        // load('plant'): eager load plant để trả kèm thông tin plant trong response
        // (UI cần name/difficulty/light_level...)

        return $this->success($tankPlant, 'Plant attached to tank.');
        // Trả JSON: tankPlant + plant + message
    }

    public function destroy(TankPlant $tankPlant)
    {
        // destroy(): DELETE /api/tank-plants/{tankPlant} -> remove cây khỏi tank (soft delete)

        $tankPlant->load('tank');
        // load('tank'): load tank để kiểm tra ownership (cần tank để authorize)

        $this->authorize('update', $tankPlant->tank);
        // authorize update tank: chỉ owner/admin mới được remove cây khỏi bể
        // (không authorize trực tiếp TankPlant, mà authorize trên Tank để kiểm owner)

        $tankPlant->delete();
        // delete(): vì TankPlant dùng SoftDeletes -> sẽ set deleted_at (trash), không xóa hẳn

        return $this->success(null, 'Plant removed from tank.');
        // Trả success, data null, message
    }

    //  RESTORE
    public function restore(\Illuminate\Http\Request $request, string $tankPlant)
    {
        // restore(): PATCH /api/tank-plants/{tankPlant}/restore -> khôi phục tankPlant đã bị soft delete
        // Nhận $tankPlant là string id (không dùng route model binding vì cần withTrashed)

        $tp = TankPlant::withTrashed()->with('tank')->findOrFail($tankPlant);
        // withTrashed(): tìm cả record đã soft delete
        // with('tank'): load tank luôn để authorize
        // findOrFail(): không tìm thấy thì 404

        if (!$tp->tank) abort(404, 'Tank not found.');
        // Nếu vì lý do gì tank không tồn tại (tank bị xóa) -> trả 404

        $this->authorize('update', $tp->tank);
        // Chỉ owner/admin của tank mới được restore tankPlant

        if ($tp->trashed()) {
            // Nếu đang bị soft delete

            $tp->restore();
            // restore(): set deleted_at = null
        }

        $tp->load('plant');
        // load('plant'): load plant để trả info cây kèm theo

        return $this->success($tp, 'Tank plant restored.');
        // Trả JSON tankPlant đã restore + plant + message
    }
}