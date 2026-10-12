<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'min' => ['nullable', 'numeric', 'min:0'],
            'max' => ['nullable', 'numeric', 'min:0'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        // Public catalogue: identical queries are cached for 60 seconds.
        $payload = Cache::remember('api.products.'.md5(json_encode($filters)), 60, function () use ($filters) {
            $products = Product::listed()
                ->with(['store:id,name', 'category:id,name'])
                ->filter($filters)
                ->orderByDesc('id')
                ->paginate($filters['per_page'] ?? 12);

            return ProductResource::collection($products)->response()->getData(true);
        });

        return response()->json($payload);
    }

    public function show(Product $product): ProductResource
    {
        abort_unless(Product::listed()->whereKey($product->id)->exists(), 404);

        return new ProductResource($product->load(['store:id,name', 'category:id,name']));
    }
}
