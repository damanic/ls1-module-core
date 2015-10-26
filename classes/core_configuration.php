<?

	/**
	 * Returns general core configuration parameters.
	 */
	class Core_Configuration
	{
		public static function is_php_allowed()
		{
			return !Phpr::$config->get('CORE_DISABLE_PHP', false);
		}
	}

?>