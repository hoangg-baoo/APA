<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Tank;

use App\Models\Plant;

use App\Models\PlantLog;

class TankPlant extends Model
// Khai báo model TankPlant: đại diện cho "một cây (plant) được gắn vào một bể (tank)"
{
    use HasFactory, SoftDeletes;
    // Gắn trait:
    // - HasFactory: dùng factory cho seed/test
    // - SoftDeletes: delete() sẽ set deleted_at, có thể restore() lại

    protected $fillable = [
        'tank_id',
        'plant_id',
        'planted_at',
        'position',
        'note',
    ];
    // $fillable: các field cho phép mass assignment (TankPlant::create([...]) / update([...]))
    // - tank_id: bể nào
    // - plant_id: cây nào (plant master)
    // - planted_at: ngày trồng cây vào bể
    // - position: vị trí trong bể (vd: foreground/midground/background, left/right...)
    // - note: ghi chú thêm

    protected $casts = [
        'planted_at' => 'date',
        'deleted_at' => 'datetime',
    ];
    // $casts: tự động ép kiểu khi đọc/ghi
    // - planted_at: ép thành kiểu date (Carbon date) để format và so sánh ngày dễ
    // - deleted_at: ép thành datetime để xử lý soft delete chuẩn

    public function tank()
    {
        return $this->belongsTo(Tank::class);
    }
    // Relationship: TankPlant thuộc về 1 Tank
    // Mặc định tank_plants.tank_id -> tanks.id
    // Dùng: $tankPlant->tank (biết cây này đang nằm trong bể nào)

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }
    // Relationship: TankPlant thuộc về 1 Plant (cây master)
    // Mặc định tank_plants.plant_id -> plants.id
    // Dùng: $tankPlant->plant (biết instance này là cây gì)

    public function plantLogs()
    {
        return $this->hasMany(PlantLog::class);
    }
    // Relationship: 1 TankPlant có nhiều PlantLog (log phát triển của cây trong bể)
    // Mặc định plant_logs.tank_plant_id -> tank_plants.id
    // Dùng: $tankPlant->plantLogs (lấy lịch sử tăng trưởng của cây này)
}