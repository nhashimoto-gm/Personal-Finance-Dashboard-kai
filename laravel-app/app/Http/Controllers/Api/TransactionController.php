<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * Display a listing of transactions.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Transaction::with(['shop', 'category'])->latest();

        // Apply filters
        if ($request->filled('shop_id')) {
            $query->forShop($request->shop_id);
        }

        if ($request->filled('category_id')) {
            $query->forCategory($request->category_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->withinDateRange($request->start_date, $request->end_date);
        }

        $perPage = min($request->get('per_page', 50), 100);
        $transactions = $query->paginate($perPage);

        return TransactionResource::collection($transactions);
    }

    /**
     * Store a newly created transaction.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_date' => 'required|date',
            'shop_id' => 'required|exists:shops,id',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $transaction = Transaction::create($request->only([
            'transaction_date',
            'shop_id',
            'category_id',
            'amount',
        ]));

        $transaction->load(['shop', 'category']);

        return response()->json([
            'message' => 'Transaction created successfully',
            'data' => new TransactionResource($transaction),
        ], 201);
    }

    /**
     * Display the specified transaction.
     */
    public function show(Transaction $transaction): TransactionResource
    {
        $transaction->load(['shop', 'category']);

        return new TransactionResource($transaction);
    }

    /**
     * Update the specified transaction.
     */
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_date' => 'sometimes|required|date',
            'shop_id' => 'sometimes|required|exists:shops,id',
            'category_id' => 'sometimes|required|exists:categories,id',
            'amount' => 'sometimes|required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $transaction->update($request->only([
            'transaction_date',
            'shop_id',
            'category_id',
            'amount',
        ]));

        $transaction->load(['shop', 'category']);

        return response()->json([
            'message' => 'Transaction updated successfully',
            'data' => new TransactionResource($transaction),
        ]);
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy(Transaction $transaction): JsonResponse
    {
        $transaction->delete();

        return response()->json([
            'message' => 'Transaction deleted successfully',
        ]);
    }

    /**
     * Get transaction statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        $stats = Transaction::withinDateRange($startDate, $endDate)
            ->selectRaw('
                SUM(amount) as total,
                AVG(amount) as average,
                COUNT(*) as count,
                COUNT(DISTINCT shop_id) as unique_shops,
                COUNT(DISTINCT category_id) as unique_categories,
                MIN(amount) as min_amount,
                MAX(amount) as max_amount
            ')
            ->first();

        return response()->json([
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'statistics' => [
                'total' => (int) $stats->total,
                'average' => round($stats->average, 2),
                'count' => (int) $stats->count,
                'unique_shops' => (int) $stats->unique_shops,
                'unique_categories' => (int) $stats->unique_categories,
                'min_amount' => (int) $stats->min_amount,
                'max_amount' => (int) $stats->max_amount,
            ],
        ]);
    }
}
