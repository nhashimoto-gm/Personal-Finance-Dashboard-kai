<?php
// logout.php - ログアウト処理
require_once 'config.php';

// ログアウト実行
logoutUser();

// ログインページにリダイレクト
header('Location: login.php');
exit;
