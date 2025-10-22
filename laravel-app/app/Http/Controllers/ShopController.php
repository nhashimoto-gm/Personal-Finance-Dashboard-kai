<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
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

        return view('management.shops', compact('shops'));
    }

    /**
     * Store a newly created shop.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('shops')],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Shop::create([
            'name' => trim($request->name),
        ]);

        return redirect()->back()
            ->with('success', __('messages.shop_added'));
    }

    /**
     * Update the specified shop.
     */
    public function update(Request $request, Shop $shop)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('shops')->ignore($shop->id)],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $shop->update([
            'name' => trim($request->name),
        ]);

        return redirect()->back()
            ->with('success', __('messages.shop_updated'));
    }

    /**
     * Remove the specified shop.
     */
    public function destroy(Shop $shop)
    {
        // Check if shop has transactions
        if ($shop->transactions()->exists()) {
            return redirect()->back()
                ->with('error', __('messages.shop_has_transactions'));
        }

        $shop->delete();

        return redirect()->back()
            ->with('success', __('messages.shop_deleted'));
    }
}
