<?php

namespace App\Models;

use App\Models\Concerns\HandlesApiData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory, HandlesApiData;

    protected $fillable = [
        'g_number',
        'date',
        'last_change_date',
        'supplier_article',
        'tech_size',
        'barcode',
        'quantity',
        'total_price',
        'discount_percent',
        'is_supply',
        'is_realization',
        'promo_code_discount',
        'warehouse_name',
        'country_name',
        'oblast_okrug_name',
        'region_name',
        'income_id',
        'sale_id',
        'odid',
        'spp',
        'for_pay',
        'finished_price',
        'price_with_disc',
        'nm_id',
        'subject',
        'category',
        'brand',
        'is_storno',
    ];

    protected $casts = [
        'date' => 'date',
        'last_change_date' => 'date',
        'is_supply' => 'boolean',
        'is_realization' => 'boolean',
        'is_storno' => 'boolean',
        'total_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'promo_code_discount' => 'decimal:2',
        'spp' => 'decimal:2',
        'for_pay' => 'decimal:2',
        'finished_price' => 'decimal:2',
        'price_with_disc' => 'decimal:2',
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
            'quantity' => $data['quantity'] ?? 0,
            'total_price' => $data['total_price'] ?? null,
            'discount_percent' => $data['discount_percent'] ?? null,
            'is_supply' => $data['is_supply'] ?? null,
            'is_realization' => $data['is_realization'] ?? null,
            'promo_code_discount' => $data['promo_code_discount'] ?? null,
            'warehouse_name' => $data['warehouse_name'] ?? null,
            'country_name' => $data['country_name'] ?? null,
            'oblast_okrug_name' => $data['oblast_okrug_name'] ?? null,
            'region_name' => $data['region_name'] ?? null,
            'income_id' => $data['income_id'] ?? null,
            'sale_id' => $data['sale_id'] ?? null,
            'odid' => $data['odid'] ?? null,
            'spp' => $data['spp'] ?? null,
            'for_pay' => $data['for_pay'] ?? null,
            'finished_price' => $data['finished_price'] ?? null,
            'price_with_disc' => $data['price_with_disc'] ?? null,
            'nm_id' => $data['nm_id'] ?? null,
            'subject' => $data['subject'] ?? null,
            'category' => $data['category'] ?? null,
            'brand' => $data['brand'] ?? null,
            'is_storno' => $data['is_storno'] ?? null,
        ];
    }
}
