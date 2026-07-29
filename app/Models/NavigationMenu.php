<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationMenu extends Model
{
    protected $fillable = ['key', 'name'];

    public function items(): HasMany
    {
        return $this->hasMany(NavigationItem::class);
    }
}
