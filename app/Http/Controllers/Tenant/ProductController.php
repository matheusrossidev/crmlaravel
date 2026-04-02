<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductMedia;
use App\Services\PlanLimitChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::with('media', 'categoryRelation.parent')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $categories = ProductCategory::withoutGlobalScopes()
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('tenant.settings.products', compact('products', 'categories'));
    }

    public function store(Request $request): JsonResponse
    {
        if (auth()->user()->role !== 'admin' && ! auth()->user()->is_super_admin) {
            return response()->json(['success' => false, 'message' => 'Sem permissão.'], 403);
        }

        $data = $request->validate([
            'name'       => 'required|string|max:191',
            'description' => 'nullable|string|max:5000',
            'sku'        => 'nullable|string|max:50',
            'price'      => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'category'    => 'nullable|string|max:100',
            'category_id' => 'nullable|integer|exists:product_categories,id',
            'unit'        => 'nullable|string|max:20',
        ]);

        $data['sort_order'] = (int) Product::max('sort_order') + 1;
        $data['is_active']  = true;

        $product = Product::create($data);
        $product->load('media');

        return response()->json(['success' => true, 'product' => $product], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        if (auth()->user()->role !== 'admin' && ! auth()->user()->is_super_admin) {
            return response()->json(['success' => false, 'message' => 'Sem permissão.'], 403);
        }

        $data = $request->validate([
            'name'       => 'sometimes|required|string|max:191',
            'description' => 'nullable|string|max:5000',
            'sku'        => 'nullable|string|max:50',
            'price'      => 'sometimes|required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'category'    => 'nullable|string|max:100',
            'category_id' => 'nullable|integer|exists:product_categories,id',
            'unit'        => 'nullable|string|max:20',
            'is_active'   => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $product->update($data);
        $product->load('media');

        return response()->json(['success' => true, 'product' => $product]);
    }

    public function destroy(Product $product): JsonResponse
    {
        if (auth()->user()->role !== 'admin' && ! auth()->user()->is_super_admin) {
            return response()->json(['success' => false, 'message' => 'Sem permissão.'], 403);
        }

        // Delete media files from disk
        foreach ($product->media as $media) {
            Storage::disk('public')->delete($media->storage_path);
        }

        $product->delete();

        return response()->json(['success' => true]);
    }

    public function uploadMedia(Request $request, Product $product): JsonResponse
    {
        if (auth()->user()->role !== 'admin' && ! auth()->user()->is_super_admin) {
            return response()->json(['success' => false, 'message' => 'Sem permissão.'], 403);
        }

        $request->validate([
            'file'        => 'required|file|max:10240|mimes:jpg,jpeg,png,webp,gif,mp4,mov,pdf',
            'description' => 'nullable|string|max:191',
        ]);

        $file = $request->file('file');
        $path = $file->store("products/{$product->id}", 'public');

        $media = ProductMedia::create([
            'product_id'    => $product->id,
            'tenant_id'     => $product->tenant_id,
            'original_name' => preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file->getClientOriginalName())),
            'storage_path'  => $path,
            'mime_type'     => $file->getMimeType(),
            'file_size'     => $file->getSize(),
            'description'   => $request->input('description', ''),
            'sort_order'    => (int) $product->media()->max('sort_order') + 1,
        ]);

        return response()->json(['success' => true, 'media' => $media], 201);
    }

    public function deleteMedia(Product $product, ProductMedia $media): JsonResponse
    {
        if (auth()->user()->role !== 'admin' && ! auth()->user()->is_super_admin) {
            return response()->json(['success' => false, 'message' => 'Sem permissão.'], 403);
        }

        if ((int) $media->product_id !== (int) $product->id) {
            return response()->json(['success' => false, 'message' => 'Mídia não pertence a este produto.'], 403);
        }

        Storage::disk('public')->delete($media->storage_path);
        $media->delete();

        return response()->json(['success' => true]);
    }

    // ── Categories CRUD ─────────────────────────────────────────────────

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'parent_id' => 'nullable|integer|exists:product_categories,id',
        ]);

        $cat = ProductCategory::create($data);

        return response()->json(['success' => true, 'category' => $cat], 201);
    }

    public function updateCategory(Request $request, ProductCategory $category): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'sometimes|required|string|max:100',
            'parent_id' => 'nullable|integer|exists:product_categories,id',
        ]);

        $category->update($data);

        return response()->json(['success' => true, 'category' => $category->fresh()]);
    }

    public function destroyCategory(ProductCategory $category): JsonResponse
    {
        // Move children to parent (or root)
        ProductCategory::where('parent_id', $category->id)->update(['parent_id' => $category->parent_id]);
        // Unlink products
        Product::where('category_id', $category->id)->update(['category_id' => null]);

        $category->delete();

        return response()->json(['success' => true]);
    }
}
