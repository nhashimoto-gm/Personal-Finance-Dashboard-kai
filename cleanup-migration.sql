-- ============================================================
-- マイグレーション失敗時のクリーンアップSQL
-- ============================================================
-- 途中で作成されたテーブルを削除して、再実行の準備をします
--
-- 使用方法:
--   mysql -h host -u user -p -P 3306 database < cleanup-migration.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 途中で作成されたテーブルを削除
DROP TABLE IF EXISTS `source_new`;
DROP TABLE IF EXISTS `cat_1_labels_new`;
DROP TABLE IF EXISTS `cat_2_labels_new`;
DROP TABLE IF EXISTS `budgets_new`;
DROP TABLE IF EXISTS `monthly_summary_cache`;

-- usersテーブルは保持（既にユーザーが作成されている場合）
-- 必要に応じて削除する場合は以下のコメントを外してください
-- DROP TABLE IF EXISTS `users`;

-- 一時テーブルも念のため削除
DROP TEMPORARY TABLE IF EXISTS cat_1_mapping_user1;
DROP TEMPORARY TABLE IF EXISTS cat_1_mapping_user2;
DROP TEMPORARY TABLE IF EXISTS cat_2_mapping_user1;
DROP TEMPORARY TABLE IF EXISTS cat_2_mapping_user2;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- クリーンアップ完了
-- ============================================================
-- 次のコマンドでマイグレーションを再実行してください:
-- mysql -h host -u user -p -P 3306 database < migration-existing-data.sql
-- ============================================================
