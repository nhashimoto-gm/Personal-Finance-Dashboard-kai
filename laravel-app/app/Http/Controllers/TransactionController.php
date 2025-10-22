<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Shop;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * Display a listing of transactions.
     */
    public function index(Request $request)
    {
        $query = Transaction::with(['shop', 'category'])->latest();

        // Apply filters if provided
        if ($request->filled('shop_id')) {
            $query->forShop($request->shop_id);
        }

        if ($request->filled('category_id')) {
            $query->forCategory($request->category_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->withinDateRange($request->start_date, $request->end_date);
        }

        $transactions = $query->paginate(50);

        return view('transactions.index', compact('transactions'));
    }

    /**
     * Show the form for creating a new transaction.
     */
    public function create()
    {
        $shops = Shop::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('transactions.create', compact('shops', 'categories'));
    }

    /**
     * Store a newly created transaction.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_date' => 'required|date',
            'shop_id' => 'required|exists:shops,id',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Transaction::create([
            'transaction_date' => $request->transaction_date,
            'shop_id' => $request->shop_id,
            'category_id' => $request->category_id,
            'amount' => $request->amount,
        ]);

        return redirect()->route('transactions.entry')
            ->with('success', __('messages.transaction_added'));
    }

    /**
     * Show the transaction entry form.
     */
    public function entry()
    {
        $shops = Shop::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('transactions.entry', compact('shops', 'categories'));
    }

    /**
     * Display the specified transaction.
     */
    public function show(Transaction $transaction)
    {
        $transaction->load(['shop', 'category']);

        return view('transactions.show', compact('transaction'));
    }

    /**
     * Show the form for editing the specified transaction.
     */
    public function edit(Transaction $transaction)
    {
        $shops = Shop::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('transactions.edit', compact('transaction', 'shops', 'categories'));
    }

    /**
     * Update the specified transaction.
     */
    public function update(Request $request, Transaction $transaction)
    {
        $validator = Validator::make($request->all(), [
            'transaction_date' => 'required|date',
            'shop_id' => 'required|exists:shops,id',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $transaction->update([
            'transaction_date' => $request->transaction_date,
            'shop_id' => $request->shop_id,
            'category_id' => $request->category_id,
            'amount' => $request->amount,
        ]);

        return redirect()->route('transactions.index')
            ->with('success', __('messages.transaction_updated'));
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy(Transaction $transaction)
    {
        $transaction->delete();

        return redirect()->route('transactions.index')
            ->with('success', __('messages.transaction_deleted'));
    }
}
