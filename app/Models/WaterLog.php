<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class WaterLog extends Model
// Khai báo model WaterLog: đại diện cho 1 bản ghi log nước (theo thời gian) của một tank
{
    use HasFactory, SoftDeletes;
    // Gắn trait:
    // - HasFactory: dùng factory cho seed/test
    // - SoftDeletes: delete() sẽ set deleted_at, có thể restore() để khôi phục

    protected $fillable = [
        'tank_id',
        'logged_at',
        'ph',
        'temperature',
        'no3',
        'other_params',
    ];
    // $fillable: các field cho phép mass assignment (WaterLog::create([...]) / update([...]))
    // - tank_id: log này thuộc bể nào
    // - logged_at: thời điểm đo (rất quan trọng cho phân tích 7 ngày, trend)
    // - ph: độ pH
    // - temperature: nhiệt độ nước
    // - no3: nồng độ nitrate (NO3)
    // - other_params: các chỉ số thêm (vd: gh, kh, tds, nh3...) gói trong 1 cột JSON/array

    protected $casts = [
        'logged_at'    => 'datetime',
        'other_params' => 'array',
        'deleted_at'   => 'datetime',
    ];
    // $casts: tự động ép kiểu khi đọc/ghi từ DB
    // - logged_at: ép thành datetime (Carbon) để so sánh, format, lọc theo thời gian dễ
    // - other_params: ép thành array (thường lưu JSON trong DB, lấy ra thành mảng PHP)
    // - deleted_at: ép thành datetime để xử lý soft delete chuẩn

    public function tank()
    {
        return $this->belongsTo(Tank::class);
    }
    // Relationship: WaterLog thuộc về 1 Tank
    // Mặc định water_logs.tank_id -> tanks.id
    // Dùng: $waterLog->tank (biết log này của bể nào) / $tank->waterLogs (lấy list log của bể)
}