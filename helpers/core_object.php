<?

	/**
	 * Core object helpers
	 */
	class Core_Object
	{
		/**
		 * Returns array representation of an object
		 * @param mixed $object object
		 * @return array Returns array
		 */
		public static function to_array($object, $parent_key = null)
		{
			$result = array();
			
			if(is_object($object))
			{
				if($var = get_object_vars($object))
					foreach($var as $key => $value)
						$result[$parent_key ? $parent_key . '_' . $key : $key] = ($key && !$value) ? strval($value) : self::to_array($value, $parent_key === null ? $key : null);
			}
			else if(is_array($object))
			{
				foreach($object as $key => $value)
					$result[$parent_key ? $parent_key . '_' . $key : $key] = self::to_array($value, $parent_key === null ? $key : null);
			}
			else
				$result = strval($object); // strval and everything is fine
			
			return $result;
		}
	
		/**
		 * Returns plain array representation of an object
		 * @param mixed $object object
		 * @return array Returns array
		 */
		public static function to_plain_array($array)
		{
			$result = array();
			
			$array = self::to_array($array, true);
			
			foreach($array as $key => $value) {
				$item = self::to_array($value, true);
				
				if(is_array($item))
					$result = array_merge($result, $item);
				else
					$result[$key] = $value;
			}

			return $result;
		}
	}