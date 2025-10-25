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
                        <button type="submit" class="btn btn-primary w-100" style="margin-top: 2rem;" title="Filter">
                            <i class="bi bi-search"></i> <span class="btn-text" data-i18n="filter">絞り込み</span>
                        </button>
                    </div>
                    <div class="col-4 col-md-2">
                        <button type="button" class="btn btn-secondary w-100" id="resetBtn"
                            style="margin-top: 2rem;" title="Reset">
                            <i class="bi bi-arrow-clockwise"></i> <span class="btn-text" data-i18n="reset">リセット</span>
                        </button>
                    </div>
                    <div class="col-4 col-md-2">
                        <a href="export.php?type=summary&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>"
                           class="btn btn-success w-100" style="margin-top: 2rem;" title="Export">
                            <i class="bi bi-download"></i> <span class="btn-text" data-i18n="export">Export</span>
                        </a>
                    </div>
                </form>
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
<?php
$month_names = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
$budget_period_en = $month_names[$current_month - 1] . ' ' . $current_year;
?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-<?= $budget_progress['alert_level'] ?>">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-piggy-bank"></i> <span data-i18n="budgetProgress">予算進捗</span> (<span id="budgetPeriod" data-year="<?= $current_year ?>" data-month="<?= $current_month ?>"><?= $budget_period_en ?></span>)</h5>
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
                    <div class="col-md-3">
                        <div class="text-muted small" data-i18n="budgetAmount">予算額</div>
                        <div class="h4">¥<?= number_format($budget_progress['budget_amount']) ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small" data-i18n="actualAmount">実績額</div>
                        <div class="h4 text-<?= $budget_progress['alert_level'] ?>">¥<?= number_format($budget_progress['actual_amount']) ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small" data-i18n="remaining">残高</div>
                        <div class="h4 text-<?= $budget_progress['alert_level'] ?>">¥<?= number_format($budget_progress['remaining']) ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small" data-i18n="percentage">達成率</div>
                        <div class="h4 text-<?= $budget_progress['alert_level'] ?>"><?= $budget_progress['percentage'] ?>%</div>
                    </div>
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
