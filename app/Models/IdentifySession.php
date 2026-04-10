<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdentifySession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tank_id',
        'source_image_path',
        'note',
        'merged_results',
        'confirmed_plants',
    ];

    protected $casts = [
        'merged_results' => 'array',
        'confirmed_plants' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tank()
    {
        return $this->belongsTo(Tank::class);
    }

    public function regions()
    {
        return $this->hasMany(IdentifyRegion::class);
    }
}