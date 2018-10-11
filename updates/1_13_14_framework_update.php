<?php
$file_name = '_form_field_dropdown.htm';
$current_file = PATH_APP.'/phproad/modules/db/behaviors/db_formbehavior/partials/'.$file_name;
$replacement_file = PATH_APP.'/modules/core/updates/framework/phproad/modules/db/behaviors/db_formbehavior/partials/'.$file_name;
if(!copy($replacement_file, $current_file)){
	throw new Phpr_ApplicationException('Could not copy '.$file_name.' to '.$current_file.' check write permissions for PHP');
}