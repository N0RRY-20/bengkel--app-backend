<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceMaster extends Model
{
    use HasFactory;

    protected $table = 'services_master';

    protected $fillable = [
        'nama_jasa',
        'harga',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
