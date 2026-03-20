<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'harga_jual',
        'stok',
        'tipe_pembeli',
    ];

    public function workOrderParts()
    {
        return $this->hasMany(WorkOrderPart::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }
}
