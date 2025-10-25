<?php
// views/budget.php - 予算管理ビュー
?>
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0"><i class="bi bi-piggy-bank"></i> <span data-i18n="budgetManagement">予算管理</span></h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="set_budget">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label" data-i18n="year">年</label>
                            <input type="number" class="form-control" name="target_year"
                                   value="<?= date('Y') ?>" min="2000" max="2100" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" data-i18n="month">月</label>
                            <input type="number" class="form-control" name="target_month"
                                   value="<?= date('m') ?>" min="1" max="12" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" data-i18n="budgetAmount">予算額</label>
                            <input type="number" class="form-control" name="amount"
                                   min="1" max="100000000" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Budget Type</label>
                            <select class="form-select" name="budget_type" required>
                                <option value="monthly" data-i18n="monthlyBudget">月次予算</option>
                            </select>
                        </div>
                        <input type="hidden" name="target_id" value="">
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle"></i> <span data-i18n="setBudget">予算を設定</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- 予算一覧 -->
        <div class="card">
            <div class="card-header bg-transparent">
                <h5 class="mb-0"><i class="bi bi-list"></i> 予算一覧</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th data-i18n="year">年</th>
                                <th data-i18n="month">月</th>
                                <th>Type</th>
                                <th data-i18n="budgetAmount">予算額</th>
                                <th data-i18n="actions">アクション</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_budgets)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted" data-i18n="noBudget">予算が設定されていません</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_budgets as $budget): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($budget['target_year']) ?></td>
                                        <td><?= htmlspecialchars($budget['target_month']) ?></td>
                                        <td>
                                            <?php if ($budget['budget_type'] === 'monthly'): ?>
                                                <span class="badge bg-primary" data-i18n="monthlyBudget">月次予算</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>¥<?= number_format($budget['amount']) ?></td>
                                        <td>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                <input type="hidden" name="action" value="delete_budget">
                                                <input type="hidden" name="id" value="<?= $budget['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm"
                                                        onclick="return confirm('<?= htmlspecialchars(getTranslations()['en']['confirmDeleteBudget'] ?? 'Are you sure you want to delete this budget?') ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
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
