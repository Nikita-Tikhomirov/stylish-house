<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'items',
        'total_price',
        'status',
        'comment',
        'delivery_method',
        'delivery_cost',
        'customer_details',
    ];

    protected $casts = [
        'items' => 'array',
        'customer_details' => 'array',
        'total_price' => 'decimal:2',
        'delivery_cost' => 'decimal:2',
    ];

    public const STATUSES = [
        1 => 'Заказ обрабатывается',
        2 => 'Заказ в производстве',
        3 => 'Заказ на складе',
        4 => 'Заказ у клиента',
    ];

    public const DELIVERY_METHODS = [
        'pickup' => 'Самовывоз',
        'courier_mkad' => 'Доставка в пределах МКАД',
        'courier_outside' => 'Доставка за пределы МКАД',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? 'Статус уточняется';
    }

    public function getDeliveryLabelAttribute(): string
    {
        return self::DELIVERY_METHODS[$this->delivery_method] ?? 'Не выбрано';
    }
}
