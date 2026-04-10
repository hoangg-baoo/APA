<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterLogReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'tank_id',
        'enabled',
        'frequency',
        'preferred_time',
        'start_date',
        'next_due_at',
        'last_sent_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'start_date' => 'date',
        'next_due_at' => 'datetime',
        'last_sent_at' => 'datetime',
    ];

    public function tank()
    {
        return $this->belongsTo(Tank::class);
    }
}