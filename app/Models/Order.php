<?php

namespace App\Models;

use App\Models\Concerns\HandlesApiData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory, HandlesApiData;

    protected $fillable = [
        'g_number',
        'date',
        'last_change_date',
        'supplier_article',
        'tech_size',
        'barcode',
        'total_price',
        'discount_percent',
        'warehouse_name',
        'oblast',
        'income_id',
        'odid',
        'nm_id',
        'subject',
        'category',
        'brand',
        'is_cancel',
        'cancel_dt',
    ];

    protected $casts = [
        'date' => 'datetime',
        'last_change_date' => 'date',
        'barcode' => 'integer',
        'total_price' => 'decimal:2',
        'discount_percent' => 'integer',
        'income_id' => 'integer',
        'nm_id' => 'integer',
        'is_cancel' => 'boolean',
        'cancel_dt' => 'date',
    ];

    protected static function mapApiData(array $data): array
    {
        return [
            'g_number' => $data['g_number'] ?? null,
            'date' => $data['date'] ?? null,
            'last_change_date' => $data['last_change_date'] ?? null,
            'supplier_article' => $data['supplier_article'] ?? null,
            'tech_size' => $data['tech_size'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'total_price' => $data['total_price'] ?? null,
            'discount_percent' => $data['discount_percent'] ?? null,
            'warehouse_name' => $data['warehouse_name'] ?? null,
            'oblast' => $data['oblast'] ?? null,
            'income_id' => $data['income_id'] ?? null,
            'odid' => $data['odid'] ?? null,
            'nm_id' => $data['nm_id'] ?? null,
            'subject' => $data['subject'] ?? null,
            'category' => $data['category'] ?? null,
            'brand' => $data['brand'] ?? null,
            'is_cancel' => $data['is_cancel'] ?? false,
            'cancel_dt' => $data['cancel_dt'] ?? null,
        ];
    }
}
