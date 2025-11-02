<?php
// views/dashboard.php - ダッシュボード表示部分
?>
<!-- 期間選択 -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="startDate" data-i18n="startDate">開始日</label>
                        <input type="date" class="form-control" name="start_date" id="startDate"
                            value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="endDate" data-i18n="endDate">終了日</label>
                        <input type="date" class="form-control" name="end_date" id="endDate"
                            value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    <div class="col-4 col-md-2">
                        <button type="submit" class="btn btn-primary w-100 filter-action-btn" title="Filter">
                            <i class="bi bi-search"></i> <span class="btn-text" data-i18n="filter">絞り込み</span>
                        </button>
                    </div>
                    <div class="col-4 col-md-2">
                        <button type="button" class="btn btn-secondary w-100 filter-action-btn" id="resetBtn" title="Reset">
                            <i class="bi bi-arrow-clockwise"></i> <span class="btn-text" data-i18n="reset">リセット</span>
                        </button>
                    </div>
                    <div class="col-4 col-md-2">
                        <a href="export.php?type=summary&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>"
                           class="btn btn-success w-100 filter-action-btn" title="Export">
                            <i class="bi bi-download"></i> <span class="btn-text" data-i18n="export">Export</span>
                        </a>
                    </div>
                </form>
                <!-- Quick Filter Buttons -->
                <div class="row g-2 mt-2">
                    <div class="col-auto">
                        <a href="?start_date=<?= date('Y-m-01') ?>&end_date=<?= date('Y-m-t') ?>"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-calendar-month"></i> <span data-i18n="currentMonth">当月</span>
                        </a>
                    </div>
                    <div class="col-auto">
                        <a href="?start_date=<?= date('Y-m-01', strtotime('first day of last month')) ?>&end_date=<?= date('Y-m-t', strtotime('last day of last month')) ?>"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-calendar-minus"></i> <span data-i18n="lastMonth">前月</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- サマリーカード -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">
                    <i class="bi bi-calendar-range"></i> <span data-i18n="periodTotal">期間合計</span>
                </h6>
                <h2 class="card-title text-primary">
                    ¥<?= number_format($total) ?>
                </h2>
                <p class="card-text text-muted small">
                    <?= $start_date ?> ～ <?= $end_date ?>
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">
                    <i class="bi bi-calculator"></i> <span data-i18n="dailyAvg">1日平均</span>
                </h6>
                <h2 class="card-title text-success">
                    ¥<?= number_format($total / max($active_days, 1)) ?>
                </h2>
                <p class="card-text text-muted small">
                    <span data-i18n="activeDays">データがある</span><?= $active_days ?><span
                        data-i18n="daysAvg">日間の平均</span>
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">
                    <i class="bi bi-receipt"></i> <span data-i18n="recordCount">レコード数</span>
                </h6>
                <h2 class="card-title text-info">
                    <?= number_format($record_count) ?><span data-i18n="records"></span>
                </h2>
                <p class="card-text text-muted small">
                    <span data-i18n="transactionCount">期間内の取引数</span>
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">
                    <i class="bi bi-shop"></i> <span data-i18n="shopCount">取引ショップ数</span>
                </h6>
                <h2 class="card-title text-warning">
                    <?= number_format($shop_count) ?><span data-i18n="shops"></span>
                </h2>
                <p class="card-text text-muted small">
                    <span data-i18n="uniqueShops">ユニークショップ数</span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- 予算進捗 -->
