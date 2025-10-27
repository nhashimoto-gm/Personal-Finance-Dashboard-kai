<?php
/**
 * 既存のqueries.phpと統合する場合の例
 * 
 * このファイルは analytics-api.php と既存の queries.php を統合する方法を示します
 */

// 既存のqueries.phpを読み込み
require_once __DIR__ . '/../queries.php';

/**
 * 既存関数を活用した拡張API
 */
class AnalyticsIntegration {
    
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * 既存のgetSummary()を使った統計取得
     */
    public function getEnhancedSummary($start_date, $end_date) {
        // 既存関数を使用
        $basic_summary = getSummary($this->pdo, $start_date, $end_date);
        $active_days = getActiveDays($this->pdo, $start_date, $end_date);
        
        // 追加統計を計算
        $days_diff = (strtotime($end_date) - strtotime($start_date)) / 86400;
        $months = ceil($days_diff / 30);
        
        return [
            'basic' => $basic_summary,
            'active_days' => $active_days,
            'period_info' => [
                'start' => $start_date,
                'end' => $end_date,
                'total_days' => $days_diff,
                'total_months' => $months,
                'avg_monthly' => $months > 0 ? $basic_summary['total'] / $months : 0
            ],
            'activity_rate' => $days_diff > 0 ? ($active_days / $days_diff) * 100 : 0
        ];
    }
    
    /**
     * 既存のgetShopData()を使った拡張分析
     */
    public function getShopAnalytics($start_date, $end_date) {
        $shop_data = getShopData($this->pdo, $start_date, $end_date);
        
        // トップショップを分析
        $top_shops = $shop_data['above_4pct'];
        $total = array_sum(array_column($top_shops, 'total'));
        
        $analytics = [];
        foreach ($top_shops as $shop) {
            $percentage = $total > 0 ? ($shop['total'] / $total) * 100 : 0;
            $analytics[] = [
                'shop_id' => $shop['cat_1'],
                'shop_name' => $shop['label1'],
                'total' => $shop['total'],
                'percentage' => round($percentage, 2),
                'rank' => count($analytics) + 1
            ];
        }
        
        return [
            'top_shops' => $analytics,
            'others_total' => $shop_data['below_4pct_total'],
            'grand_total' => $total + $shop_data['below_4pct_total']
        ];
    }
    
    /**
     * 既存のgetCategoryData()を使った拡張分析
     */
    public function getCategoryAnalytics($start_date, $end_date) {
        $category_data = getCategoryData($this->pdo, $start_date, $end_date);
        
        $total = array_sum(array_column($category_data, 'total'));
        
        $analytics = [];
        foreach ($category_data as $cat) {
            $percentage = $total > 0 ? ($cat['total'] / $total) * 100 : 0;
            $analytics[] = [
                'category_id' => $cat['cat_2'],
                'category_name' => $cat['label2'],
                'total' => $cat['total'],
                'percentage' => round($percentage, 2)
            ];
        }
        
        return [
            'categories' => $analytics,
            'total' => $total
        ];
    }
    
    /**
     * トレンド分析（既存関数を活用）
     */
    public function getTrendAnalysis($period_months = 12) {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-$period_months months"));
        
        // 月次データ取得
        $daily_data = getDailyData($this->pdo, $start_date, $end_date);
        
        // 月ごとに集計
        $monthly = [];
        foreach ($daily_data as $day) {
            $month = substr($day['re_date'], 0, 7);
            if (!isset($monthly[$month])) {
                $monthly[$month] = 0;
            }
            $monthly[$month] += $day['daily_total'];
        }
        
        // トレンド計算（移動平均）
        $values = array_values($monthly);
        $moving_avg = [];
        for ($i = 0; $i < count($values); $i++) {
            if ($i < 2) {
                $moving_avg[] = null;
            } else {
                $slice = array_slice($values, $i - 2, 3);
                $moving_avg[] = array_sum($slice) / 3;
            }
        }
        
        return [
            'monthly_totals' => $monthly,
            'moving_average' => array_combine(array_keys($monthly), $moving_avg),
            'trend' => $this->calculateTrend($values)
        ];
    }
    
