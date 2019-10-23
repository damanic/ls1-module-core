<?

	class Db_ActiverecordProxy extends Phpr_Extensible
	{
		private $model_class;
		private $fields = array();
		private $key;
		private $light_obj;
		private $heavy_obj;

		protected static $proxiable_methods = array();
		
		public function __construct($key, $model_class, $fields)
		{
			$this->key = $key;
			$this->model_class = $model_class;
			$this->fields = $fields;
		}
		
		public function __set($field, $value)
		{
			if (array_key_exists($field, $this->fields))
			{
				$this->fields[$field] = $value;
				return;
			}
			
			$this->get_object()->$field = $value;
		}
		
		public function __get($field)
		{
			if (array_key_exists($field, $this->fields))
				return $this->fields[$field];

			return $this->get_object()->$field;
		}
		
		public function __call($method, $arguments = array())
		{
			/*
			 * Try to call extension methods
			 */
			
			if (array_key_exists($method, $this->extensible_data['methods']))
				return parent::__call($method, $arguments);
			
			/*
			 * Try to call a proxiable method
			 */
			
			$proxiable_method_name = $method.'_proxiable';

			if (
				array_key_exists($this->model_class, self::$proxiable_methods) && 
				array_key_exists($method, self::$proxiable_methods[$this->model_class]) 
			)
				$proxiable = self::$proxiable_methods[$this->model_class][$method];
			else {
				$proxiable = method_exists($this->model_class, $proxiable_method_name);
				if (array_key_exists($this->model_class, self::$proxiable_methods))
					self::$proxiable_methods[$this->model_class] = array();

				self::$proxiable_methods[$this->model_class][$method] = $proxiable;
			}
				
			if ($proxiable)
			{
				array_unshift($arguments, $this);
				return call_user_func_array(array($this->model_class, $proxiable_method_name), $arguments);
			}
			
			/*
			 * Create a light model object and call its method
			 */
			
			if($this->has_proxiable_method($method) ){
				$obj = $this->get_object(true);
				return $obj->$method;
		}

			/*
			 * Create a heavy model object and call its method
			 */
			return call_user_func_array(array($this->get_object(false), $method), $arguments);
		}

		public function get_proxied_model_class(){
			return $this->model_class;
		}

		protected function has_proxiable_method($method){
			$class = $this->model_class;
			if(class_exists($class)) {
				if ( property_exists( $class, 'proxiable_methods' ) && is_array( $class::$proxiable_methods ) ) {
					$proxiable_methods = $class::$proxiable_methods;
					if(in_array($method,$proxiable_methods)){
						$proxiable = method_exists( $this->model_class, $method );
						return $proxiable;
					}
				}
			}
			return false;
		}

		protected function get_object($light=false) {
			if($light && $this->light_obj){
				return $this->light_obj;
			}
			if (!$light && $this->heavy_obj){
				return $this->heavy_obj;
			}

			$obj = new $this->model_class($this->fields);

			if($light){
				return $this->light_obj = $obj;
			}
			return $this->heavy_obj = $obj->find($this->key);
		}
	}
?>