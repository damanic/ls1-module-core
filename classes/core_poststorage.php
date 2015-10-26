<?php

	/**
	 * The POST storage class helps storing arbitrary data between POST
	 * requests.
	 */
	class Core_PostStorage
	{
		protected $values = array();
		protected $name;
		
		public function __construct($name)
		{
			$this->name = $name;
			
			$posted_values = Phpr::$request->post($name);
			if ($posted_values)
			{
				try
				{
					$this->values = unserialize(mb_convert_encoding($posted_values, 'UTF-8', 'BASE64'));
				} catch (exception $ex) {}
			}
		}
		
		public function set($name, $value)
		{
			$this->values[$name] = $value;
		}
		
		public function merge($name, $value)
		{
			$original_data = $this->get($name, array());
			if (!is_array($original_data))
				throw new Phpr_SystemException('Core_PostStorage::merge() - original data should be array.');

			$value = $this->merge_arrays($original_data, $value);
			
			$this->set($name, $value);
		}
		
		public function get($name, $default = null)
		{
			if (!array_key_exists($name, $this->values))
				return $default;
				
			return $this->values[$name];
		}

		public function create_field()
		{
			$value = mb_convert_encoding(serialize($this->values), 'BASE64', 'UTF-8');
			return '<input type="hidden" name="'.h($this->name).'" value="'.h($value).'"/>';
		}
		
		protected function merge_arrays($original, $updated)
		{
			foreach ($updated as $updated_key=>$updated_value)
			{
				if (!array_key_exists($updated_key, $original))
					$original[$updated_key] = $updated_value;
				else
					if (is_array($updated_value))
					{
						if (!is_array($original[$updated_key]))
							$original[$updated_key] = $updated_value;
						else
							$original[$updated_key] = $this->merge_arrays($original[$updated_key], $updated_value);
					}
					else
						$original[$updated_key] = $updated_value;
			}
			
			return $original;
		}
	}

?>