<?php if ($budget_progress): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-<?= $budget_progress['alert_level'] ?>">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-piggy-bank"></i> <span data-i18n="budgetProgress">予算進捗</span>
                    <small class="text-muted ms-2">(<?= $start_date ?> ～ <?= $end_date ?>)</small>
                </h5>
                <?php if ($budget_progress['alert_level'] === 'danger'): ?>
                    <span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> <span data-i18n="budgetOver">予算超過！</span></span>
                <?php elseif ($budget_progress['alert_level'] === 'warning'): ?>
                    <span class="badge bg-warning"><i class="bi bi-exclamation-circle"></i> <span data-i18n="budgetWarning">予算の80%に到達</span></span>
                <?php else: ?>
                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> <span data-i18n="budgetOk">予算内</span></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-muted small" data-i18n="budgetAmount">予算額</div>
                        <div class="h4">¥<?= number_format($budget_progress['budget_amount']) ?></div>
                        <small class="text-muted" data-i18n="proratedBudget">日割按分済</small>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-muted small" data-i18n="actualAmount">実績額</div>
                        <div class="h4 text-<?= $budget_progress['alert_level'] ?>">¥<?= number_format($budget_progress['actual_amount']) ?></div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-muted small" data-i18n="remaining">残高</div>
                        <div class="h4 text-<?= $budget_progress['alert_level'] ?>">¥<?= number_format($budget_progress['remaining']) ?></div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-muted small" data-i18n="percentage">達成率</div>
                        <div class="h4 text-<?= $budget_progress['alert_level'] ?>"><?= $budget_progress['percentage'] ?>%</div>
                    </div>
                    <?php if ($predicted_expense): ?>
                    <div class="col-md-12">
                        <hr class="my-2">
                        <div class="row">
                            <div class="col-md-4 col-6">
                                <div class="text-muted small" data-i18n="predictedExpense">予測消費額（当月）</div>
                                <div class="h5 text-info">¥<?= number_format($predicted_expense['predicted_amount']) ?></div>
                                <?php if (isset($predicted_expense['confidence_lower']) && isset($predicted_expense['confidence_upper'])): ?>
                                <small class="text-muted">
                                    <span data-i18n="confidenceInterval">信頼区間</span>:
                                    ¥<?= number_format($predicted_expense['confidence_lower']) ?>
                                    ~ ¥<?= number_format($predicted_expense['confidence_upper']) ?>
                                </small>
                                <?php else: ?>
                                <small class="text-muted">
                                    <span data-i18n="basedOnLastYear">前年実績より算出</span>
                                </small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 col-6">
                                <div class="text-muted small" data-i18n="lastYearActual">前年同月実績</div>
                                <div class="h6">¥<?= number_format($predicted_expense['last_year_actual']) ?></div>
                                <?php if (isset($predicted_expense['trend_coefficient'])): ?>
                                <small class="text-muted">
                                    <span data-i18n="trend">トレンド</span>:
                                    <?php
                                    $trend = $predicted_expense['trend_coefficient'];
                                    if ($trend > 1.05): ?>
                                        <span class="text-danger">↗ <span data-i18n="trendIncreasing">増加傾向</span> (<?= round(($trend - 1) * 100, 1) ?>%)</span>
                                    <?php elseif ($trend < 0.95): ?>
                                        <span class="text-success">↘ <span data-i18n="trendDecreasing">減少傾向</span> (<?= round((1 - $trend) * 100, 1) ?>%)</span>
                                    <?php else: ?>
                                        <span class="text-secondary">→ <span data-i18n="trendStable">横ばい</span></span>
                                    <?php endif; ?>
                                </small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 col-12">
                                <div class="text-muted small" data-i18n="progressInfo">進捗情報</div>
                                <div class="small">
                                    <span data-i18n="currentDay">現在</span>: <?= $predicted_expense['current_day'] ?><span data-i18n="dayUnit">日</span> / <?= $predicted_expense['days_in_month'] ?><span data-i18n="dayUnit">日</span>
                                    (¥<?= number_format($predicted_expense['current_actual']) ?>)
                                </div>
                            </div>
                        </div>
                        <?php if (isset($predicted_expense['methods_used']) && !empty($predicted_expense['methods_used'])): ?>
                        <div class="row mt-2">
                            <div class="col-12">
                                <details class="small">
                                    <summary class="text-muted" style="cursor: pointer;">
                                        <span data-i18n="predictionDetails">予測詳細情報</span>
                                        <span class="badge badge-secondary"><?= count($predicted_expense['methods_used']) ?> <span data-i18n="methods">手法</span></span>
                                    </summary>
                                    <div class="mt-2 p-2 bg-body-tertiary rounded">
                                        <div class="text-muted mb-1" data-i18n="methodsUsed">使用した予測手法:</div>
                                        <ul class="mb-0 small">
                                            <?php
                                            // Method name translation mapping
                                            $method_translation_map = [
                                                'simple_pace' => 'methodSimplePace',
                                                'historical_trend' => 'methodHistoricalTrend',
                                                'weekday_aware' => 'methodWeekdayAware',
                                                'exponential_smoothing' => 'methodExponentialSmoothing',
                                                'arima' => 'methodArima'
                                            ];

                                            foreach ($predicted_expense['methods_used'] as $method):
                                                $translation_key = $method_translation_map[$method] ?? '';
                                            ?>
                                            <li>
                                                <strong>
                                                    <?php if ($translation_key): ?>
                                                        <span data-i18n="<?= $translation_key ?>"><?= ucwords(str_replace('_', ' ', $method)) ?></span>
                                                    <?php else: ?>
                                                        <?= ucwords(str_replace('_', ' ', $method)) ?>
                                                    <?php endif; ?>
                                                </strong>
                                                <?php if (isset($predicted_expense['method_predictions'][$method])): ?>
                                                    : ¥<?= number_format($predicted_expense['method_predictions'][$method]) ?>
                                                <?php endif; ?>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <div class="text-muted mt-2 small">
                                            <span data-i18n="ensembleNote">※ 複数手法のアンサンブル予測を採用</span>
                                        </div>
                                    </div>
                                </details>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="progress" style="height: 30px;">
                    <div class="progress-bar bg-<?= $budget_progress['alert_level'] ?>" role="progressbar"
                         style="width: <?= min($budget_progress['percentage'], 100) ?>%;"
                         aria-valuenow="<?= $budget_progress['percentage'] ?>"
                         aria-valuemin="0"
                         aria-valuemax="100">
                        <?= $budget_progress['percentage'] ?>%
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- ショップ別グラフ -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-transparent">
                <h5 class="mb-0"><i class="bi bi-pie-chart"></i> <span
                        data-i18n="shopExpense">ショップ別支出</span></h5>
            </div>
            <div class="card-body">
                <div id="shopChart"></div>
            </div>
        </div>
    </div>

    <!-- カテゴリ別グラフ -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-transparent">
                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> <span data-i18n="categoryTop10">カテゴリ
                        TOP10</span></h5>
            </div>
            <div class="card-body">
                <div id="categoryChart" class="chart-container"></div>
            </div>
        </div>
    </div>

    <!-- 日別推移グラフ -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-transparent">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> <span
                        data-i18n="dailyTrend">日別支出推移</span></h5>
            </div>
            <div class="card-body">
                <div id="dailyChart" class="chart-container"></div>
            </div>
        </div>
    </div>

    <!-- 日別累積推移グラフ -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-transparent">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> <span
                        data-i18n="cumulativeTrend">日別累積支出推移</span></h5>
            </div>
            <div class="card-body">
                <div id="cumulativeChart" class="chart-container"></div>
            </div>
        </div>
    </div>

    <!-- 期間別推移グラフ -->
    <div class="col-md-12 mb-4" id="periodTrendSection">
        <div class="card">
            <div class="card-header bg-transparent">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span id="periodTrendLabel">Monthly Expense Trend</span>
                        (<span data-i18n="past">過去</span><span
                            id="periodValue"><?= $period_range < 60 ? $period_range : round($period_range / 12) ?></span><span
                            id="periodUnit"
                            data-i18n="<?= $period_range < 60 ? 'months' : 'years' ?>"><?= $period_range < 60 ? 'ヶ月' : '年' ?></span>)
                    </h5>
                    <div class="btn-group" role="group">
                        <a href="?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&period_range=12#periodTrendSection"
                            class="btn btn-sm <?= $period_range == 12 ? 'btn-primary' : 'btn-outline-primary' ?>">12<span
                                data-i18n="monthsShort">ヶ月</span></a>
                        <a href="?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&period_range=24#periodTrendSection"
                            class="btn btn-sm <?= $period_range == 24 ? 'btn-primary' : 'btn-outline-primary' ?>">2<span
                                data-i18n="yearsShort">年</span></a>
                        <a href="?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&period_range=60#periodTrendSection"
                            class="btn btn-sm <?= $period_range == 60 ? 'btn-primary' : 'btn-outline-primary' ?>">5<span
                                data-i18n="yearsShort">年</span></a>
                        <a href="?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&period_range=120#periodTrendSection"
                            class="btn btn-sm <?= $period_range == 120 ? 'btn-primary' : 'btn-outline-primary' ?>">10<span
                                data-i18n="yearsShort">年</span></a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="periodChart" class="chart-container"></div>
            </div>
        </div>
    </div>

    <!-- 取引履歴テーブル -->
    <?php require_once __DIR__ . '/transactions_table.php'; ?>

    <!-- 検索結果 -->
    <?php require_once __DIR__ . '/search_results.php'; ?>
</div>
