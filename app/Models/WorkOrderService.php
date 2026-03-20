<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderService extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'nama_jasa',
        'harga_jasa',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
