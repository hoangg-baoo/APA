<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdentifyRegion extends Model
{
    use HasFactory;

    protected $fillable = [
        'identify_session_id',
        'crop_image_path',
        'crop_box',
        'query_vector',
        'match_results',
        'proposal_source',
        'proposal_score',
    ];

    protected $casts = [
        'crop_box' => 'array',
        'query_vector' => 'array',
        'match_results' => 'array',
        'proposal_score' => 'float',
    ];

    public function session()
    {
        return $this->belongsTo(IdentifySession::class, 'identify_session_id');
    }
}