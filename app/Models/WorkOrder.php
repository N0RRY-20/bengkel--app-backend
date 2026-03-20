<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_pemilik',
        'plat_nomor',
        'mechanic_id',
        'created_by',
        'status',
    ];

    public function mechanic()
    {
        return $this->belongsTo(Mechanic::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function services()
    {
        return $this->hasMany(WorkOrderService::class);
    }

    public function parts()
    {
        return $this->hasMany(WorkOrderPart::class);
    }

    public function payment()
    {
        return $this->morphOne(Payment::class, 'source');
    }
}
