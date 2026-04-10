<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class PlantImage extends Model
{
    use HasFactory;
    // Gắn trait HasFactory để dùng factory cho seeding/testing

    protected $fillable = [
        'plant_id',
        'image_path',
        'feature_vector',

        'user_id',
        'tank_id',
        'purpose',
        'query_vector',
        'match_results',
        'note',
    ];
    // $fillable: các field cho phép mass assignment (PlantImage::create([...]))
    // Nhìn danh sách này cho thấy PlantImage đang "gánh 2 vai":
    // (1) Ảnh thư viện cây: plant_id + image_path + feature_vector
    // (2) Lịch sử identify/search: user_id, tank_id, purpose, query_vector, match_results, note

    protected $casts = [
        'feature_vector' => 'array',
        'query_vector'   => 'array',
        'match_results'  => 'array',
    ];
    // $casts: tự động chuyển đổi kiểu khi đọc/ghi DB
    // - feature_vector: lưu trong DB dạng JSON/text nhưng khi lấy ra sẽ thành mảng PHP
    // - query_vector: tương tự, vector ảnh người dùng upload
    // - match_results: danh sách kết quả match (top-K) dạng mảng (thường gồm plant_id/score/paths,...)

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }
    // Relationship: PlantImage thuộc về 1 Plant (master)
    // Mặc định dùng plant_images.plant_id -> plants.id

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    // Relationship: PlantImage có thể thuộc về 1 User (ảnh identify/history của user)
    // Mặc định plant_images.user_id -> users.id

    public function tank()
    {
        return $this->belongsTo(Tank::class);
    }
    // Relationship: PlantImage có thể gắn với 1 Tank (ngữ cảnh identify/add-to-tank)
    // Mặc định plant_images.tank_id -> tanks.id
}