<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        DB::enableQueryLog();
        $startTime = microtime(true);

        $categoryId = $request->input('category_id', 'all');
        $sort = $request->input('sort', 'default');
        $page = $request->input('page', 1);

        $cacheKey = "products:catalog:cat_{$categoryId}:sort_{$sort}:page_{$page}";

        $cachedData = Cache::remember($cacheKey, 600, function () use ($request) {
            $query = Product::query()
                ->where('status', 'published')
                ->with([
                    'category:id,name',
                    'brand:id,name',
                    'variants:id,product_id,sku,price,stock'
                ]);

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->input('category_id'));
            }

            if ($request->filled('sort')) {
                match ($request->input('sort')) {
                    'price_asc' => $query->orderBy('base_price', 'asc'),
                    'price_desc' => $query->orderBy('base_price', 'desc'),
                    default => $query->latest(),
                };
            } else {
                $query->latest();
            }

            $products = $query->paginate(15);

            return [
                'total_items' => $products->total(),
                'data' => collect($products->items())->map(fn($product) => [
                    'id' => $product->id,
                    'title' => $product->title,
                    'base_price' => $product->base_price,
                    'category' => $product->category?->name,
                    'brand' => $product->brand?->name,
                    'variants' => $product->variants->map(fn($v) => [
                        'sku' => $v->sku,
                        'price' => $v->price,
                        'stock' => $v->stock,
                    ]),
                ])->all(),
            ];
        });

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        $memoryUsageMB = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        $queries = DB::getQueryLog();

        return response()->json([
            'caching_diagnostics' => [
                'is_cache_hit' => count($queries) === 0,
                'total_queries' => count($queries),
                'execution_time_ms' => $executionTime,
                'peak_memory_mb' => $memoryUsageMB,
            ],
            'total_items' => $cachedData['total_items'],
            'data' => $cachedData['data'],
        ]);
    }


    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = DB::transaction(function () use ($request) {
            $productData = $request->safe()->only([
                'category_id',
                'brand_id',
                'title',
                'description',
                'base_price',
                'status',
            ]);

            $productData['slug'] = Str::slug($productData['title']);

            $product = Product::create($productData);

            $product->variants()->createMany($request->validated('variants'));

            if ($request->filled('images')) {
                $product->images()->createMany($request->validated('images'));
            }

            return $product;
        });

        $product->load(['variants', 'images', 'category', 'brand']);

        return response()->json([
            'message' => 'Product and variants created successfully',
            'data' => $product,
        ], Response::HTTP_CREATED);
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->where('status', 'published')
            ->with(['category', 'brand', 'variants', 'images'])
            ->firstOrFail();

        return response()->json([
            'data' => $product,
        ]);
    }
}