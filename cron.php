<?php
/**
 * Execute cron as a standalone
 *
 * Example usage:
 *   /usr/local/bin/php -q /home/YOUR_USERNAME/public_html/modules/core/cron.php
 */

chdir(dirname(__FILE__));
$APP_CONF = array();
$Phpr_InitOnly = true;
include '../../index.php';
Core_Cron::execute_cron();
