<?
	
	/**
	 * Provides methods for working with CSV file data
	 */
	class Core_CsvHelper
	{
		/**
		 * Parses string representation of a boolean value.
		 * @param string $value Specifies the string value.
		 * @return boolean Returns boolean value corresponding the passed value.
		 */
		public static function boolean($value)
		{
			$value = mb_strtolower($value);

			if ($value == 1 || $value == 'enabled' || $value == 'y' || $value == 'yes' || $value == 'active' || $value == 'true')
				return true;
				
			return false;
		}
	}

?>