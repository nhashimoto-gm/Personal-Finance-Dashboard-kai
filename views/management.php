<?php
// views/management.php - マスター管理画面
?>
<!-- Recurring Expenses Section -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header bg-transparent">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-arrow-repeat"></i> <span data-i18n="recurringExpenses">定期支出管理</span></h5>
                    <button class="btn btn-sm btn-primary" onclick="showAddRecurringExpenseDialog()">
                        <i class="bi bi-plus"></i> <span data-i18n="add">追加</span>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><span data-i18n="name">名前</span></th>
                                <th><span data-i18n="shop">ショップ</span></th>
                                <th><span data-i18n="category">カテゴリ</span></th>
                                <th><span data-i18n="amount">金額</span></th>
                                <th><span data-i18n="dayOfMonth">支払日</span></th>
                                <th><span data-i18n="period">期間</span></th>
                                <th><span data-i18n="status">状態</span></th>
                                <th><span data-i18n="actions">操作</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recurring_expenses)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        <span data-i18n="noRecurringExpenses">定期支出が登録されていません</span>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recurring_expenses as $expense): ?>
                                    <tr class="<?= $expense['is_active'] ? '' : 'text-muted' ?>">
                                        <td><?= htmlspecialchars($expense['name']) ?></td>
                                        <td><?= htmlspecialchars($expense['shop_name']) ?></td>
                                        <td><?= htmlspecialchars($expense['category_name']) ?></td>
                                        <td>¥<?= number_format($expense['price']) ?></td>
                                        <td><?= $expense['day_of_month'] ?><span data-i18n="day">日</span></td>
                                        <td>
                                            <?= htmlspecialchars($expense['start_date']) ?>
                                            <?php if ($expense['end_date']): ?>
                                                ~ <?= htmlspecialchars($expense['end_date']) ?>
                                            <?php else: ?>
                                                ~ <span data-i18n="ongoing">継続中</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($expense['is_active']): ?>
                                                <span class="badge bg-success"><span data-i18n="active">有効</span></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><span data-i18n="inactive">無効</span></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editRecurringExpense(<?= $expense['id'] ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-<?= $expense['is_active'] ? 'warning' : 'success' ?>"
                                                    onclick="toggleRecurringExpense(<?= $expense['id'] ?>)">
                                                <i class="bi bi-<?= $expense['is_active'] ? 'pause' : 'play' ?>"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteRecurringExpense(<?= $expense['id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-transparent">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-shop"></i> <span
                            data-i18n="shopManagement">ショップ管理</span></h5>
                    <button class="btn btn-sm btn-primary" onclick="showAddShopDialog()">
                        <i class="bi bi-plus"></i> <span data-i18n="add">追加</span>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <h6 data-i18n="registeredShops">登録済みショップ</h6>
                <div class="list-group">
                    <?php foreach ($shops as $shop): ?>
                        <div class="list-group-item">
                            <span><?= htmlspecialchars($shop) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-transparent">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-tag"></i> <span
                            data-i18n="categoryManagement">カテゴリ管理</span></h5>
                    <button class="btn btn-sm btn-primary" onclick="showAddCategoryDialog()">
                        <i class="bi bi-plus"></i> <span data-i18n="add">追加</span>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <h6 data-i18n="registeredCategories">登録済みカテゴリ</h6>
                <div class="list-group">
                    <?php foreach ($categories as $cat): ?>
                        <div class="list-group-item">
                            <span><?= htmlspecialchars($cat) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>