<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    /**
     * Fields that can be mass assigned
     */
    protected $fillable = [
        'title',
        'content',
        'state',
        'review_note',
        'risk_score',
        'reviewed_at'
    ];

    /**
     * Cast database fields to specific types
     */
    protected $casts = [
        'reviewed_at' => 'datetime',
        'risk_score' => 'integer',
    ];
}