    /**
     * トレンド方向を計算
     */
    private function calculateTrend($values) {
        if (count($values) < 4) {
            return 'insufficient_data';
        }
        
        $recent = array_slice($values, -3);
        $older = array_slice($values, -6, 3);
        
        $recent_avg = array_sum($recent) / count($recent);
        $older_avg = array_sum($older) / count($older);
        
        $change = (($recent_avg - $older_avg) / $older_avg) * 100;
        
        if ($change > 5) {
            return 'increasing';
        } elseif ($change < -5) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }
    
    /**
     * 予算との比較（既存のbudgets機能を活用）
     */
    public function getBudgetComparison($year, $month) {
        $budget_progress = getBudgetProgress($this->pdo, $year, $month);
        
        if (!$budget_progress) {
            return ['status' => 'no_budget_set'];
        }
        
        $remaining_days = date('t', mktime(0, 0, 0, $month, 1, $year)) - date('d');
        $daily_budget = $remaining_days > 0 
            ? $budget_progress['remaining'] / $remaining_days 
            : 0;
        
        return [
            'budget' => $budget_progress,
            'recommendations' => [
                'daily_limit' => round($daily_budget),
                'status' => $budget_progress['alert_level'],
                'message' => $this->getBudgetMessage($budget_progress['percentage'])
            ]
        ];
    }
    
    private function getBudgetMessage($percentage) {
        if ($percentage >= 100) {
            return '予算を超過しています。支出を控えめにしましょう。';
        } elseif ($percentage >= 80) {
            return '予算の80%に達しました。残りの日数に注意しましょう。';
        } elseif ($percentage >= 50) {
            return '順調です。このペースを維持しましょう。';
        } else {
            return '予算に余裕があります。';
        }
    }
}

/**
 * 統合APIエンドポイント
 * 
 * 使用例:
 * ?action=enhanced_summary&start_date=2024-01-01&end_date=2024-12-31
 * ?action=shop_analytics&start_date=2024-01-01&end_date=2024-12-31
 * ?action=trend&months=12
 * ?action=budget_check&year=2024&month=10
 */

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    // この場合、このファイルが直接呼ばれた
    
    header('Content-Type: application/json; charset=utf-8');
    
    // データベース接続（既存の方法を使用）
    require_once __DIR__ . '/../config.php';  // あなたの設定ファイル
    
    $analytics = new AnalyticsIntegration($pdo);
    $action = $_GET['action'] ?? 'help';
    
    try {
        switch ($action) {
            case 'enhanced_summary':
                $start = $_GET['start_date'] ?? '2008-01-01';
                $end = $_GET['end_date'] ?? date('Y-m-d');
                echo json_encode($analytics->getEnhancedSummary($start, $end));
                break;
                
            case 'shop_analytics':
                $start = $_GET['start_date'] ?? date('Y-m-01');
                $end = $_GET['end_date'] ?? date('Y-m-d');
                echo json_encode($analytics->getShopAnalytics($start, $end));
                break;
                
            case 'category_analytics':
                $start = $_GET['start_date'] ?? date('Y-m-01');
                $end = $_GET['end_date'] ?? date('Y-m-d');
                echo json_encode($analytics->getCategoryAnalytics($start, $end));
                break;
                
            case 'trend':
                $months = (int)($_GET['months'] ?? 12);
                echo json_encode($analytics->getTrendAnalysis($months));
                break;
                
            case 'budget_check':
                $year = (int)($_GET['year'] ?? date('Y'));
                $month = (int)($_GET['month'] ?? date('n'));
                echo json_encode($analytics->getBudgetComparison($year, $month));
                break;
                
            default:
                echo json_encode([
                    'error' => 'Unknown action',
                    'available_actions' => [
                        'enhanced_summary',
                        'shop_analytics',
                        'category_analytics',
                        'trend',
                        'budget_check'
                    ]
                ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
