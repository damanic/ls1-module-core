<?php
$framework_update_dir = '/modules/core/updates/framework';
$update_files = array(
	'/phproad/modules/db/classes/db_activerecordproxy.php',
	'/phproad/modules/db/classes/db_activerecord.php',
);

foreach($update_files as $file){
	if(!copy(PATH_APP.$framework_update_dir.$file, PATH_APP.$file)){
		throw new Phpr_ApplicationException('Could not copy '.$framework_update_dir.$file.' to '.$file.' check write permissions for PHP');
	}
}