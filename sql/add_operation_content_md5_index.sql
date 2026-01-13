-- 为 lt_agent_user_bet_logs 表添加 operation_content_md5 字段和联合索引
-- 执行时间：2026-01-14

-- 1. 添加 operation_content_md5 字段
ALTER TABLE `lt_agent_user_bet_logs` 
ADD COLUMN `operation_content_md5` VARCHAR(32) NULL DEFAULT NULL COMMENT 'operation_content的MD5值，用于联合索引' AFTER `bet_logs`;

-- 2. 为已存在的记录填充 operation_content_md5（基于 bet_logs 字段）
UPDATE `lt_agent_user_bet_logs` 
SET `operation_content_md5` = MD5(`bet_logs`) 
WHERE `operation_content_md5` IS NULL AND `bet_logs` IS NOT NULL AND `bet_logs` != '';

-- 3. 创建联合唯一索引 (access_token, operation_content_md5)
-- 注意：如果表中已有重复数据，需要先清理重复数据再创建唯一索引
-- 可以先创建普通索引，确认无重复后再改为唯一索引
ALTER TABLE `lt_agent_user_bet_logs` 
ADD INDEX `idx_access_token_operation_content_md5` (`access_token`, `operation_content_md5`);

-- 如果确认数据无重复，可以改为唯一索引：
-- ALTER TABLE `lt_agent_user_bet_logs` 
-- ADD UNIQUE INDEX `idx_access_token_operation_content_md5` (`access_token`, `operation_content_md5`);

-- 4. 可选：如果需要删除旧的 wp_record_id 相关索引（如果不再需要）
-- ALTER TABLE `lt_agent_user_bet_logs` DROP INDEX `idx_wp_record_id`;
