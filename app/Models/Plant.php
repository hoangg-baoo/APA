<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    use HasFactory;
    // Gắn trait HasFactory để dùng factory cho seeding/testing

    protected $fillable = [
        'name',
        'description',
        'ph_min',
        'ph_max',
        'temp_min',
        'temp_max',
        'light_level',
        'difficulty',
        'image_sample',
        'care_guide',
    ];
    // $fillable: các field cho phép mass assignment (Plant::create([...]))
    // Chỉ các cột này được phép nhận dữ liệu từ request/array
    // Giúp tránh ghi nhầm/ghi bừa các cột không mong muốn

    public function tankPlants()
    {
        return $this->hasMany(TankPlant::class);
    }
    // Relationship: 1 plant (master) có thể xuất hiện trong nhiều tank_plants (instance trong các bể)
    // Mặc định tank_plants.plant_id trỏ tới plants.id

    public function images()
    {
        return $this->hasMany(PlantImage::class);
    }
    // Relationship: 1 plant có nhiều plant_images (ảnh thư viện / ảnh phục vụ identify)
    // Mặc định plant_images.plant_id trỏ tới plants.id

    public function primaryImage()
    {
        return $this->hasOne(PlantImage::class)
            ->oldestOfMany()
            ->select([
                'plant_images.id',
                'plant_images.plant_id',
                'plant_images.image_path',
            ]);
    }
    // Relationship "primaryImage": lấy 1 ảnh đại diện cho plant
    // - hasOne(): quan hệ 1-1 về mặt lấy dữ liệu (chỉ lấy 1 record)
    // - oldestOfMany(): chọn record "cũ nhất" trong nhóm ảnh (thường là ảnh đầu tiên upload/seed)
    // - select([...]): chỉ lấy 3 cột cần thiết để nhẹ query (không lấy feature_vector hoặc cột nặng khác)
}