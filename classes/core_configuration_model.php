<?

	class Core_Configuration_Model extends Db_ActiveRecord
	{
		public $table_name = 'core_configuration_records';

		public $record_code = null;
		public $is_personal = false;
		
		protected $added_fields = array();
		protected static $loaded_objects = array();
		protected $record_code_global = null;
		
		public function __construct()
		{
			$this->record_code_global = $this->record_code;
			
			if ($this->is_personal) 
			{
				$user = Phpr::$security->getUser();
				if ($user)
					$this->record_code .= '-u-'.$user->id;
			}
			
			parent::__construct();
		}
		
		public function load()
		{
			if (array_key_exists($this->record_code, self::$loaded_objects))
				return self::$loaded_objects[$this->record_code];

			$this->disable_column_cache();

			$obj = $this->find_by_record_code($this->record_code);
			if (!$obj)
			{
				if ($this->is_personal)
				{
					$class_name = get_class($this);
					$fallback = new $class_name();
					$fallback = $fallback->find_by_record_code($this->record_code_global);
					if ($fallback) 
					{
						$obj = new $class_name();
						$obj->record_code = $this->record_code;
						$obj->config_data = $fallback->config_data;
					}
				}

				if (!$obj)
				{
					$class_name = get_class($this);
					$obj = new $class_name();
				}
			}
			
			$obj->define_form_fields();
			
			self::$loaded_objects[$this->record_code] = $obj;
			
			return $obj;
		}
		
		public function define_form_fields($context = null)
		{
			$this->build_form();

			if (!$this->is_new_record())
				$this->load_xml_data();
			else
				$this->init_config_data();
		}
		
		protected function build_form()
		{
			
		}
		
		public function add_field($code, $title, $side = 'full', $type = db_text, $options = array())
		{
			$this->custom_columns[$code] = $type;
			$this->_columns_def = null;
			$column = $this->define_column($code, $title);
			if(($type==db_date || $type==db_datetime) && array_key_exists('dateFormat', $options))
				$column->dateFormat($options['dateFormat']);
			if(($type==db_datetime || $type==db_time) && array_key_exists('timeFormat', $options))
				$column->timeFormat($options['timeFormat']);
			$column->validation();

			$form_field = $this->add_form_field($code, $side);
			$this->added_fields[$code] = $form_field;
			
			return $form_field;
		}
		
		public function before_save($deferred_session_key = null)
		{
			$this->validate_config_on_save($this);
			
			$document = new SimpleXMLElement('<settings></settings>');
			foreach ($this->added_fields as $code=>$form_field)
			{
				$field_element = $document->addChild('field');
				$field_element->addChild('id', $code);
				$value_element = $field_element->addChild('value');
				
				$node = dom_import_simplexml($value_element); 
				$doc = $node->ownerDocument;
				$node->appendChild($doc->createCDATASection(serialize($this->$code)));
			}

			$this->config_data = $document->asXML();
		}

		protected function load_xml_data()
		{
			if (!strlen($this->config_data))
				return;

			$object = new SimpleXMLElement($this->config_data);
			foreach ($object->children() as $child)
			{
				$code = $child->id;
				$this->$code = unserialize($child->value);
			}
			
			$this->validate_config_on_load($this);
		}
		
		protected function validate_config_on_load()
		{
		}
		
		protected function init_config_data()
		{
		}
		
		protected function validate_config_on_save()
		{
		}
	}

?>