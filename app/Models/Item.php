<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Item extends Model
{
    use HasFactory;

    public function transaction(): BelongsToMany
    {
        return $this->belongsToMany(Transaction::class, 'transaction_item')->withPivot('qty', 'price');
    }
}
