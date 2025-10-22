<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShopResource;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ShopController extends Controller
{
    /**
     * Display a listing of shops.
     */
    public function index()
    {
        $shops = Shop::orderBy('name')->get();

        return ShopResource::collection($shops);
    }

    /**
     * Store a newly created shop.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('shops')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $shop = Shop::create([
            'name' => trim($request->name),
        ]);

        return response()->json([
            'message' => 'Shop created successfully',
            'data' => new ShopResource($shop),
        ], 201);
    }

    /**
     * Display the specified shop.
     */
    public function show(Shop $shop): ShopResource
    {
        return new ShopResource($shop);
    }

    /**
     * Update the specified shop.
     */
    public function update(Request $request, Shop $shop): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('shops')->ignore($shop->id)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $shop->update([
            'name' => trim($request->name),
        ]);

        return response()->json([
            'message' => 'Shop updated successfully',
            'data' => new ShopResource($shop),
        ]);
    }

    /**
     * Remove the specified shop.
     */
    public function destroy(Shop $shop): JsonResponse
    {
        if ($shop->transactions()->exists()) {
            return response()->json([
                'message' => 'Cannot delete shop with existing transactions',
            ], 409);
        }

        $shop->delete();

        return response()->json([
            'message' => 'Shop deleted successfully',
        ]);
    }
}
