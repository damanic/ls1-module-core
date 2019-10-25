<?php

$framework_update = PATH_APP.'/modules/core/updates/framework_update.zip';
$framework_folder = PATH_APP.'/phproad/';
if (file_exists($framework_update)){
	Core_ZipHelper::unzip($framework_folder, $framework_update, $update_file_permissions = true);
	@unlink($framework_update);
}