<?php

namespace App\Models;

use App\Models\Concerns\HandlesApiData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    use HasFactory, HandlesApiData;

    protected $fillable = [
        'income_id',
        'number',
        'date',
        'last_change_date',
        'supplier_article',
        'tech_size',
        'barcode',
        'quantity',
        'total_price',
        'date_close',
        'warehouse_name',
        'nm_id',
    ];

    protected $casts = [
        'date' => 'date',
        'last_change_date' => 'date',
        'date_close' => 'date',
        'total_price' => 'decimal:2',
    ];

    protected static function mapApiData(array $data): array
    {
        return [
            'income_id' => $data['income_id'] ?? null,
            'number' => $data['number'] ?? null,
            'date' => $data['date'] ?? null,
            'last_change_date' => $data['last_change_date'] ?? null,
            'supplier_article' => $data['supplier_article'] ?? null,
            'tech_size' => $data['tech_size'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'quantity' => $data['quantity'] ?? 0,
            'total_price' => $data['total_price'] ?? null,
            'date_close' => $data['date_close'] ?? null,
            'warehouse_name' => $data['warehouse_name'] ?? null,
            'nm_id' => $data['nm_id'] ?? null,
        ];
    }
}
