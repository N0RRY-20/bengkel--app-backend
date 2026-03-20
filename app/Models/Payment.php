<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_type',
        'source_id',
        'total',
        'metode',
        'kasir_id',
    ];

    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }

    public function source()
    {
        if ($this->source_type === 'work_order') {
            return $this->belongsTo(WorkOrder::class, 'source_id');
        }
        return $this->belongsTo(Sale::class, 'source_id');
    }
}
