<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class PlantLog extends Model
// Khai báo model PlantLog: đại diện cho 1 bản ghi log tăng trưởng/tình trạng của cây trong bể
{
    use HasFactory, SoftDeletes;
    // Gắn trait:
    // - HasFactory: dùng factory cho seed/test
    // - SoftDeletes: delete() sẽ set deleted_at, có thể restore() để khôi phục

    protected $fillable = [
        'tank_plant_id',
        'logged_at',
        'height',
        'status',
        'note',
        'image_path',
    ];
    // $fillable: các field cho phép mass assignment (PlantLog::create([...]) / update([...]))
    // - tank_plant_id: log này thuộc "cây trong bể" nào (instance), không phải plant master
    // - logged_at: ngày ghi log (ngày đo/quan sát)
    // - height: chiều cao cây (vd: cm) hoặc chỉ số tăng trưởng
    // - status: trạng thái (vd: healthy/yellowing/melting...) tùy bạn định nghĩa
    // - note: ghi chú thêm
    // - image_path: đường dẫn ảnh minh họa log (ảnh tùy chọn)

    protected $casts = [
        'logged_at'  => 'date',
        'deleted_at' => 'datetime',
    ];
    // $casts: tự động ép kiểu khi đọc/ghi
    // - logged_at: ép thành date (chỉ ngày, không cần giờ) để hiển thị/tổng hợp theo ngày dễ
    // - deleted_at: ép thành datetime để xử lý soft delete chuẩn

    public function tankPlant()
    {
        return $this->belongsTo(TankPlant::class);
    }
    // Relationship: PlantLog thuộc về 1 TankPlant
    // Mặc định plant_logs.tank_plant_id -> tank_plants.id
    // Dùng: $plantLog->tankPlant (biết log này của cây nào trong bể nào)
}