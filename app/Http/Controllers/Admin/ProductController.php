<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $search = mb_substr($search, 0, 100);

        $products = Product::query()
            ->with(['store:id,name,status', 'category:id,name'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'store' => $product->store?->name,
                'store_status' => $product->store?->status?->value,
                'category' => $product->category?->name,
                'stock' => $product->stock,
                'price_cents' => $product->price_cents,
                'is_active' => $product->is_active,
            ]);

        return Inertia::render('Admin/Products', [
            'products' => $products,
            'filters' => ['q' => $search],
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'stock' => ['sometimes', 'required', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ]);

        if (count($data) === 0) {
            return back()->withErrors(['product' => 'Choose at least one property to update.']);
        }

        $product->update($data);

        return back()->with('success', 'Product updated.');
    }
}
