<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'tank_id',
        'sensor_id',
        'type',
        'numeric_value',
        'recorded_at',
        'raw_payload',
    ];

    protected $casts = [
        'numeric_value' => 'float',
        'recorded_at'   => 'datetime',
        'raw_payload'   => 'array',
    ];

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class);
    }
}