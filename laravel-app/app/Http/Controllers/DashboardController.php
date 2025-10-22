<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Shop;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $periodRange = $request->get('period_range', 12);

        // Summary statistics
        $summary = $this->getSummary($startDate, $endDate);

        // Active days count
        $activeDays = Transaction::withinDateRange($startDate, $endDate)
            ->distinct('transaction_date')
            ->count('transaction_date');

        $dailyAverage = $activeDays > 0 ? round($summary['total'] / $activeDays) : 0;

        // Shop data for pie chart
        $shopData = $this->getShopData($startDate, $endDate);

        // Category data for bar chart
        $categoryData = $this->getCategoryData($startDate, $endDate);

        // Daily trend data
        $dailyData = $this->getDailyData($startDate, $endDate);

        // Period trend data
        $periodData = $this->getPeriodData($periodRange);

        // Recent transactions
        $searchShop = $request->get('search_shop');
        $searchCategory = $request->get('search_category');
        $recentLimit = $request->get('recent_limit', 20);

        $recentTransactions = $this->getRecentTransactions(
            $startDate,
            $endDate,
            $searchShop,
            $searchCategory,
            $recentLimit
        );

        return view('dashboard.index', compact(
            'summary',
            'activeDays',
            'dailyAverage',
            'shopData',
            'categoryData',
            'dailyData',
            'periodData',
            'recentTransactions',
            'startDate',
            'endDate',
            'periodRange',
            'searchShop',
            'searchCategory'
        ));
    }

    /**
     * Get summary statistics.
     */
    private function getSummary(string $startDate, string $endDate): array
    {
        $data = Transaction::withinDateRange($startDate, $endDate)
            ->selectRaw('
                SUM(amount) as total,
                COUNT(*) as record_count,
                COUNT(DISTINCT shop_id) as shop_count
            ')
            ->first();

        return [
            'total' => $data->total ?? 0,
            'record_count' => $data->record_count ?? 0,
            'shop_count' => $data->shop_count ?? 0,
        ];
    }

    /**
     * Get shop spending data.
     */
    private function getShopData(string $startDate, string $endDate): array
    {
        $shopData = Transaction::withinDateRange($startDate, $endDate)
            ->select('shop_id', DB::raw('SUM(amount) as total'))
            ->with('shop:id,name')
            ->groupBy('shop_id')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) {
                return [
                    'shop_id' => $item->shop_id,
                    'shop_name' => $item->shop->name,
                    'total' => $item->total,
                ];
            })
            ->toArray();

        // Separate "Others" shop and take top 7
        $othersShop = null;
        $regularShops = [];

        foreach ($shopData as $shop) {
            if (in_array($shop['shop_name'], ['Others', 'その他'])) {
                $othersShop = $shop;
            } else {
                $regularShops[] = $shop;
            }
        }

        $top7Shops = array_slice($regularShops, 0, 7);
        $remainingShops = array_slice($regularShops, 7);

        $remainingTotal = array_sum(array_column($remainingShops, 'total'));

        return [
            'above_4pct' => $top7Shops,
            'below_4pct_total' => $remainingTotal,
            'others_shop' => $othersShop,
        ];
    }

    /**
     * Get category spending data.
     */
    private function getCategoryData(string $startDate, string $endDate): array
    {
        return Transaction::withinDateRange($startDate, $endDate)
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->with('category:id,name')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'category_id' => $item->category_id,
                    'category_name' => $item->category->name,
                    'total' => $item->total,
                ];
            })
            ->toArray();
    }

    /**
     * Get daily spending data.
     */
    private function getDailyData(string $startDate, string $endDate): array
    {
        return Transaction::withinDateRange($startDate, $endDate)
            ->selectRaw('transaction_date as re_date, SUM(amount) as daily_total')
            ->groupBy('transaction_date')
            ->orderBy('transaction_date')
            ->get()
            ->toArray();
    }

    /**
     * Get period trend data.
     */
    private function getPeriodData(int $periodRange): array
    {
        $isMonthly = $periodRange < 60;
        $dateFrom = now()->subMonths($periodRange);

        if ($isMonthly) {
            $data = Transaction::where('transaction_date', '>=', $dateFrom)
                ->selectRaw("
                    DATE_FORMAT(transaction_date, '%Y-%m') as period,
                    shop_id,
                    SUM(amount) as total
                ")
                ->with('shop:id,name')
                ->groupBy('period', 'shop_id')
                ->orderBy('period')
                ->get();
        } else {
            $data = Transaction::where('transaction_date', '>=', $dateFrom)
                ->selectRaw("
                    YEAR(transaction_date) as period,
                    shop_id,
                    SUM(amount) as total
                ")
                ->with('shop:id,name')
                ->groupBy('period', 'shop_id')
                ->orderBy('period')
                ->get();
        }

        return $data->map(function ($item) {
            return [
                'period' => $item->period,
                'shop_name' => $item->shop->name,
                'total' => $item->total,
            ];
        })->toArray();
    }

    /**
     * Get recent transactions.
     */
    private function getRecentTransactions(
        string $startDate,
        string $endDate,
        ?string $searchShop,
        ?string $searchCategory,
        int $limit
    ): array {
        $query = Transaction::withinDateRange($startDate, $endDate)
            ->with(['shop:id,name', 'category:id,name']);

        if ($searchShop) {
            $shop = Shop::where('name', $searchShop)->first();
            if ($shop) {
                $query->forShop($shop->id);
            }
        }

        if ($searchCategory) {
            $category = Category::where('name', $searchCategory)->first();
            if ($category) {
                $query->forCategory($category->id);
            }
        }

        return $query->latest()
            ->limit($limit)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    're_date' => $transaction->transaction_date->toDateString(),
                    'label1' => $transaction->shop->name,
                    'label2' => $transaction->category->name,
                    'price' => $transaction->amount,
                ];
            })
            ->toArray();
    }
}
