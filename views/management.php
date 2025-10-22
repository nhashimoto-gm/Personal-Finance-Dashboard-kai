<?php
// views/management.php - マスター管理画面
?>
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