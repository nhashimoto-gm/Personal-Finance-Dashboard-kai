<?php
// views/entry.php - データ入力画面
?>
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-transparent">
                <h5 class="mb-0"><i class="bi bi-plus-lg"></i> <span
                        data-i18n="addNewTransaction">新規取引を追加</span></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_transaction">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" data-i18n="date">日付</label>
                            <input type="date" class="form-control" name="re_date" id="entryDate"
                                value="<?= isset($_SESSION['form_re_date']) ? htmlspecialchars($_SESSION['form_re_date']) : date('Y-m-d') ?>"
                                required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" data-i18n="amount">金額</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" class="form-control" name="price" placeholder="0"
                                    value="" min="1" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" data-i18n="shop">ショップ</label>
                            <select class="form-select" name="label1" required>
                                <option value="">-- <span data-i18n="selectShop">ショップを選択</span> --
                                </option>
                                <?php foreach ($shops as $shop): ?>
                                    <option value="<?= htmlspecialchars($shop) ?>"
                                        <?= (isset($_SESSION['form_label1']) && $_SESSION['form_label1'] === $shop) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($shop) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" data-i18n="category">カテゴリ</label>
                            <select class="form-select" name="label2" required>
                                <option value="">-- <span data-i18n="selectCategory">カテゴリを選択</span> --
                                </option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"
                                        <?= (isset($_SESSION['form_label2']) && $_SESSION['form_label2'] === $cat) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle"></i> <span data-i18n="addButton">取引を追加</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> <span
                        data-i18n="inputGuide">入力ガイド</span></h6>
            </div>
            <div class="card-body small">
                <p><strong data-i18n="date">日付</strong>: <span data-i18n="guideDate">取引日を選択してください</span>
                </p>
                <p><strong data-i18n="amount">金額</strong>: <span
                        data-i18n="guideAmount">0より大きい金額を入力してください</span></p>
                <p><strong data-i18n="shopCategory">ショップ/カテゴリ</strong>: <span
                        data-i18n="guideSelection">ドロップダウンリストから選択してください</span></p>
                <hr>
                <p class="text-muted"><span data-i18n="required">* は必須項目です</span></p>
            </div>
        </div>
    </div>
</div>