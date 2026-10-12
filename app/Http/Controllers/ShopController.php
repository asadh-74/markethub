<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'min' => ['nullable', 'numeric', 'min:0'],
            'max' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'in:newest,price_asc,price_desc'],
        ]);

        $query = Product::listed()
            ->with(['store:id,name', 'category:id,name'])
            ->filter($filters);

        match ($filters['sort'] ?? 'newest') {
            'price_asc' => $query->orderBy('price_cents')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price_cents')->orderBy('id'),
            default => $query->orderByDesc('id'),
        };

        $products = $query->paginate(12)->withQueryString()
            ->through(fn (Product $p) => $p->toCard());

        return Inertia::render('Shop/Index', [
            'products' => $products,
            'filters' => (object) $filters,
            'categories' => Cache::remember(
                'shop.categories', 300,
                fn () => Category::orderBy('name')->get(['id', 'name', 'slug'])->toArray()
            ),
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless(Product::listed()->whereKey($product->id)->exists(), 404);

        $product->load(['store:id,name', 'category:id,name']);

        $related = Product::listed()
            ->with(['store:id,name', 'category:id,name'])
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->limit(4)->get()
            ->map(fn (Product $p) => $p->toCard());

        return Inertia::render('Shop/Show', [
            'product' => $product->toCard(),
            'related' => $related,
        ]);
    }
}
