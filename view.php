<?php
// view.php - ビューファイル
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= generateCsrfToken() ?>">
    <title>Personal Finance Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://d3js.org/d3.v7.min.js"></script>
</head>

<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-wallet2"></i> <span data-i18n="title"
                    onclick="window.location.href='index.php';"
                    style="cursor: pointer;">
                    Personal Finance Dashboard
                </span>
            </span>
            <div>
                <button class="btn btn-outline-light btn-sm me-2" id="langToggle">
                    <i class="bi bi-translate"></i> <span id="langLabel">JP</span>
                </button>
                <button class="btn btn-outline-light" id="themeToggle">
                    <i class="bi bi-moon-fill" id="themeIcon"></i>
                </button>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <!-- メッセージ表示 -->
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-top: 0.5rem;">
                <?= htmlspecialchars($successMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin-top: 0.5rem;">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>

        <ul class="nav nav-tabs" role="tablist" style="margin-top: 0.5rem;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard-pane"
                    type="button" role="tab" aria-controls="dashboard-pane" aria-selected="true">
                    <i class="bi bi-graph-up"></i> <span data-i18n="tabDashboard">ダッシュボード</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="entry-tab" data-bs-toggle="tab" data-bs-target="#entry-pane" type="button"
                    role="tab" aria-controls="entry-pane" aria-selected="false">
                    <i class="bi bi-plus-circle"></i> <span data-i18n="tabDataEntry">入力</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="management-tab" data-bs-toggle="tab" data-bs-target="#management-pane"
                    type="button" role="tab" aria-controls="management-pane" aria-selected="false">
                    <i class="bi bi-gear"></i> <span data-i18n="tabManagement">マスター</span>
                </button>
            </li>
        </ul>
    </div>

    <div class="container-fluid mt-4">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="dashboard-pane" role="tabpanel" aria-labelledby="dashboard-tab">
                <?php require_once __DIR__ . '/views/dashboard.php'; ?>
            </div>

            <div class="tab-pane fade" id="entry-pane" role="tabpanel" aria-labelledby="entry-tab">
                <?php require_once __DIR__ . '/views/entry.php'; ?>
            </div>

            <div class="tab-pane fade" id="management-pane" role="tabpanel" aria-labelledby="management-tab">
                <?php require_once __DIR__ . '/views/management.php'; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
    <script>
        // データをJavaScriptに渡す
        window.translationsData = <?= json_encode(getTranslations()) ?>;
        window.startDate = '<?= htmlspecialchars($start_date) ?>';
        window.endDate = '<?= htmlspecialchars($end_date) ?>';
        window.recentLimit = '<?= htmlspecialchars($recent_limit) ?>';
        window.activeTab = '<?= isset($_SESSION['form_tab']) ? $_SESSION['form_tab'] : '' ?>';

        const chartData = {
            shop_data_above_4pct: <?= json_encode($shop_data_above_4pct) ?>,
            others_shop: <?= json_encode($others_shop) ?>,
            shop_data_below_4pct_total: <?= $shop_data_below_4pct_total ?>,
            category_data: <?= json_encode($category_data) ?>,
            daily_data: <?= json_encode($daily_data) ?>,
            period_data: <?= json_encode($period_data) ?>,
            period_range: <?= $period_range ?>
        };

        // 初期描画
        window.addEventListener('load', () => {
            applyHighchartsTheme(chartData);
        });

        // テーマ変更を監視
        const observer = new MutationObserver(() => applyHighchartsTheme(chartData));
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-bs-theme']
        });
    </script>
    <script src="assets/js/app.js"></script>
</body>

</html>
<?php
// セッション変数クリア
if (isset($_SESSION['form_tab'])) unset($_SESSION['form_tab']);
if (isset($_SESSION['form_re_date'])) unset($_SESSION['form_re_date']);
if (isset($_SESSION['form_label1'])) unset($_SESSION['form_label1']);
if (isset($_SESSION['form_label2'])) unset($_SESSION['form_label2']);
?>