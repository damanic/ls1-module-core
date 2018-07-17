<?php
$file_name = 'version.dat';
$delete_file = PATH_APP.'/modules/core/updates/framework/phproad/modules/db/updates/'.$file_name;
if(file_exists($delete_file)){
	@unlink($delete_file);
}