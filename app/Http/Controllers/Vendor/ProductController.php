<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $store = $request->user()->store;
        abort_if($store === null, 403);

        $products = Product::where('store_id', $store->id)
            ->latest('id')->paginate(15)
            ->through(fn (Product $p) => [
                'id' => $p->id, 'name' => $p->name, 'slug' => $p->slug,
                'price_cents' => $p->price_cents, 'stock' => $p->stock, 'is_active' => $p->is_active,
            ]);

        return Inertia::render('Vendor/Products/Index', [
            'products' => $products,
            'store_status' => $store->status->value,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->requireApprovedStore($request);

        return Inertia::render('Vendor/Products/Form', [
            'product' => null,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $store = $this->requireApprovedStore($request);

        $product = new Product($this->validated($request));
        $product->store_id = $store->id;
        $product->slug = Str::slug($product->name).'-'.Str::lower(Str::random(5));
        $product->save();

        return redirect()->route('vendor.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product): Response
    {
        Gate::authorize('update', $product);

        return Inertia::render('Vendor/Products/Form', [
            'product' => [
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => $product->price_cents / 100,
                'stock' => $product->stock,
                'category_id' => $product->category_id,
                'is_active' => $product->is_active,
            ],
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);

        $product->update($this->validated($request));

        return redirect()->route('vendor.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);

        $product->delete();

        return redirect()->route('vendor.products.index')->with('success', 'Product deleted.');
    }

    private function requireApprovedStore(Request $request)
    {
        $store = $request->user()->store;

        abort_unless($store !== null && $store->isApproved(), 403, 'Your store is awaiting admin approval.');

        return $store;
    }

    /** Validates the form and converts the price from rupees to integer cents. */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:10000000'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'is_active' => ['boolean'],
        ]);

        $data['price_cents'] = (int) round($data['price'] * 100);
        unset($data['price']);

        return $data;
    }
}
