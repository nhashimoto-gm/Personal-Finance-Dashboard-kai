<?php
// views/transactions_table.php - 取引履歴テーブル
?>
<div class="col-md-12 mb-4">
    <div class="card" id="recentTransactionsSection">
        <div class="card-header bg-transparent">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> <span
                        data-i18n="recentTransactions">Recent Transactions</span></h5>
                <div class="d-flex gap-2 flex-wrap">
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&search_shop=<?= urlencode($search_shop) ?>&search_category=<?= urlencode($search_category) ?>&recent_limit=20#recentTransactionsSection"
                            class="btn <?= $recent_limit == 20 ? 'btn-primary' : 'btn-outline-primary' ?>">20</a>
                        <a href="?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&search_shop=<?= urlencode($search_shop) ?>&search_category=<?= urlencode($search_category) ?>&recent_limit=100#recentTransactionsSection"
                            class="btn <?= $recent_limit == 100 ? 'btn-primary' : 'btn-outline-primary' ?>">100</a>
                        <a href="?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&search_shop=<?= urlencode($search_shop) ?>&search_category=<?= urlencode($search_category) ?>&recent_limit=500#recentTransactionsSection"
                            class="btn <?= $recent_limit == 500 ? 'btn-primary' : 'btn-outline-primary' ?>">500</a>
                        <a href="?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&search_shop=<?= urlencode($search_shop) ?>&search_category=<?= urlencode($search_category) ?>&recent_limit=1000#recentTransactionsSection"
                            class="btn <?= $recent_limit == 1000 ? 'btn-primary' : 'btn-outline-primary' ?>">1000</a>
                    </div>
                    <a href="export.php?type=transactions&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&search_shop=<?= urlencode($search_shop) ?>&search_category=<?= urlencode($search_category) ?>"
                        class="btn btn-success btn-sm btn-icon-only" title="Export" aria-label="Export">
                        <i class="bi bi-download"></i>
                    </a>
                </div>
            </div>
            <?php if (!empty($search_shop) || !empty($search_category)): ?>
                <div class="active-filters mt-2">
                    <?php if (!empty($search_shop)): ?>
                        <div class="filter-badge">
                            <i class="bi bi-shop"></i>
                            <span><?= htmlspecialchars($search_shop) ?></span>
                            <button type="button" class="btn-close" onclick="removeFilter('shop')"
                                aria-label="Remove shop filter"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($search_category)): ?>
                        <div class="filter-badge">
                            <i class="bi bi-tag"></i>
                            <span><?= htmlspecialchars($search_category) ?></span>
                            <button type="button" class="btn-close" onclick="removeFilter('category')"
                                aria-label="Remove category filter"></button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th data-i18n="date">日付</th>
                            <th data-i18n="shop">ショップ</th>
                            <th data-i18n="category">カテゴリ</th>
                            <th class="text-end" data-i18n="amount">金額</th>
                            <th class="text-center" data-i18n="actions">アクション</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_transactions as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['re_date']) ?></td>
                                <td>
                                    <span class="badge bg-primary clickable"
                                        onclick="searchByShop('<?= htmlspecialchars($t['label1'], ENT_QUOTES) ?>')">
                                        <?= htmlspecialchars($t['label1']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="clickable"
                                        onclick="searchByCategory('<?= htmlspecialchars($t['label2'], ENT_QUOTES) ?>')">
                                        <?= htmlspecialchars($t['label2']) ?>
                                    </span>
                                </td>
                                <td class="text-end fw-bold">¥<?= number_format($t['price']) ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                onclick="editTransaction(<?= $t['id'] ?>, '<?= htmlspecialchars($t['re_date'], ENT_QUOTES) ?>', <?= $t['price'] ?>, '<?= htmlspecialchars($t['label1'], ENT_QUOTES) ?>', '<?= htmlspecialchars($t['label2'], ENT_QUOTES) ?>')"
                                                title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                                onclick="deleteTransaction(<?= $t['id'] ?>)"
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>