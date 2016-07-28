<?php
$framework_update_dir = '/modules/core/updates/framework';
$update_files = array(
	'/phproad/modules/phpr/classes/phpr_response.php',
	'/phproad/modules/phpr/classes/phpr_security.php',
	'/phproad/modules/phpr/classes/phpr_securityframework.php',
	'/phproad/system/initialize.php',
);

Core_ZipHelper::unzip(PATH_APP.'/phproad/thirdpart/', PATH_APP.$framework_update_dir.'/phproad/thirdpart/random_compat.zip');


require PATH_APP.'/phproad/thirdpart/random_compat/lib/random.php';

function generate($bytes = 32)
{
	  $string = '';
	
	  try {
		    $string = random_bytes(32);
		    $string = bin2hex($string);
		  } catch (TypeError $e) {
		    // die("An unexpected error has occurred");
		  } catch (Error $e) {
		    // die("An unexpected error has occurred");
		  } catch (Exception $e) {
		    // die("Could not generate a random string. Is our OS secure?");
		  }

  return $string;
}

function writeFile($path, $contents)
{
	    if ( !($fp = @fopen( $path, 'a' )) )
		      return false;

    flock( $fp, LOCK_EX );

    if ( !@fwrite( $fp, $contents ) )
		    {
		      fclose( $fp );
		      return false;
    }

    flock( $fp, LOCK_UN );
    fclose( $fp );

    return true;
}

$salts = array(
	  'COOKIE_SALT' => generate()
		);

$config_dir = PATH_APP."/config";
$key_file = $config_dir."/keys.php";

if (file_exists($key_file)) {
	  $key_file = $config_dir."/keys_.php";
	}

$template = "<?php\n\n";
$template .= "if (!isset(\$CONFIG))\n\t\$CONFIG = array();\n\n";

foreach($salts as $name => $salt) {
	  $template .= "if (!isset(\$CONFIG['".$name."']))\n\t\$CONFIG['".$name."'] = '".$salt."';\n\n";
	}

if(!writeFile($key_file, $template)){
	throw new Phpr_ApplicationException('Could not create new security keys in /config folder. Make this folder writable by PHP and try again.');
}

foreach($update_files as $file){
	if(!copy(PATH_APP.$framework_update_dir.$file, PATH_APP.$file)){
		throw new Phpr_ApplicationException('Could not copy '.$framework_update_dir.$file.' to '.$file.' check write permissions for PHP');
	}
}