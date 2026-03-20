<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Correction extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_type',
        'source_id',
        'sebelum',
        'sesudah',
        'alasan',
        'owner_id',
    ];

    protected $casts = [
        'sebelum' => 'array',
        'sesudah' => 'array',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
