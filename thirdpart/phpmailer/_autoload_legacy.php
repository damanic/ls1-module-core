<?php
/**
 * Fall back for traditional autoload for old PHP versions
 * @param string $classname The name of the class to load
 */
function __autoload($classname)
{
PHPMailerAutoload($classname);
}