<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        return view('dashboard.products.index', [
            'page_title' => 'Product Catalog',
        ]);
    }

    public function datatable(Request $request)
    {
        $business = $request->user()->primaryBusiness()->firstOrFail();
        $query = Product::query()->where('business_id', $business->id);

        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $searchValue = $request->input('search.value');

        $recordsTotal = (clone $query)->count();

        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%$searchValue%")
                  ->orWhere('category', 'like', "%$searchValue%")
                  ->orWhere('unit', 'like', "%$searchValue%");
            });
        }

        $recordsFiltered = (clone $query)->count();

        // Ordering
        $columns = ['name', 'category', 'unit', 'selling_price', 'cost_price'];
        $order = $request->input('order', []);
        if (!empty($order)) {
            foreach ($order as $ord) {
                $colIdx = (int)($ord['column'] ?? 0);
                $dir = ($ord['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
                $column = $columns[$colIdx] ?? 'name';
                $query->orderBy($column, $dir);
            }
        } else {
            $query->orderBy('name');
        }

        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
            'selling_price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
        ]);
        $business = $request->user()->primaryBusiness()->firstOrFail();
        $data['business_id'] = $business->id;
        $product = Product::create($data);
        return response()->json(['success' => true, 'data' => $product]);
    }

    public function show(Product $product)
    {
        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
            'selling_price' => 'sometimes|required|numeric|min:0',
            'cost_price' => 'sometimes|required|numeric|min:0',
        ]);
        $product->update($data);
        return response()->json(['success' => true, 'data' => $product]);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['success' => true]);
    }

    // API Methods for Modal Management
    public function updateTitle(Request $request)
    {
        try {
            $request->validate([
                'card_id' => 'required|string',
                'title' => 'required|string|max:255'
            ]);

            $business = $request->user()->primaryBusiness()->firstOrFail();

            // Find product by card_id or create new one
            $product = Product::where('business_id', $business->id)
                             ->where('card_id', $request->card_id)
                             ->first();

            if ($product) {
                $product->update(['name' => $request->title]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteByCardId(Request $request)
    {
        try {
            $request->validate([
                'card_id' => 'required|string'
            ]);

            $business = $request->user()->primaryBusiness()->firstOrFail();

            $product = Product::where('business_id', $business->id)
                             ->where('card_id', $request->card_id)
                             ->first();

            if ($product) {
                // Delete associated BOM items
                $product->billOfMaterials()->delete();
                $product->delete();
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getByCardId($cardId, Request $request)
    {
        try {
            $business = $request->user()->primaryBusiness()->firstOrFail();

            $product = Product::where('business_id', $business->id)
                             ->where('card_id', $cardId)
                             ->with('billOfMaterials')
                             ->first();

            if ($product) {
                return response()->json([
                    'success' => true,
                    'product' => $product,
                    'bom' => $product->billOfMaterials
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'product' => null,
                    'bom' => []
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function saveFromModal(Request $request)
    {
        try {
            $request->validate([
                'card_id' => 'required|string',
                'name' => 'required|string|max:255',
                'category' => 'required|string|max:100',
                'selling_price' => 'nullable|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'unit' => 'required|string|max:50',
                'description' => 'nullable|string'
            ]);

            $business = $request->user()->primaryBusiness()->firstOrFail();

            $productData = [
                'business_id' => $business->id,
                'card_id' => $request->card_id,
                'name' => $request->name,
                'category' => $request->category,
                'selling_price' => $request->selling_price ?? 0,
                'cost_price' => $request->cost_price ?? 0,
                'unit' => $request->unit,
                'description' => $request->description,
                'status' => 'active'
            ];

            $product = Product::updateOrCreate(
                [
                    'business_id' => $business->id,
                    'card_id' => $request->card_id
                ],
                $productData
            );

            return response()->json([
                'success' => true,
                'product' => $product,
                'message' => 'Product saved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function saveBom(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'bom_items' => 'required|array',
                'bom_items.*.material_name' => 'required|string|max:255',
                'bom_items.*.quantity' => 'required|numeric|min:0.01',
                'bom_items.*.unit' => 'required|string|max:50',
                'bom_items.*.cost_per_unit' => 'required|numeric|min:0.01'
            ]);

            $product = Product::findOrFail($request->product_id);

            // Verify ownership
            $business = $request->user()->primaryBusiness()->firstOrFail();
            if ($product->business_id !== $business->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            // Delete existing BOM items
            $product->billOfMaterials()->delete();

            // Create new BOM items
            foreach ($request->bom_items as $bomItem) {
                $product->billOfMaterials()->create([
                    'material_name' => $bomItem['material_name'],
                    'quantity' => $bomItem['quantity'],
                    'unit' => $bomItem['unit'],
                    'cost_per_unit' => $bomItem['cost_per_unit'],
                    'total_cost' => $bomItem['quantity'] * $bomItem['cost_per_unit'],
                    'notes' => $bomItem['notes'] ?? null
                ]);
            }

            // Update product cost price with total BOM cost
            $totalBomCost = $product->billOfMaterials()->sum('total_cost');
            $product->update(['cost_price' => $totalBomCost]);

            return response()->json([
                'success' => true,
                'message' => 'BOM saved successfully',
                'total_cost' => $totalBomCost
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
