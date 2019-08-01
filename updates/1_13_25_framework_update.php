<?php
$framework_update_dir = '/modules/core/updates/framework';
$update_files = array(
	'/phproad/modules/db/behaviors/db_formbehavior/partials/_form_field_time.htm',
	'/phproad/modules/db/classes/db_activerecord.php',
	'/phproad/modules/db/classes/db_columndefinition.php',
);

foreach($update_files as $file){
	if(!copy(PATH_APP.$framework_update_dir.$file, PATH_APP.$file)){
		throw new Phpr_ApplicationException('Could not copy '.$framework_update_dir.$file.' to '.$file.' check write permissions for PHP');
	}
}