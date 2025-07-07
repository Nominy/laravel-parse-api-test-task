<?php

namespace App\Models;

use App\Models\Concerns\HandlesApiData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory, HandlesApiData;

    protected $fillable = [
        'date',
        'last_change_date',
        'supplier_article',
        'tech_size',
        'barcode',
        'quantity',
        'is_supply',
        'is_realization',
        'quantity_full',
        'warehouse_name',
        'in_way_to_client',
        'in_way_from_client',
        'nm_id',
        'subject',
        'category',
        'brand',
        'sc_code',
        'price',
        'discount',
    ];

    protected $casts = [
        'date' => 'date',
        'last_change_date' => 'date',
        'is_supply' => 'boolean',
        'is_realization' => 'boolean',
        'price' => 'decimal:2',
    ];

    protected static function mapApiData(array $data): array
    {
        return [
            'date' => $data['date'] ?? null,
            'last_change_date' => $data['last_change_date'] ?? null,
            'supplier_article' => $data['supplier_article'] ?? null,
            'tech_size' => $data['tech_size'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'quantity' => $data['quantity'] ?? 0,
            'is_supply' => $data['is_supply'] ?? null,
            'is_realization' => $data['is_realization'] ?? null,
            'quantity_full' => $data['quantity_full'] ?? null,
            'warehouse_name' => $data['warehouse_name'] ?? null,
            'in_way_to_client' => $data['in_way_to_client'] ?? null,
            'in_way_from_client' => $data['in_way_from_client'] ?? null,
            'nm_id' => $data['nm_id'] ?? null,
            'subject' => $data['subject'] ?? null,
            'category' => $data['category'] ?? null,
            'brand' => $data['brand'] ?? null,
            'sc_code' => $data['sc_code'] ?? null,
            'price' => $data['price'] ?? null,
            'discount' => $data['discount'] ?? null,
        ];
    }
}
