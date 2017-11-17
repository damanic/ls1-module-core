<?php
$file_name = 'mootools.js';
$current_file = PATH_APP.'/phproad/javascript/'.$file_name;
$replacement_file = PATH_APP.'/modules/core/updates/framework/phproad/javascript/'.$file_name;
if(!copy($replacement_file, $current_file)){
	throw new Phpr_ApplicationException('Could not copy '.$file_name.' to '.$current_file.' check write permissions for PHP');
}