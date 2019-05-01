ALTER TABLE `core_cron_jobs`
	ADD  INDEX `job_index` (`handler_name`);