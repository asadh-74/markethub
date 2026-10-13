<?php

namespace App\Models;

use App\Enums\StoreStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    public const LOW_STOCK_THRESHOLD = 5;

    protected $fillable = ['name', 'description', 'price_cents', 'stock', 'category_id', 'is_active', 'image_url'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'price_cents' => 'integer', 'stock' => 'integer'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Active products that belong to an approved store (what shoppers may see). */
    public function scopeListed(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereHas('store', fn (Builder $s) => $s->where('status', StoreStatus::Approved->value));
    }

    /** @param array{q?: ?string, category?: ?string, min?: mixed, max?: mixed} $filters */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, fn (Builder $q, string $term) => $q->where(
                fn (Builder $w) => $w->where('name', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%")
            ))
            ->when($filters['category'] ?? null, fn (Builder $q, string $slug) => $q->whereHas(
                'category', fn (Builder $c) => $c->where('slug', $slug)
            ))
            ->when($filters['min'] ?? null, fn (Builder $q, $v) => $q->where('price_cents', '>=', (int) round($v * 100)))
            ->when($filters['max'] ?? null, fn (Builder $q, $v) => $q->where('price_cents', '<=', (int) round($v * 100)));
    }

    public function isLowStock(): bool
    {
        return $this->stock <= self::LOW_STOCK_THRESHOLD;
    }

    /** Shape sent to the Vue front end. Requires store + category to be loaded. */
    public function toCard(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'price_cents' => $this->price_cents,
            'stock' => $this->stock,
            'store' => $this->store?->name,
            'category' => $this->category?->name,
        ];
    }
}
