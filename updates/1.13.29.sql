ALTER TABLE `core_cron_jobs`
	ADD COLUMN `que_name` VARCHAR(255) NULL AFTER `id`,
	ADD COLUMN `available_at` DATETIME NULL AFTER `created_at`,
	ADD COLUMN `started_at` DATETIME NULL AFTER `available_at`,
	ADD COLUMN `attempts` INT(11) NULL AFTER `retry`,
	ADD COLUMN `version` INT(11) NULL AFTER `attempts`;

UPDATE core_cron_jobs
SET attempts = 0, version = 1
WHERE attempts IS NULL AND version IS NULL;
