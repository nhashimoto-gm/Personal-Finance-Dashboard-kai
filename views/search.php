<?php
// views/search_results.php - 検索結果表示
?>
<?php if (!empty($search_shop) || !empty($search_category)): ?>
    <div class="col-md-12 mb-4" id="searchResultsSection">
        <div class="card">
            <div class="card-header bg-transparent">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <h5 class="mb-0">
                        <i class="bi bi-search"></i> Transactions（<?= count($search_results) ?> records：
                        <?php
                        $conditions = [];
                        if (!empty($search_shop)) $conditions[] = 'Shop=' . htmlspecialchars($search_shop);
                        if (!empty($search_category)) $conditions[] = 'Category=' . htmlspecialchars($search_category);
                        echo implode(', ', $conditions);
                        ?>）
                    </h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&search_shop=<?= urlencode($search_shop) ?>&search_category=<?= urlencode($search_category) ?>&search_limit=100#searchResultsSection"
                            class="btn <?= $search_limit == 100 ? 'btn-primary' : 'btn-outline-primary' ?>">100</a>
                        <a href="?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&search_shop=<?= urlencode($search_shop) ?>&search_category=<?= urlencode($search_category) ?>&search_limit=500#searchResultsSection"
                            class="btn <?= $search_limit == 500 ? 'btn-primary' : 'btn-outline-primary' ?>">500</a>
                        <a href="?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&search_shop=<?= urlencode($search_shop) ?>&search_category=<?= urlencode($search_category) ?>&search_limit=1000#searchResultsSection"
                            class="btn <?= $search_limit == 1000 ? 'btn-primary' : 'btn-outline-primary' ?>">1000</a>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 600px;">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th data-i18n="date">日付</th>
                                <th data-i18n="shop">ショップ</th>
                                <th data-i18n="category">カテゴリ</th>
                                <th class="text-end" data-i18n="amount">金額</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $search_total = 0;
                            foreach ($search_results as $r):
                                $search_total += $r['price'];
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['re_date']) ?></td>
                                    <td>
                                        <span class="badge bg-primary clickable"
                                            onclick="searchByShop('<?= htmlspecialchars($r['label1'], ENT_QUOTES) ?>')">
                                            <?= htmlspecialchars($r['label1']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="clickable"
                                            onclick="searchByCategory('<?= htmlspecialchars($r['label2'], ENT_QUOTES) ?>')">
                                            <?= htmlspecialchars($r['label2']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">¥<?= number_format($r['price']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light sticky-bottom">
                            <tr>
                                <th colspan="3" class="text-end"><span data-i18n="total">合計：</span></th>
                                <th class="text-end">¥<?= number_format($search_total) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>