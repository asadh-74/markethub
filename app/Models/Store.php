<?php

namespace App\Models;

use App\Enums\StoreStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'status'];

    protected function casts(): array
    {
        return ['status' => StoreStatus::class];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', StoreStatus::Approved->value);
    }

    public function isApproved(): bool
    {
        return $this->status === StoreStatus::Approved;
    }
}
