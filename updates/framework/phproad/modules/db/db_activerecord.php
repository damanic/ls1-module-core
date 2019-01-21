<?
	define ('db_varchar', 'varchar');
	define ('db_number', 'number');
	define ('db_float', 'float');
	define ('db_bool', 'bool');
	define ('db_datetime', 'datetime');
	define ('db_date', 'date');
	define ('db_time', 'time');
	define ('db_text', 'text');
	
	$activerecord_no_columns_info = false;

	/**
	 * Base class for LemonStand models.
	 * Db_ActiveRecord class is a base class for many other LemonStand classes, including 
	 * {@link Shop_Customer}, {@link Shop_Order}, {@link Cms_Page} and others. The class has a number of 
	 * methods which enable you to find and update records in the database.
	 *
	 * Properties of this class subclasses depend on the table fields they represent. If a table
	 * had <em>name</em> field, a corresponding model class would have public <em>$name</em> property.
	 *
	 * ActiveRecord uses the following constants for mapping data types:
	 * <ul>
	 *   <li><em>db_varchar</em> - varchar field</li>
	 *   <li><em>db_number</em> - integer field</li>
	 *   <li><em>db_float</em> - floating point number field</li>
	 *   <li><em>db_bool</em> - boolean field</li>
	 *   <li><em>db_datetime</em> - date/time field</li>
	 *   <li><em>db_date - date field</em></li>
	 *   <li><em>db_text</em> - text field</li>
	 * </ul>
	 * ActiveRecord incapsulates all methods required for finding, creating, updating and deleting 
	 * records in the database. Example of finding and updating a database record:
	 * <pre>
	 *   $obj = new Shop_Customer();
	 *   $customer = $obj->where('first_name=?', 'John')->where('last_name=?', 'Doe')->find();
	 *   $customer->last_name = 'Smith';
	 *   $customer->save();
	 * </pre>
	 *
	 * @see http://lemonstand.com/docs/working_with_the_database/ Working with the database
	 * @documentable
	 * @author LemonStand eCommerce Inc.
	 * @package core.classes
	 */ 
	class Db_ActiveRecord extends Db_SqlBase implements IteratorAggregate
	{
		const stateCreating = 'creating';
		const stateCreated = 'created';
		const stateSaving = 'saving';
		const stateSaved = 'saved';

		const opUpdated = 'updated';
		const opCreated = 'created';
		const opDeleted = 'deleted';
		
		/**
		 * Table name
		 *
		 * @var string
		 */
		public $table_name;

		/**
		 * @var string Specifies a name of the table primary key.
		 * Default primary key name is <em>id</em>. Override this
		 * property in a model if its table has primary key with
		 * another name.
		 * @documentable
		 */
		public $primary_key = 'id';

		/**
		 * Default ORDER BY
		 *
		 * @var string
		 */
		public $default_sort = '';

		/**
		 * @var array Contains a list of <em>Has One</em> relations.
		 * Please read {@link http://lemonstand.com/docs/creating_data_relations/ Creating data relations}
		 * article for details.
		 * @documentable
		 */
		public $has_one;

		/**
		 * @var array Contains a list of <em>Has Many</em> relations.
		 * Please read {@link http://lemonstand.com/docs/creating_data_relations/ Creating data relations}
		 * article for details.
		 * @documentable
		 */
		public $has_many;

		/**
		 * @var array Contains a list of <em>Has and Belongs To Many</em> relations.
		 * Please read {@link http://lemonstand.com/docs/creating_data_relations/ Creating data relations}
		 * article for details.
		 * @documentable
		 */
		public $has_and_belongs_to_many;

		/**
		 * @var array Contains a list of <em>Belongs To</em> Many relations.
		 * Please read {@link http://lemonstand.com/docs/creating_data_relations/ Creating data relations}
		 * article for details.
		 * @documentable
		 */
		public $belongs_to;
		
		protected $added_relations = array();
		
		/**
		 * @var array Contains a list of calculated columns.
		 * Use this property to define calculated columns in your models. Calculated columns do not 
		 * exist in the data table. Instead they are calculated with SQL. Example:
		 * <pre>
		 * public $calculated_columns = array( 
		 *   'comment_num'=>'select count(*) from comments where post_id=post.id',
		 *   'disk_file_name'=>array('sql'=>'files.file_create_date', 'join'=>array('files'=>'files.post_id=post.id'), 'type'=>db_date)
		 * );
		 * </pre>
		 * Defined calculated columns can be accessed as a regular class property:
		 * <pre>$comment_num = $obj->comment_num;</pre>
		 * @documentable
		 */
		public $calculated_columns = array();
		
		/**
		 * @var array Contains a list of custom fields definition.
		 * Use this field to define custom columns in your models. Custom columns do not exist in the database
		 * and their values a are evaluated with special model methods when the model is loaded from the database. 
		 * Each custom column should have a corresponding method in the model class.
		 * Example:
		 * <pre>
		 * public $custom_columns = array('record_status'=>db_number, 'record_css_class'=>db_text);
		 * </pre>
		 * This example the model to have two public methods for evaluating the column values:
		 * <pre>
		 * public function eval_record_status() 
		 * {
		 *   return 1;
		 * }
		 * 
		 * public function eval_record_css_class() 
		 * {
		 *   return $this->deleted ? 'deleted' : 'normal';
		 * }
		 * </pre>
		 * Defined custom fields can be accessed as a regular class property:
		 * <pre>$record_status = $obj->record_status;</pre>
		 * @documentable
		 */
		public $custom_columns = array();
		
		/*
		 * A list of columns to encrypt
		 */
		public $encrypted_columns = array();
		
		/*
		 * Caching
		 */
		
		/**
		 * Enable cross-instance simple caching by the identifier
		 *
		 * @var bool
		 */
		public $simpleCaching = false; 
		
		protected static $simpleCache = array();
		
		protected $className;

		/**
		 * Strict mode, fill only defined properties
		 *
		 * @var bool
		 */
		public $strict = false;

		/**
		 * @var array A list of create timestamp columns.
		 * Values of columns specified in this list are automatically
		 * set when the new model object is saved to the database
		 * By default <em>created_at</em> and <em>created_on</em> columns
		 * are considered as create timestamp columns.
		 *
		 * Setting the create timestamp column values takes place only if
		 * {@link Db_ActiveRecord::auto_timestamps auto_timestamps} property value is TRUE.
		 *
		 * @documentable
		 */
		public $auto_create_timestamps = array("created_at", "created_on");

		/**
		 * @var array A list of update timestamp columns.
		 * Values of columns specified in this list are automatically
		 * set when an existing model object is saved to the database
		 * By default <em>updated_at</em> and <em>updated_on</em> columns
		 * are considered as update timestamp columns.
		 *
		 * Setting the update timestamp column values takes place only if
		 * {@link Db_ActiveRecord::auto_timestamps auto_timestamps} property value is TRUE.
		 *
		 * @documentable
		 */
		public $auto_update_timestamps = array("updated_at", "updated_on");

		/**
		 * @var boolean Determines whether create and update timestamp field should be set automatically.
		 * By default columns specified in {@link Db_ActiveRecord::auto_create_timestamps auto_create_timestamps} and 
		 * {@link Db_ActiveRecord::auto_update_timestamps auto_update_timestamps} properties are set automatically when the model 
		 * is saved. Assign FALSE value to this property to cancel this behavior.
		 * @documentable
		 */
		public $auto_timestamps = true;

		/**
		 * List of datetime fields
		 *
		 * @var string[]
		 */
		protected $datetime_fields = array();
		
		/**
		 * Whether to automatically update created_user_id and updated_user_id columns
		 */
		protected $auto_footprints = true;
	
		/**
		 * New record flag
		 *
		 * @var boolean
		 */
		protected $new_record = true;

		/**
		 * SQL aggregate functions that may be applied to the associated table.
		 * 
		 * SQL defines aggregate functions AVG, COUNT, MAX, MIN and SUM.
		 * Not all of these functions are implemented by all DBMS's
		 *
		 * @var string[]
		 */
		protected $aggregations = array("count", "sum", "avg", "max", "min");
	
		/**
		 * Cache DESCRIBE in session
		 *
		 * @var boolean
		 */
		public static $cache_describe = true;
	
		protected static $describe_cache = array();

		/**
		 * Serialize associations
		 *
		 * @var boolean
		 */
		public $serialize_associations = false;

		/**
		 * @var array Contains an associative array of table column values fetched from the database.
		 * You can use this field to compare original values with new values before the model is saved
		 * to the database.
		 * @documentable 
		 */
		public $fetched = array();

		public $has_models = array();
	
		protected $changed_relations = array();
	
		protected $calc_rows = false;
		
		/**
		 * Use the legacy pagination mechanism (manual limiting the row count)
		 * @var boolean
		 */
		protected $legacy_pagination = true;
	
		public $found_rows = 0;
	
		private $__locked = false;
		
		protected $modelState;
		
		public $objectId;
		
		public $_columns_def = null;
		
		/**
		 * A list of extensions
		 */
		
		public $implement = '';
		
		protected static $column_cache_disabled = array();
		protected $form_field_columns_initialized = false;
	
		/**
		 * Visual representation and validation - column definition feature
		 */
		protected $column_definitions = array();
		protected static $cached_column_definitions = array();
		public $form_elements = array();
		public $formTabIds = array();
		public $formTabVisibility = array();
		public $formTabCssClasses = array();
		
		/**
		 * @var Phpr_Validation Contains the model's validation object.
		 * This model uses this method during the validation process.
		 * @documentable
		 */
		public $validation;
		
		protected $columns_loaded = false;
		protected static $relations_cache = array();
		protected $fields_cache = null;
		protected $column_definition_context = null;
		public static $execution_context = null;

		protected $defined_column_list = array();
		
		public static $object_counter = 0;
		
		/**
		 * Memory management 
		 */
		
		public $model_options = array();
		
		/*
		 * Specifies a class name of a controller responsible for rendering forms and lists of models of this class.
		 */
		public $native_controller = null;
		
		public function __construct($values = null, $options = array())
		{
			$this->modelState = self::stateCreating;
			$this->model_options = $options;

			$this->implement = Phpr_Util::splat($this->implement, true);
			array_unshift($this->implement, 'Phpr_Events');
			parent::__construct();

			$this->initialize();
			self::$object_counter++;
			$this->objectId = 'ac_obj_'.self::$object_counter;
			
			if (!$this->get_model_option('no_validation'))
			{
				$this->validation = new Phpr_Validation($this);
				$this->validation->focusPrefix = get_class($this)."_";
			}

			// Fill with data
			if ($values !== null) 
			{
				$this->fill($values);
				$this->fill_relations($values);
			}

			$this->modelState = self::stateCreated;
//			$this->define_columns();
		}
		
		public function __destruct() 
		{
			foreach ($this->column_definitions as $id=>$def)
				unset($this->column_definitions[$id]);

			foreach ($this->form_elements as $id=>$def)
				unset($this->form_elements[$id]);

			foreach ($this->formTabIds as $id=>$def)
				unset($this->formTabIds[$id]);

			unset($this->validation);
		}
	
		protected function initialize()
		{
			$this->datetime_fields = array_merge(
				$this->datetime_fields,
				$this->auto_create_timestamps,
				$this->auto_update_timestamps
			);

			if (!isset($this->table_name))
				$this->table_name = Phpr_Inflector::tableize(get_class($this));
		
			$fields = array_keys($this->fields());
			if ($this->auto_timestamps && !$this->get_model_option('no_timestamps'))
			{
				foreach($this->auto_create_timestamps as $field)
				{
					if (in_array($field, $fields))
						$this->{$field} = Phpr_DateTime::now();
				}
			}

			$this->className = get_class($this);

			$this->load_relations();
		}
		
		protected function get_model_option($name, $default = null)
		{
			return array_key_exists($name, $this->model_options) ? $this->model_options[$name] : $default;
		}

		/* Find methods */

		protected function _find_fill($data, $form_context = null)
		{
			if ($this->calc_rows)
				$this->found_rows = Db::sql()->fetchOne('SELECT FOUND_ROWS()');

			$class_name = get_class($this);
			$result = new Db_DataCollection();
			$result->parent = $this;
			foreach($data as $row) 
			{
				$result[] = $o = new $class_name(null, $this->model_options);
				
				foreach ($this->added_relations as $relation_info)
					$o->add_relation($relation_info[0], $relation_info[1], $relation_info[2]);

				$o->before_fetch($row);
				$o->fill($row, true, $form_context);
				$o->new_record = false;
				$o->after_fetch();
			}

			if ($this->legacy_pagination && $this->get_limit() > 1)
				return $result;

			if ($this->get_limit() == 1)
				return $result[0];
			else
				return $result;
		}
	
		/**
		 * Finds a record by its primary key value.
		 * Note that if the primary key value passed to the method is NULL,
		 * the method returns the first record from the result set.
		 * @documentable
		 * @param integer $id Specifies the primary key value.
		 * @param array $include This parameter is reserved.
		 * @param string $form_context Allows to set the form execution context for the loaded records.
		 * @return Db_ActiveRecord Returns a model object corresponding to the found record or NULL.
		 */
		public function find($id = null, $include = array(), $form_context = null)
		{
			//if (!Cms_Controller::get_instance())
				$this->init_columns_info($form_context);
//			$this->column_definition_context = $form_context;
			
			$this->limit(1);
			$this->calc_rows = false;
			if (is_array($id))
				$id = array_shift($id);

			return $this->find_all_internal($id, $include, $form_context);
		}

		protected function find_all_internal($id = null, $include = array(), $form_context = null)
		{
			//if (!Cms_Controller::get_instance())
				$this->init_columns_info($form_context);
//			$this->column_definition_context = $form_context;

			$cachingCase = false;

			if ($id instanceof WhereBase)
				$this->where($id);
			elseif (is_array($id))
				$this->where($this->primary_key . ' IN (?)', $id);
			elseif ($id !== null)
			{
				if ($this->get_limit() == 1 && $this->simpleCaching)
				{
					$cachingCase = true;
					
					if ( ($obj = self::load_cached($this->className, $this->primary_key, $id)) !== -1 )
						return $obj;
				}
					
				$this->where($this->primary_key . ' = ?', $id);
			}

			if (!$this->has_order() && (trim($this->default_sort) != ''))
			{
				$prefix = '';
				if (strpos($this->default_sort, '.') === false && strpos($this->default_sort, ',') === false)
					$prefix = $this->table_name . '.';

				$this->order($prefix . $this->default_sort);
			}

			$this->applyCalculatedColumns();
			// TODO: handle $include (eager associations)
	
			$data = $this->fetchAll($this->build_sql());
			$result = $this->_find_fill($data, $form_context);

			if ($cachingCase)
				self::cache_instance($this->className, $this->primary_key, $id, $result);
				
			return $result;
		}
		
		/**
		 * Finds all records and returns the {@link Db_DataCollection} object, containing a list of models.
		 * @documentable
		 * @see Db_DataCollection
		 * @param integer $id Specifies the primary key value.
		 * @param array $include This parameter is reserved.
		 * @param string $form_context Allows to set the form execution context for the loaded records.
		 * @return Db_DataCollection Returns a collection of found records.
		 */
		public function find_all($id = null, $include = array(), $form_context = null)
		{
			$result = $this->find_all_internal($id, $include, $form_context);

			if ($result instanceof Db_ActiveRecord)
			 	$result = new Db_DataCollection(array($result));
			else if (!$result)
			 	$result = new Db_DataCollection();

			return $result;
		}

		public function applyCalculatedColumns()
		{
			if (count($this->calculated_columns))
			{
				foreach ($this->calculated_columns as $name=>$definition)
				{
					if (is_string($definition))
						$this->addColumn('('.$definition.') as '.$name );
					elseif (is_array($definition))
					{
						if (!isset($definition['sql']))
							throw new Phpr_SystemException('Invalid calculated column definition - no SQL clause for '.$name.' column in class '.$this->className);

						if (isset($definition['join']))
						{
							foreach ($definition['join'] as $table=>$conditions)
								$this->join($table, $conditions);
						}

						$this->addColumn('('.$definition['sql'].') as '.$name);
					}
				}
			}
		}

		public function find_by_sql($sql, $include = array()) 
		{
			if ($sql instanceof Db_SqlBase)
				$sql = $sql->build_sql();
		
			// TODO: handle $include (eager associations)
		
			$data = $this->fetchAll($sql);
			return $this->_find_fill($data);
		}

		/**
		 * Finds a record by a field value.
		 * There is a more usable magic method <em>find_by_[column_name]</em>, where column_name is 
		 * any database column name. Example:
		 * <pre>$usa = Shop_Country::create()->find_by_code('US');</pre>
		 * @documentable
		 * @param string $field Specifies the database field name.
		 * @param mixed $value Specifies the field value to find.
		 * @param array $include This parameter is reserved.
		 * @return Db_ActiveRecord Returns the model object corresponding to the found record or NULL.
		 */
		public function find_by($field, $value, $include = array()) 
		{
			$this->limit(1);
			$this->calc_rows = false;
			return $this->find_all_by($field, $value, $include);
		}

		public function find_all_by($field, $value, $include = array()) 
		{
			$this->where($field . ' = ?', $value);
			return $this->find_all_internal(null, $include);
		}

		public function find_related($relation, $params = null) 
		{
			return $this->load_relation($relation, $params);
		}
		
		/**
		 * Returns a list of {@link http://lemonstand.com/docs/creating_data_relations/ relation} objects, taking into account deferred relations.
		 * Use this method to obtain a list of relation records, before the record is saved to the database.
		 * @documentable
		 * @param string $name Specifies the relation name.
		 * @param string $deferred_session_key Specifies deferred session key.
		 * @return Db_DataCollection Returns the data collection object.
		 */
		public function list_related_records_deferred($name, $deferred_session_key)
		{
			$object = $this->get_related_records_deferred_obj($name, $deferred_session_key);

			$data = $object->find_all_internal();
			$data->relation = $name;
			$data->parent = $this;
			return $data;
		}
		
		public function get_related_records_deferred_obj($name, $deferred_session_key)
		{
			if (!isset($this->has_models[$name])) 
				throw new Phpr_SystemException("Relation $name is not found in the model ".$this->className);

			$type = $this->has_models[$name];
			if ($type != 'has_many' && $type != 'has_and_belongs_to_many')
				throw new Phpr_SystemException('list_related_records_deferred supports only has_many and has_and_belongs_to_many relations');

			$has_primary_key = false;
			$has_foreign_key = false;
			$options = $this->get_relation_options($type, $name, $has_primary_key, $has_foreign_key);

			$object = new $options['class_name']();
			if (is_null($options['order']) && ($object->default_sort != ''))
				$options['order'] = $object->default_sort;

			if (!$has_foreign_key)
				$options['foreign_key'] = Phpr_Inflector::foreign_key($this->table_name, $object->primary_key);

			$foreignKey = $options['foreign_key'];

			$deffered_where = "(exists 
				(select * from db_deferred_bindings where 
					detail_key_value={$object->table_name}.{$object->primary_key} 
					and master_relation_name=:relation_name and master_class_name='{$this->className}' 
					and is_bind=1 and session_key=:session_key))";
					
			$deffered_deletion_where = "(exists 
					(select * from db_deferred_bindings where 
						detail_key_value={$object->table_name}.{$object->primary_key} 
						and master_relation_name=:relation_name and master_class_name='{$this->className}' 
						and is_bind=0 and session_key=:session_key
						and id > ifnull((select max(id) from db_deferred_bindings where 
							detail_key_value={$object->table_name}.{$object->primary_key} 
							and master_relation_name=:relation_name and master_class_name='{$this->className}' 
							and is_bind=1 and session_key=:session_key), 0)
						))";

			$bind = array(
				'foreign_key'=>$this->{$options['primary_key']}, 
				'bind'=>1,
				'session_key'=>$deferred_session_key,
				'relation_name'=>$name);

			if ( !$this->is_new_record() )
			{
				if ($type == 'has_many')
					$object->where("({$object->table_name}.{$foreignKey}=:foreign_key) or ($deffered_where)", $bind);
				else 
				{
					$this_key = $this->get_primary_key_value();
					$existing_m2m_records = "(exists (select * from {$options['join_table']} where {$options['primary_key']}='{$this_key}' and {$options['foreign_key']}={$object->table_name}.{$object->primary_key}))";

					$object->where("({$existing_m2m_records}) or ($deffered_where)", $bind);
				}
			}
			else
				$object->where("($deffered_where)", $bind);
			
			$object->where("(not ($deffered_deletion_where))", $bind);
			$object->where($options['conditions']);
			
			if (strlen($options['order']))
				$object->order($options['order']);

			return $object;
		}
		
		
		/**
		 * Returns a column value, taking into account possible deferred bindings. 
		 * This method is used by the validation framework.
		 */
		public function getDeferredValue($column, $deferred_session_key)
		{
			if (isset($this->has_models[$column]) && $this->has_models[$column] == 'has_many') 
				return $this->list_related_records_deferred($column, $deferred_session_key);
				
			return $this->$column;
		}
		
		/**
		 * Sets a column value, taking into account possible deferred bindings.
		 * This method is used by the validation framework.
		 */
		public function setDeferredValue($column, $value, $deferred_session_key)
		{
			if (isset($this->has_models[$column])) 
				return;
				
			return $this->$column = $value;
		}
	
		/* Save methods */
	
		public function update($values) 
		{
			$this->before_fill($values);
			$this->fill($values);
			$this->fill_relations($values);
			return $this;
		}

		/**
		 * Performs data validation. Do not use this method
		 * if you are going to save the model, because save() method
		 * performs validation before saving data.
		 * @param mixed[] $values
		 * @return Db_ActiveRecord
		 */
		public function validate_data($values, $deferred_session_key = null)
		{
			$this->modelState = self::stateSaving;
			$this->update($values)->valid($deferred_session_key); 
			$this->modelState = self::stateSaved;
			return $this;
		}

		/**
		 * Set the record field values. Do not use this method
		 * if you are going to save the model, because save() method
		 * performs validation before saving data.
		 * @param mixed[] $values
		 * @return Db_ActiveRecord
		 */
		public function set_data($values)
		{
			$this->modelState = self::stateSaving;
			$this->update($values); 
			$this->modelState = self::stateSaved;
			return $this;
		}
	
		/**
		 * Saves record to the database.
		 * Depending on whether the model was created or loaded from the database the method
		 * creates or updates a corresponding table record.
		 * The optional $values array can be used to set the record field values.
		 * <pre>
		 * $customer = Shop_Customer::create()->find(23);
		 * $customer->save(array('first_name'=>'john'));
		 * </pre>
		 * Alternatively you can set the column values directly:
		 * <pre>
		 * $customer = Shop_Customer::create()->find(23);
		 * $customer->first_name = 'john';
		 * $customer->save();
		 * </pre>
		 * @documentable
		 * @param array $values
		 * @param string $deferred_session_key Optional deferred session key.
		 * @return Db_ActiveRecord Returns the saved model object
		 */
		public function save($values = null, $deferred_session_key = null) 
		{
			$this->modelState = self::stateSaving;

			if ($values !== null) 
			{
				$this->before_fill($values);
				$this->fill($values);
				$this->fill_relations($values);
			}

			if (!$this->valid($deferred_session_key)) 
				return false;

			$this->before_save($deferred_session_key);

			if ($this->new_record)
				$this->before_create($deferred_session_key);
			else
				$this->before_update($deferred_session_key);

			// Fill record to save
			$record = array();
			$fields = array_keys($this->fields());
			$dataUpdated = false;
			$newRecord = $this->new_record;
			$reflection = new ReflectionObject($this);
			
			foreach($reflection->getProperties() as $property) 
			{
				if (!in_array($property->name, $fields)) 
					continue;

				$val = $property->getValue($this);
			
				// convert datetime
				if (in_array($property->name, $this->datetime_fields))
					$val = $this->type_cast_date($val, $property->name);
					
				// encrypt
				if (in_array($property->name, $this->encrypted_columns))
					$val = base64_encode(Phpr_SecurityFramework::create()->encrypt($val));

				// Set value
				$record[$property->name] = $val;
			}

			if ($this->new_record) 
			{
				if (isset($record[$this->primary_key]) && ($record[$this->primary_key] === 0))
					unset($record[$this->primary_key]);
					
				$this->create_footprints($record);

				$this->sql_insert($this->table_name, $record);
				$key = $this->primary_key;
				$this->$key = $this->last_insert_id($this->table_name, $this->primary_key);
				$this->new_record = false;
				$this->after_create();
			} else {
				if (!isset($record[$this->primary_key]))
					throw new Phpr_SystemException('Primary key can not be null: '.$this->table_name);

				$key = $this->primary_key;
				if (isset($record[$this->primary_key]) && ($record[$this->primary_key] === 0))
					unset($record[$this->primary_key]);

				$this->unset_unchanged($record);

				if (count($record) > 0 || count($this->changed_relations))
				{
					$this->update_footprints($record);
					if (count($record) > 0)
						$this->sql_update($this->table_name, $record, Db::where($this->primary_key . ' = ?', $this->{$key}));
					$dataUpdated = true;
				}

				$this->after_update();
			}
			
			$relationsUpdated = $this->apply_relations_changes($deferred_session_key);

			if ($newRecord)
			{
				$this->fireEvent('onAfterCreate');
				$this->after_create_saved();
			}
			elseif ($relationsUpdated || $dataUpdated)
				$this->fireEvent('onAfterUpdate');
		
			$this->after_save();
			
			if ($newRecord)
				$this->after_modify(self::opCreated, $deferred_session_key);
			else
				$this->after_modify(self::opUpdated, $deferred_session_key);
			
			$this->modelState = self::stateSaved;

			return $this;
		}
				
		/**
		 * Duplicates a record, but not saves it.
		 * Doesn't duplicate any relations.
		 * @return mixed Returns the new object.
		 */
		public function duplicate()
		{
			$obj = clone $this;
			
			$primary_key = $this->primary_key;
			
			$obj->new_record = true;
			$obj->$primary_key = null;
			
			return $obj;
		}
	
		 /* Delete methods */
	
		/**
		 * Deletes the record from the database.
		 * @documentable
		 * @param integer $id Specifies the record identifier.
		 * If the identifier is not provided, deletes the current record.
		 */
		public function delete($id = null) 
		{
			if (is_null($id))
				$id = $this->{$this->primary_key};

			$this->before_delete($id);
			$this->delete_all(Db::where($this->primary_key . ' IN (?)', Phpr_Util::splat($id)));
			$this->after_delete();
			
			$this->after_modify(self::opDeleted, null);
			
			$this->fireEvent('onAfterDelete');
		}

		/**
		 * Deletes all the records that match the condition.
		 *
		 * @param string|WhereBase $conditions
		 */
		public function delete_all($conditions = null) 
		{
			global $activerecord_no_columns_info;
			$prev_no_columns_info_value = $activerecord_no_columns_info;
			$activerecord_no_columns_info = true;

			// Delete related
			foreach($this->has_models as $name => $type)
			{
				$relation_info = $this->{$type}[$name];
				if (!is_array($relation_info) || !isset($relation_info['delete']) || !$relation_info['delete']) 
					continue;
					
				switch ($type) 
				{
					case 'has_one':
						$related = $this->{$name};
						if (isset($related))
							$related->delete();
					break;
					case 'has_many':
						$related = $this->{$name};
						foreach($related as $item) 
							$item->delete();
					break;
					case 'has_and_belongs_to_many':
						if (!is_array($relation_info)) 
						{
							$relation_info = array(
							'class_name' => Phpr_Inflector::classify($relation_info)
							);
						} elseif (!isset($relation_info['class_name']))
							$relation_info['class_name'] = Phpr_Inflector::classify($name);

						// Create model
						$object = new $relation_info['class_name']();
						if (is_null($object))
							throw new Phpr_SystemException('Class not found: '.$relation_info['class_name']);

						$options = array_merge(array(
							'join_table' => $this->get_join_table_name($this->table_name, $object->table_name),
							'primary_key' => Phpr_Inflector::foreign_key($this->table_name, $this->primary_key),
							'foreign_key' => Phpr_Inflector::foreign_key($object->table_name, $object->primary_key)
							), Phpr_Util::splat($relation_info));

						DB::select()->sql_delete($options['join_table'], DB::where($options['join_table'] . '.' . $options['primary_key'] . ' = ?', $this->{$this->primary_key}));
						break;
				}

			}
				
			$this->sql_delete($this->table_name, $conditions);
			
			$activerecord_no_columns_info = $prev_no_columns_info_value;
		}

		/* Data processing routines */
	
		public function fill($row, $save_fetched = false, $form_context = null) 
		{
			//if (!Cms_Controller::get_instance())
			$this->init_columns_info($form_context);
//			$this->column_definition_context = $form_context;
			
			if ($row === null) return;

			// fill model with record
			if ($save_fetched)
				$this->fetched = array();

			foreach($row as $name => $val) 
			{
				if ($this->strict && !isset($this->{$name})) 
					continue;
					
				if (array_key_exists($name, $this->has_models))
				 	continue;

				if ($this->modelState != self::stateSaving)
				{
					if (in_array($name, $this->encrypted_columns) && strlen($val))
					{
						try
						{
							$val = Phpr_SecurityFramework::create()->decrypt(base64_decode($val));
						} catch (Exception $ex)
						{
							$val = null;
						}
					}
				}

				// Store unchanged values
				if ($save_fetched)
					$this->fetched[$name] = $this->type_cast_field($name, $val);

				// typecasting
				$val = $this->type_cast_field($name, $val);
//				if (!is_null($val))
					$this->{$name} = $val;
			}

			if ($save_fetched)
				$this->fireEvent('onAfterLoad');
		}
		
		public function fill_external($row, $form_context = null)
		{
			$this->before_fetch($row);
			$this->fill($row, true, $form_context);
			$this->new_record = false;
			$this->after_fetch();
		}
		
		public function eval_custom_columns()
		{
			foreach ($this->custom_columns as $column=>$type)
			{
				$methodName = 'eval_'.$column;
				if (method_exists($this, $methodName))
					$this->{$column} = $this->$methodName();
			}
		}
	
		protected function fill_relations($values)
		{
			foreach($values as $name => $value) 
			{
				if (!array_key_exists($name, $this->has_models)) 
					continue;
					
				$this->__set($name, $value);
			}
		}
	
		/* Typecasting */
	
		protected function type_cast_field($field, $value) 
		{
			$field_info = $this->field($field);
			if (!isset($field_info['type']))
			{
				if (array_key_exists($field, $this->calculated_columns) && isset($this->calculated_columns[$field]['type']))
					$field_info = array('type'=>$this->calculated_columns[$field]['type']);
			}

			// convert datetime
			// if (in_array($field, $this->datetime_fields) ||
			// 		(isset($field_info['type']) && in_array($field_info['type'], array('datetime', 'date', 'time')))
			// 	) 
			if ((isset($field_info['type']) && ($field_info['type'] == 'datetime' || $field_info['type'] == 'date' || $field_info['type'] == 'time'))) 
			{
				$value = $this->type_cast_date($value, $field);
			} 
			elseif (isset($field_info['type'])) 
			{
				switch($field_info['type']) 
				{
					case 'decimal':
					case 'int':
					//case 'tinyint':
					case 'smallint':
					case 'mediumint':
					case 'bigint':
					case 'double':
					case 'float':
						$value = $value;
						break;
					case 'bool':
					case 'tinyint':
						$value = $value;
						break;
					case 'datetime':
					case 'date':
					case 'time':
						$value = $this->type_cast_date($value);

						break;
				}
			}

			return $value;
		}
		
		protected function type_cast_date($value, $field)
		{
			$isObject = is_object($value);
			
			if (!$isObject)
			{
				$len = strlen($value);
				if (!$len)
					return null;
				if ($len <= 10)
					$value .= ' 00:00:00';

				/*
				 * Do not convert dates to object during saving for validatable fields. The Validation object
				 * will process dates instead of model.
				 */
				if ($this->modelState == self::stateSaving && $this->validation->hasRuleFor($field))
					return $value;

				return new Phpr_Datetime($value);
			}
			elseif ($value instanceof Phpr_DateTime) 
				return $value->toSqlDateTime();
				
			return null;
		}
	
		/* Triggers */

		/**
		 * Is called before fill() && fill_relations() on existing objects that has a record
		 */
		public function before_fill(&$new_values) 
		{
		}

		/**
		 * Called before a new object is saved to the database.
		 * Override this method in your model classes if you need to perform
		 * any operations before the record is created in the database.
		 * @documentable
		 * @param string $deferred_session_key Specifies the deferred session key passed to the {@link Db_ActiveRecord::save() save()} method.
		 */
		public function before_create($deferred_session_key = null) 
		{
		}

		/**
		 * Called after a new object is saved to the database.
		 * Override this method in your model classes if you need to perform
		 * any operations after the record is created in the database.
		 * When this method is called, object relations are not saved to the database yet.
		 * @documentable
		 */
		public function after_create() 
		{
		}
		
		/**
		 * Called after a new object is saved to the database.
		 * Override this method in your model classes if you need to perform
		 * any operations after the record is created in the database.
		 * When this method is called, object relations are already saved to the database.
		 * @documentable
		 */
		public function after_create_saved()
		{
		}

		/**
		 * Called before an existing record is updated in the database.
		 * Override this method in your model classes if you need to perform
		 * any operations before the record is updated in the database.
		 * @documentable
		 * @param string $deferred_session_key Specifies the deferred session key passed to the {@link Db_ActiveRecord::save() save()} method.
		 */
		public function before_update($session_key = null) 
		{
		}

		/**
		 * Called after an existing record is updated in the database.
		 * Override this method in your model classes if you need to perform
		 * any operations after the record is updated in the database.
		 * @documentable
		 */
		public function after_update() 
		{
		}

		/**
		 * Called before an existing or new record is created or updated in the database.
		 * Override this method in your model classes if you need to perform
		 * any operations before the record is saved to the database.
		 * @documentable
		 * @param string $deferred_session_key Specifies the deferred session key passed to the {@link Db_ActiveRecord::save() save()} method.
		 */
		public function before_save($deferred_session_key = null) 
		{
		}

		/**
		 * Called after an existing or new record is created or updated in the database.
		 * Override this method in your model classes if you need to perform
		 * any operations after the record is saved to the database.
		 * @documentable
		 */
		public function after_save()
		{
		}

		/**
		 * Called before a record is deleted from the database.
		 * Override this method in your model classes if you need to perform
		 * any operations before the record is deleted from the database. Usually
		 * this method is used for checking whether the object can be deleted.
		 * You can throw an exception in the handler to stop the operation. The following
		 * example demonstrates a typical usage of the method:
		 * <pre>
		 * public function before_delete($id=null) 
		 * {
		 *   if ($order_num = $this->orders->count) // Load the number of customer orders from the relation
		 *     throw new Phpr_ApplicationException("Error deleting customer. There are $order_num order(s) 
		 *       belonging to this customer.");
		 * }
		 * </pre>
		 * @documentable
		 * @param integer $id Specifies the record primary key value.
		 */
		public function before_delete($id=null) 
		{
		}
		
		/**
		 * Called after an existing or new record is deleted from the database.
		 * Override this method in your model classes if you need to perform
		 * any operations after the record is deleted from the database.
		 * @documentable
		 */
		public function after_delete()
		{
		}
		
		/**
		 * Called after an existing or new record was modified.
		 * Override this method in your model classes if you need to perform
		 * any operations after the record is modified.
		 * @documentable
		 * @param string $operation Specifies the operation type.
		 * Possible values are: <em>created</em>, <em>updated</em>, <em>deleted</em>
		 * @param string $deferred_session_key Specifies the deferred session key passed to the {@link Db_ActiveRecord::save() save()} method.
		 */
		public function after_modify($operation, $deferred_session_key)
		{
		}

		/**
		 * Is called before fetch row(s) from database
		 */
		public function before_fetch($data)
		{
		}

		/**
		 * Is called after a has-many relation item has been bound to the model
		 */
		public function after_has_many_bind($obj, $relation_name)
		{
		}

		/**
		 * Is called after a has-many relation item has been unbound from the model
		 */
		public function after_has_many_unbind($obj, $relation_name)
		{
		}
		
		/**
		 * Is called after fetch() on existing objects that has a record
		 */
		protected function after_fetch()
		{
		}

		/* Service methods */

		protected function field($name)
		{
			$fields = $this->fields();
			return isset($fields[$name]) ? $fields[$name] : array();
		}
	
		public function fields() 
		{
			if ($this->fields_cache)
				return $this->fields_cache;

			if (isset(self::$describe_cache[$this->table_name])) 
				return self::$describe_cache[$this->table_name];

			if (self::$cache_describe && Phpr::$config->get('ALLOW_DB_DESCRIBE_CACHE')) 
			{
				$cache = Core_CacheBase::create();
				
				$descriptions = $cache->get('phpr_table_descriptions');
				if (!$descriptions || !is_array($descriptions))
					$descriptions = array();

				try
				{
					if (is_array($descriptions) && array_key_exists($this->table_name, $descriptions))
						return self::$describe_cache[$this->table_name] = $descriptions[$this->table_name];
				} catch (exception $ex)
				{}

				// DESCRIBE and save cache
				$describe = $this->describe_table($this->table_name);
				self::$describe_cache[$this->table_name] = $describe;

				$descriptions[$this->table_name] = $describe;
				$cache->set('phpr_table_descriptions', $descriptions);
				return $describe;
			}

			return $this->fields_cache = self::$describe_cache[$this->table_name] = $this->describe_table($this->table_name);
		}
		
		public static function clear_describe_cache()
		{
			Phpr::$session->set('phpr_table_descriptions', array());
			
			if (Phpr::$config->get('ALLOW_DB_DESCRIBE_CACHE'))
			{
				$cache = Core_CacheBase::create();
				$cache->set('phpr_table_descriptions', false);
			}
		}
		
		protected function create_footprints(&$new_values)
		{
			if ($this->auto_footprints && $this->field('created_user_id'))
			{
				$user = Phpr::$security->getUser();
				if ($user)
					$new_values['created_user_id'] = $this->created_user_id = $user->id;
			}
		}
		
		protected function update_footprints(&$new_values)
		{
			// set $auto_update_timestamps
			if ($this->auto_timestamps)
			{
				$fields = array_keys($this->fields());

				foreach($this->auto_update_timestamps as $field) 
				{
					if (in_array($field, $fields))
						$new_values[$field] = $this->{$field} = Phpr_DateTime::now();
				}
			}

			// update updated_user_id column
			if ($this->auto_footprints && !($this instanceof Phpr_User) && $this->field('updated_user_id'))
			{

				$user = Phpr::$security->getUser();
				if ($user)
					$new_values['updated_user_id'] = $this->updated_user_id = $user->id;
			}
		}
		
		protected function unset_unchanged(&$new_values)
		{
			// Unset unmodified fields
			foreach($this->fetched as $key => $value) 
			{
				if (array_key_exists($key, $new_values))
				{
					$equal = false;
					
					$new_value = $new_values[$key];
					
					if (is_object($value) && $value instanceof Phpr_DateTime && !is_object($new_value))
						$new_value = $this->type_cast_date($new_value, $key);

					if (is_object($value) && is_object($new_value))
					{
						if ($value instanceof Phpr_DateTime && $new_value instanceof Phpr_DateTime)
							$equal = $value->equals($new_value);
					}
					else
					{
						$equal = (string)$new_value === (string)$value;
					}
					
					if ($equal)
						unset($new_values[$key]);
				}
			}
		}

		public function has_column($field) 
		{
			return ($this->field($field) !== array());
		}
	
		public function column($field) 
		{
			$columns = $this->columns();
			$column = $columns->find($field, 'name');

			if (isset($column))
				return $column;
			else
				return null;
		}
	
		public function columns() 
		{
			if (isset($this->_columns_def))
				return $this->_columns_def;
				
			$columns = array();
			$fields = $this->fields();

			foreach($fields as $info) 
				$columns[] = new Db_ActiveRecordColumn($info);
				
			foreach($this->calculated_columns as $name=>$data)
			{
				$type = (is_array($data) && isset($data['type'])) ? $data['type'] : db_text;
				$info = array('calculated'=>true, 'name'=>$name, 'type'=> $type);

				$columns[] = new Db_ActiveRecordColumn($info);
			}

			foreach($this->custom_columns as $name=>$type)
			{
				$info = array('custom'=>true, 'name'=>$name, 'type'=> $type);
				$columns[] = new Db_ActiveRecordColumn($info);
			}

			return $this->_columns_def = new Db_DataCollection($columns);
		}
		
		public function get_primary_key_value()
		{
			return $this->{$this->primary_key};
		}

		public function is_new_record()
		{
			return $this->new_record;
		}
		
		public function set_new_record()
		{
			$this->{$this->primary_key} = 0;
			$this->new_record = true;
		}

		/* Internal methods */
	
		public function build_sql() 
		{
			if (count($this->parts['from']) == 0)
				$this->from($this->table_name);

			if ($this->calc_rows)
				$this->use_calc_rows();

			return parent::build_sql();
		}

		/**
		 * Allows to limit the result of the {@link Db_ActiveRecord::find_all() find_all()} method with the specified number of records.
		 * The following example loads 10 newest orders from the database.
		 * <pre>$orders = Shop_Order::create()->order('id desc')->limit(10);</pre>
		 * @documentable
		 * @param integer $count Number of records to return.
		 * @param integer $offset Zero-based offset of the first returned record.
		 * @return Db_ActiveRecord Returns the model object.
		 */
		public function limit($count = null, $offset = null) 
		{
			if (!$this->legacy_pagination)
				$this->calc_rows = true;
				
			return parent::limit($count, $offset);
		}

		public function limitPage($page, $rowCount) 
		{
			$this->calc_rows = true;
			return parent::limitPage($page, $rowCount);
		}

		/**
		 * Returns a number of rows which would be returned with {@link Db_ActiveRecord::find_all() find_all()} method.
		 * Use this method to obtain the number of records which is going to be returned by a subsequent {@link Db_ActiveRecord::find_all() find_all()} 
		 * method call. 
		 * @documentable
		 * @return integer Returns the number of rows.
		 */
		public function requestRowCount()
		{
			$obj = clone $this;
			self::$object_counter++;
			$obj->objectId = 'ac_obj_'.self::$object_counter;

			$obj->init_columns_info(null, true);
			$obj->applyCalculatedColumns();

			if (count($obj->parts['from']) == 0)
				$obj->from($obj->table_name);

			$obj->parts['order'] = array();

			if (!$obj->has_group())
			{
				$obj->parts['fields'] = array('count(*)');
				$sql = $obj->build_sql();

				return Db_Sql::create()->fetchOne($sql); 
			} else
			{
				$obj->use_calc_rows();
				$sql = $obj->build_sql();
				Db_DbHelper::query($sql);
				return Db_DbHelper::scalar('SELECT FOUND_ROWS()');
			}
		}
	
		/* Interface methods */
	
		/**
		 * Return iterator object for ActiveRecord
		 *
		 * @return Db_ActiveRecordIterator
		 * @internal For internal use only
		 */
		function getIterator() 
		{
			return new Db_ActiveRecordIterator($this);
		}

		/* Magic */
	
		/**
		 * Override call() to dynamically call the database associations
		 *
		 * @param string $method_name
		 * @param mixed $parameters
		 */

		function __call($method_name, $parameters = null) 
		{
			if (method_exists($this, $method_name)) 
				// If the method exists, just call it
				return call_user_func_array(array($this, $method_name), $parameters);
				
			// ... otherwise, check to see if the method call is one of our
			// special ActiveRecord methods ...
			if (count($parameters) && is_array($parameters[0]))
				$parameters = $parameters[0];

			// ... first check for method names that match any of our explicitly
			// declared associations for this model ( e.g. public $has_many = "movies" ) ...
			if (in_array($method_name, array_keys($this->has_models)))
				return call_user_func_array(array($this, 'find_related'), array_merge(array($method_name), $parameters));
		
			// check for the [count,sum,avg,etc...]_all magic functions
			if (substr($method_name, -4) == "_all" && in_array(substr($method_name, 0, -4), $this->aggregations))
				return $this->aggregate_all(substr($method_name, 0, -4), $parameters);
			else
			{
				// check for the find_all_by_* magic functions
				if (strlen($method_name) > 11 && substr($method_name, 0, 11) == "find_all_by") 
					//$result = $this->find_all_by(substr($method_name, 12), $parameters);
					return call_user_func_array(array($this, 'find_all_by'), array_merge(array(substr($method_name, 12)), $parameters));

				// check for the find_by_* magic functions
				if (strlen($method_name) > 7 && substr($method_name, 0, 7) == "find_by") 
					//$result = $this->find_by(substr($method_name, 8), $parameters);
					return call_user_func_array(array($this, 'find_by'), array_merge(array(substr($method_name, 8)), $parameters));
			}

			//return call_user_func_array(array($this, $method_name), $parameters);
			return parent::__call($method_name, $parameters);
		}
		
		function __get($name) 
		{
			if (isset($this->$name)) 
				return $this->$name;

			// Evaluate custom column values
			if (array_key_exists($name, $this->custom_columns))
			{
				$methodName = 'eval_'.$name;
				if (method_exists($this, $methodName))
					return $this->{$name} = $this->$methodName();
			}
			
			if (array_key_exists($name, $this->has_models)) 
			{
				$this->__lock();
				$this->$name = $this->load_relation($name);
				$this->__unlock();
			}

			if (substr($name, -5) == '_list' && array_key_exists(substr($name, 0, -5), $this->has_models)) 
				return $this->prepare_relation_object(substr($name, 0, -5));

			if (!property_exists($this, $name))
				return parent::__get($name);
				
			return $this->$name;
		
			/*
			if (!isset($this->$name))
				return null;
			else
				return $this->$name;
			*/
		}

		public function __lock()
		{
			if (!$this->__locked)
				$this->__locked = true;
		}

		public function __unlock() 
		{
			if ($this->__locked)
				$this->__locked = false;
		}

		function __set($name, $value) 
		{
			if(!$name) return;
			
			if (!$this->__locked) 
			{
				// this if checks if first its an object if its parent is ActiveRecord
				$is_object = is_object($value);

				if ($is_object && ($value instanceof Db_ActiveRecord)) 
				{
					if (!is_null($this->has_one) && array_key_exists($name, $this->has_one)) 
					{
						$primary_key = $value->primary_key;
						if (isset($this->has_one[$name]['foreign_key']))
							$foreign_key = $this->has_one[$name]['foreign_key'];
						else
							$foreign_key = Phpr_Inflector::singularize($value->table_name) . "_" . $primary_key;

						$this->$foreign_key = $value->$primary_key;
					}

					if (!is_null($this->belongs_to) && array_key_exists($name, $this->belongs_to)) 
					{
						$primary_key = $this->primary_key;
						if (isset($this->belongs_to[$name]['foreign_key']))
							$foreign_key = $this->belongs_to[$name]['foreign_key'];
						else
							$foreign_key = Phpr_Inflector::singularize($this->table_name) . "_" . $primary_key;
						
						$has_primary_key = $has_foreign_key = false;
						$options = $this->get_relation_options('belongs_to', $name, $has_primary_key, $has_foreign_key);
						if (!$has_foreign_key)
							$options['foreign_key'] = Phpr_Inflector::foreign_key($value->table_name, $this->primary_key);

						$this->{$options['foreign_key']} = $value->{$options['primary_key']};
					}
					// this elseif checks if its an array of objects and if its parent is ActiveRecord
				} elseif (is_array($value) || ($is_object && ($value instanceof Db_DataCollection))) 
				{
					// update (replace) related records
					if (isset($this->has_models[$name])) 
					{
						$type = $this->has_models[$name];
						if (!in_array($type, array('has_many', 'has_and_belongs_to_many'))) 
							return;
					
						$this->unbind_all($name);
						if ($value instanceof ActiveRecord)
							$this->bind($name, $value);
						elseif (($value instanceof Db_DataCollection) || is_array($value)) 
						{
							foreach($value as $record)
								$this->bind($name, $record);
						}
					}
				}
			}

			// Assignment to something else, do it
			$this->$name = $value;
		}
	
		/* Relations */
	
		/**
		 * This method parses all the class properties to find relationships
		 */
		protected function load_relations() 
		{
			$this->has_one = Phpr_Util::splat_keys($this->has_one);
			$this->has_many = Phpr_Util::splat_keys($this->has_many);
			$this->has_and_belongs_to_many = Phpr_Util::splat_keys($this->has_and_belongs_to_many);
			$this->belongs_to = Phpr_Util::splat_keys($this->belongs_to);
			
			if (array_key_exists($this->className, self::$relations_cache))
			{
				$this->has_models = self::$relations_cache[$this->className];
				return;
			}

			/*
			$reflection = new ReflectionObject($this);
			
			foreach($reflection->getProperties() as $prop) 
			{
				// ignore private properties so we don't need to parse every single variable
				if ($prop->isPublic() || $prop->isProtected()) 
				{
					if (preg_match("/^(has_many|has_one|belongs_to|has_and_belongs_to_many)_(.+)/", $prop->name, $found)) 
					{
						$relationship = $found[1];
						$model = $found[2];
						$params = (array) $prop->getValue($this);

						$this->{$relationship}[$model] = $params;
					}
				}
			}
			*/

			// merge models
			// and add itself to the list of models
			$this->has_models = array_merge(
				Phpr_Util::indexing(Phpr_Util::splat($this->has_one), 'has_one'),
				Phpr_Util::indexing(Phpr_Util::splat($this->has_many), 'has_many'),
				Phpr_Util::indexing(Phpr_Util::splat($this->has_and_belongs_to_many), 'has_and_belongs_to_many'),
				Phpr_Util::indexing(Phpr_Util::splat($this->belongs_to), 'belongs_to')
			);
			
      	self::$relations_cache[$this->className] = $this->has_models;
		}

		/**
		 * Returns a the name of the join table that would be used for the two
		 * tables.	The join table name is decided from the alphabetical order
		 * of the two tables.	e.g. "genres_movies" because "g" comes before "m"
		 *
		 * @param string $first_table
		 * @param string $second_table
		 * @return string
		 */
		public function get_join_table_name($first_table, $second_table) 
		{
			$tables = array($first_table, $second_table);
			sort($tables);
			return implode('_', $tables);
		}
	
		/**
		 * Returns a related class name
		 */
		public function get_related($relation) 
		{
			if (!isset($this->has_models[$relation])) 
				return null;
				
			$relation_type = $this->has_models[$relation];
			$relation = $this->{$relation_type}[$relation];
		
			$class_name = (is_array($relation) && isset($relation['class_name'])) ? $relation['class_name'] : Phpr_Inflector::classify($relation);
			return $class_name;
		}
		
		/**
		 * Create related class instance
		 *
		 * @param string $relation
		 * @return Db_ActiveRecord
		 */
		public function related($relation) 
		{
			$class_name = $this->get_related($relation);
			
			if (class_exists($class_name))
				return new $class_name();
				
			return null;
		}
		
		protected function prepare_relation_object($name, $params = null)
		{
			if (!isset($this->has_models[$name])) 
				return null;
		
			$type = $this->has_models[$name];
			
			$has_primary_key = false;
			$has_foreign_key = false;
			$options = $this->get_relation_options($type, $name, $has_primary_key, $has_foreign_key);
			
			// Create model
			$object = new $options['class_name']();
			if (is_null($object))
				throw new Phpr_SystemException('Class not found: '.$options['class_name']);
				
			if (is_null($options['order']) && ($object->default_sort != ''))
				$options['order'] = $object->default_sort;

			// Apply params filter
			if (!is_null($params)) 
			{
				if ($params instanceof WhereBase)
					$object->where($params);
				elseif (is_array($params))
					$object->where($object->primary_key . ' IN (?)', $params);
				else
					$object->where($object->primary_key . ' = ?', $params);
			}
		
			if (!is_null($options['finder_sql'])) 
			{
				if (in_array($type, array('has_one', 'belongs_to')))
				{
					$object->limit(1);
					$object->calc_rows = false;
				}

				return $object->find_by_sql($options['finder_sql']);
			} else 
			{
				switch($type) 
				{
					case 'has_one':
						//$object->where($object->primary_key . ' = ?', $this->{$options['foreign_key']});
						if (!$has_foreign_key)
							$options['foreign_key'] = Phpr_Inflector::foreign_key($this->table_name, $object->primary_key);
						
						$object->where($options['foreign_key'] . ' = ?', $this->{$options['primary_key']});
						break;
					case 'has_many':
						if (!$has_foreign_key)
							$options['foreign_key'] = Phpr_Inflector::foreign_key($this->table_name, $object->primary_key);

						if (!$has_primary_key)
							$options['primary_key'] = Phpr_Inflector::foreign_key($this->table_name, $this->primary_key);

						$object->where($options['foreign_key'] . ' = ?', $this->get_primary_key_value());
						break;
					case 'has_and_belongs_to_many':
						if (!isset($options['join_table']))
							$options['join_table'] = $this->get_join_table_name($this->table_name, $object->table_name);

						if (!$has_primary_key)
							$options['primary_key'] = Phpr_Inflector::foreign_key($this->table_name, $this->primary_key);
							
						if (isset($options['join_primary_key']))
							$options['primary_key'] = $options['join_primary_key'];

						if (!$has_foreign_key)
							$options['foreign_key'] = Phpr_Inflector::foreign_key($object->table_name, $object->primary_key);

						$object->join($options['join_table'], $object->table_name . '.' . $object->primary_key . ' = ' . $options['join_table'] . '.' . $options['foreign_key'])->where($options['join_table'] . '.' . $options['primary_key'] . ' = ?', $this->{$this->primary_key});
						
						if (isset($options['use_straight_join']))
							$object->use_straight_join = $options['use_straight_join'];
						
						break;
					case 'belongs_to':
						if (!$has_foreign_key)
							$options['foreign_key'] = Phpr_Inflector::foreign_key($object->table_name, $this->primary_key);

						$object->where($options['primary_key'] . ' = ?', $this->{$options['foreign_key']});
						//$object->where($options['primary_key'] . ' = ?', $this->{$options['foreign_key']});
						break;
				}
			}

			$object->where($options['conditions'])->limit($options['limit']);
			$object->calc_rows = false;

			if ($options['order'] !== false)
				$object->order($options['order']);

			return $object;
		}
	
		protected function load_relation($name, $params = null) 
		{
			$object = $this->prepare_relation_object($name, $params);
			if (!$object) 
				return null;

			$type = $this->has_models[$name];
			if (in_array($type, array('has_one', 'belongs_to')))
				return $object->find();

			$data = $object->find_all_internal();

			$data->relation = $name;
			$data->parent = $this;
			return $data;
		}
		
		public function reset_relations()
		{
			foreach ($this->has_models as $name=>$settings)
			{
				if (isset($this->$name)) 
					unset($this->$name);
			}
			
			$this->changed_relations = array();
			$this->reset_custom_columns();
		}
		
		public function reset_custom_columns()
		{
			foreach ($this->custom_columns as $name=>$settings)
			{
				if (isset($this->$name)) 
					unset($this->$name);
			}
		}
		
		public function reset_plain_fields($skip_fields = array())
		{
			$fields = array_keys($this->fields());
			foreach ($fields as $field)
			{
				if (!array_key_exists($field, $this->has_models))
				{
					if (!in_array($field, $skip_fields))
						$this->$field = null;
				}
			}
		}
		
		public function get_relation_options($type, $name, &$has_primary_key, &$has_foreign_key)
		{
			$default_options = array(
				'class_name' => Phpr_Inflector::classify($name),
				'primary_key' => $this->primary_key,
				'foreign_key' => Phpr_Inflector::foreign_key($name, $this->primary_key),
				'conditions' => null,
				'order' => null,
				'limit' => null,
				'finder_sql' => null
			);
			
			$has_primary_key = false;
			$has_foreign_key = false;

			$relation = $this->$type;
			if (isset($relation) && isset($relation[$name])) {
				if (is_string($relation[$name]))
					$relation[$name] = array('class_name' => Phpr_Inflector::classify($relation[$name]));

				$has_primary_key = isset($relation[$name]['primary_key']);
				$has_foreign_key = isset($relation[$name]['foreign_key']);
				
				return array_merge($default_options, $relation[$name]);
			}
			
			return $default_options;
		}

		protected function change_relation($relation, $record, $action) 
		{
			if (!isset($this->has_models[$relation])) 
				return $this;

			$name = $relation;
			$type = $this->has_models[$name];
		
			if (!in_array($type, array('has_many', 'has_and_belongs_to_many'))) 
				return $this;
		
			$relations = $this->$type;
			$relation = $relations[$name];
		
			if ($record instanceof Db_ActiveRecord)
				$record = $record->{$record->primary_key};
		
			if (!isset($this->changed_relations[$action]))
				$this->changed_relations[$action] = array();

			if (!isset($this->changed_relations[$action][$name])) 
			{
				$this->changed_relations[$action][$name] = array(
					'values' => array(),
					'type' => $type,
					'relation' => $relation
				);
			}

			$this->changed_relations[$action][$name]['values'][] = $record;
			return $this;
		}
		
		protected function apply_deferred_relation_changes($deferred_session_key)
		{
			if ($deferred_session_key)
			{
				$bindings = Db_DeferredBinding::create();
				$bindings->where('master_class_name=?', $this->className);
				$bindings->where('session_key=?', $deferred_session_key);
			
				$bindings = $bindings->find_all_internal();
				foreach ($bindings as $binding)
				{
					$action = $binding->is_bind ? 'bind' : 'unbind';
					$this->change_relation($binding->master_relation_name, $binding->detail_key_value, $action);
					$binding->delete();
				}
			}
		}
		
		protected function find_related_record($relation, $record)
		{
			$keyValue = is_object($record) ? $record->get_primary_key_value() : $record;
			$related_records = $this->{$relation};

			foreach ($related_records as $obj)
			{
				if ($obj->get_primary_key_value() == $keyValue)
					return $obj;
			}
			
			return null;
		}

		/**
		 * Applies relation changes. Returns true in case if any relation has been changed.
		 */
		protected function apply_relations_changes($deferred_session_key) 
		{
			$result = false;
			$this->apply_deferred_relation_changes($deferred_session_key);
			
			// sort by action desc to unbind first
			krsort($this->changed_relations);

			$this->custom_relation_save();

			foreach($this->changed_relations as $action => $relation) 
			{
				foreach($relation as $name => $info) 
				{
					switch($info['type']) 
					{
						case 'has_many':
							$defaults = array(
								'class_name' => Phpr_Inflector::classify($name),
								'foreign_key' => Phpr_Inflector::foreign_key($this->table_name, $this->primary_key)
							);

							if (is_array($info['relation']))
								$options = array_merge($defaults, $info['relation']);
							else
								$options = array_merge($defaults, array('class_name' => Phpr_Inflector::classify($info['relation'])));

							// Create model
							$object = new $options['class_name']();
							if (is_null($object))
								throw new Phpr_SystemException('Class not found: '.$options['class_name']);

							foreach ($info['values'] as $record)
							{
								$related_record = $this->find_related_record($name, $record);

								if(isset($info['relation']['primary_key']))
									$primary_key = $this->{$info['relation']['primary_key']};
								else
									$primary_key = $this->{$this->primary_key};

								if ($action == 'bind')
								{
									if (!$related_record)
									{
										$this->sql_update($object->table_name, array($options['foreign_key'] => $primary_key), DB::where($object->primary_key . ' IN (?)', $record));
										$this->after_has_many_bind($record, $name);
										$result = true;
									}
								}
								elseif ($action == 'unbind')
								{
									if ($related_record)
									{
										$this->sql_update($object->table_name, array($options['foreign_key'] => null), DB::where($object->primary_key . ' IN (?)', $record));
										$this->after_has_many_unbind($related_record, $name);

										if (array_key_exists('delete', $info['relation']) && $info['relation']['delete'])
											$related_record->delete();
										$result = true;
									}
								}
							}

							break;
						case 'has_and_belongs_to_many':
							$defaults = array(
								'class_name' => Phpr_Inflector::classify($name)
							);
							if (is_array($info['relation']))
								$options = array_merge($defaults, $info['relation']);
							else
								$options = array_merge($defaults, array('class_name' => Phpr_Inflector::classify($info['relation'])));

							// Create model
							$object = new $options['class_name']();
							if (is_null($object))
								throw new Phpr_SystemException('Class not found: '.$options['class_name']);

							if (!isset($options['primary_key']))
								$options['primary_key'] = Phpr_Inflector::foreign_key($this->table_name, $this->primary_key);

							if (!isset($options['foreign_key']))
								$options['foreign_key'] = Phpr_Inflector::foreign_key($object->table_name, $object->primary_key);

							if (!isset($options['join_table']))
								$options['join_table'] = $this->get_join_table_name($this->table_name, $object->table_name);

							if ($action == 'bind')
							{
								$this->sql_insert($options['join_table'], array($options['primary_key'], $options['foreign_key']), Phpr_Util::pairs($this->{$this->primary_key}, $info['values']));
								$result = true;
							}
							elseif ($action == 'unbind')
							{
								$this->sql_delete($options['join_table'], Db::where($options['primary_key'] . ' = ?', $this->{$this->primary_key})->where($options['foreign_key'] . ' IN (?)', array($info['values'])));
								$result = true;
							}
							break;
					}
				}
			}
			return $result;
		}

		/**
		 * Dynamically adds a new relation to the model. 
		 * @param string $type Specifies a relation type: 'has_one', 'has_many', 'has_and_belongs_to_many', 'belongs_to'
		 * @param string $name Specifies a model field name to assign the relation to
		 * @param array $options Specifies a relation options: array('class_name'=>'Related_Class', 'delete'=>true)
		 */
		public function add_relation($type, $name, $options)
		{
			$this->{$type}[$name] = $options;
			$this->has_models[$name] = $type;
			$this->added_relations[$name] = array($type, $name, $options);
		}

		/**
		 * Bind related record
		 *
		 * @param string $relation
		 * @param mixed|ActiveRecord $record
		 * @param string $deferred_session_key An edit session key for deferred bindings
		 * @return Db_ActiveRecord
		 */
		public function bind($relation, $record, $deferred_session_key = null) 
		{
			if (!$record)
				throw new Phpr_SystemException('Binding failed: the record passed to the bind method is NULL.');
			
			if ($deferred_session_key === null)
				return $this->change_relation($relation, $record, 'bind');
			else
			{
				$binding = Db_DeferredBinding::create();
				$binding->master_class_name = $this->className;
				$binding->detail_class_name = get_class($record);
				$binding->master_relation_name = $relation;
				$binding->is_bind = 1;
				$binding->detail_key_value = $record->get_primary_key_value();
				$binding->session_key = $deferred_session_key;
				$binding->save();
			}
			
			return $this;
		}
		
		/**
		 * Allows to implement custom relation saving method
		 * The method must remove processed relations from the $changed_relations collection
		 */
		protected function custom_relation_save()
		{
		}

		/**
		 * Unbind related record
		 *
		 * @param string $relation
		 * @param mixed|ActiveRecord $record
		 * @param string $deferred_session_key An edit session key for deferred bindings
		 * @return Db_ActiveRecord
		 */
		public function unbind($relation, $record, $deferred_session_key = null) 
		{
			if ($deferred_session_key === null)
				return $this->change_relation($relation, $record, 'unbind');
			else
			{
				$binding = Db_DeferredBinding::create();
				$binding->master_class_name = $this->className;
				$binding->detail_class_name = get_class($record);
				$binding->master_relation_name = $relation;
				$binding->is_bind = 0;
				$binding->detail_key_value = $record->get_primary_key_value();
				$binding->session_key = $deferred_session_key;
				$binding->save();
			}
			
			return $this;
		}

		/**
		 * Cancels all deferred bindings added during an edit session
		 * @param string $deferred_session_key An edit session key
		 * @return Db_ActiveRecord
		 */
		public function cancelDeferredBindings($deferred_session_key)
		{
			Db_DeferredBinding::cancelDeferredActions($this->className, $deferred_session_key);
			return $this;
		}

		/**
		 * Unbind all related records
		 *
		 * @param string $relation
		 * @param string $deferred_session_key An edit session key for deferred bindings
		 * @return Db_ActiveRecord
		 */
		public function unbind_all($relation, $deferred_session_key = null) 
		{
			if (!isset($this->has_models[$relation])) 
				return $this;
				
			$name = $relation;
			$type = $this->has_models[$name];
		
			if (!in_array($type, array('has_many', 'has_and_belongs_to_many'))) 
				return $this;

			foreach($this->{$name} as $record)
				$this->unbind($name, $record, $deferred_session_key);
		
			return $this;
		}

	
		/* Aggregation */

		/**
		 * Implement *_all() functions (SQL aggregate functions)
		 *
		 * @param string $operation
		 * @param string[] $parameters
		 * @return mixed
		 */
		protected function aggregate_all($operation, $parameters = null) 
		{
			if (count($parameters) && $parameters[0]) 
			{
				$field = $parameters[0];
				if ((strpos($field, '.') === false) && (strpos($field, '(') === false)) 
					$field = $this->table_name . '.' . $field;
			} else
				$field = $this->table_name . '.*';

			$field = $operation . '(' . $field . ') as ' . $operation . '_result';
		
			if (!count($this->parts['from']))
				$this->from($this->table_name, $field);

			$this->parts['fields'] = array($field);
			return $this->fetchOne($this->build_sql());
		}

		public function count() 
		{
			if ($this->calc_rows)
				return $this->found_rows;
			else
				return (int)$this->aggregate_all('count', array($this->primary_key));
		}

		/* Serialization */
	
		public function serialize($serialize_relations = true) 
		{
			// Serialize fields
			$record = array('fields' => array());
			$fields = array_keys($this->fields());
			$reflection = new ReflectionObject($this);
			
			foreach($reflection->getProperties() as $property) 
			{
				if (!in_array($property->name, $fields)) 
					continue;
					
				$record['fields'][$property->name] = $this->{$property->name};
			}
		
			if (is_string($serialize_relations)) 
				$serialize_relations = preg_split('/[\s,;]+/', $serialize_relations, -1, PREG_SPLIT_NO_EMPTY);

			// Serialize column_info
			if (self::$cache_describe) 
			{
				// If already loaded - return
				if (isset(self::$describe_cache[$this->table_name]))
					$record['describe_cache'] = self::$describe_cache[$this->table_name];
			}
		
			// Serialize relations
			if (($serialize_relations === true) || is_array($serialize_relations)) 
			{
				foreach($this->has_models as $name => $relation) 
				{
					if (!isset($this->{$name})) 
						continue;
						
					if (is_array($serialize_relations) && !in_array($serialize_relations)) 
						continue;
						
					if (count($this->{$name}) == 0) 
						continue;
						
					if ($this->{$name} instanceof ActiveRecord)
						$record['relations'][$name] = $this->{$name}->serialize($serialize_associations);
					elseif ($this->{$name} instanceof DataCollection) 
					{
						$record['relations'][$name] = array();
						foreach($this->{$name} as $item)
							$record['relations'][$name][] = $record->serialize($serialize_associations);
					}
				}
			}		

			return $record;
		}

		public function unserialize($data) 
		{
			if (!is_array($data)) 
				return null;
		
			$fields = array_keys($this->fields());
			if (!count($fields)) 
				return null;

			if (self::$cache_describe && isset($data['describe_cache']))
				self::$describe_cache[$this->table_name] = $data['describe_cache'];

			if (isset($data['fields']))
			{
				foreach($data['fields'] as $key => $value) 
				{
					if (!in_array($key, $fields)) 
						continue;
						
					$this->$key = $value;
				}
			}

			$relations = array_keys($this->has_models);
			if (isset($data['relations']))
			{
				foreach($data['relations'] as $key => $value)
				{
					if (!in_array($key, $relations))
						continue;
				
					if (isset($this->has_models[$key]['class_name']))
						$classname = $this->has_models[$key]['class_name'];
					else
						$classname = Phpr_Inflector::classify($key);

					$childs = array();
					foreach($value['records'] as $record) 
					{
						$childs[] = $child = unserialize($record);
						$child->after_fetch(true);
					}
					$this->$key = new Db_DataCollection($childs);
				}
			}

			$this->after_fetch(true);
			return $this;
		}

		public function __sleep() 
		{
			$this->serialized = $this->serialize($this->serialize_associations);
			return array('serialized');
		}
	
		public function __wakeup()
		{
			if (isset($this->serialized))
			{
				$this->initialize();
				$this->unserialize($this->serialized);
				unset($this->serialized);
			}
		}

		public function save_in_session($key)
		{
			$list = array();
			
			if (Phpr::$session->has('active_record_store'))
				$list = Phpr::$session->get('active_record_store');

			if (in_array($key, $list))
				Phpr::$session->remove($key);
			else
				$list[] = $key;

			Phpr::$session->set($key, serialize($this));
			Phpr::$session->set('active_record_store', $list);
		}

		public static function load_from_session($key) 
		{
			if (Phpr::$session->has($key))
				return unserialize(Phpr::$session->get($key));

			return null;
		}
		
		protected static function cache_instance($modelClass, $keyName, $keyValue, $obj)
		{
			if ( !isset(self::$simpleCache[$modelClass]) )
				self::$simpleCache[$modelClass] = array();

			if ( !isset(self::$simpleCache[$modelClass][$keyName]) )
				self::$simpleCache[$modelClass][$keyName] = array();
				
			self::$simpleCache[$modelClass][$keyName][$keyValue] = $obj;
		}
		
		protected static function load_cached($modelClass, $keyName, $keyValue)
		{
			if (!array_key_exists($modelClass, self::$simpleCache))
				return -1;

			if (!array_key_exists($keyName, self::$simpleCache[$modelClass]))
				return -1;

			if (!array_key_exists($keyValue, self::$simpleCache[$modelClass][$keyName]))
				return -1;

			return self::$simpleCache[$modelClass][$keyName][$keyValue];
		}
		
		public function reset_simple_cache()
		{
			self::$simpleCache[$this->className] = array();
		}
		
		/**
		 * Allows to limit the result of the {@link Db_ActiveRecord::find_all() find_all()} method with a single page of records.
		 * Call this method before the {@link Db_ActiveRecord::find_all() find_all()} method call.
		 * <pre>$pagination = $products->paginate(0, 10);</pre>
		 * @documentable
		 * @see Phpr_Pagination
		 * @param integer $page_index Specifies a zero-based page index.
		 * @param integer $records_per_page Specifies the number of records the page should contain.
		 * @return Phpr_Pagination Returns the pagination object.
		 */
		public function paginate($page_index, $records_per_page)
		{
			$pagination = new Phpr_Pagination($records_per_page);
			$pagination->setRowCount($this->requestRowCount());
			$pagination->setCurrentPageIndex($page_index);
			$pagination->limitActiveRecord($this);

			return $pagination;
		}

		/*
		 * Validation
		 */

		public function valid($deferred_session_key = null) 
		{
			if ($this->before_validation($deferred_session_key) === false)
				return false;
			
			if ($this->new_record) 
			{
				if ($this->before_validation_on_create($deferred_session_key) === false)
					return false;
					
				if ($this->validate($deferred_session_key) === false)
					return false;
					
				if ($this->after_validation_on_create($deferred_session_key) === false)
					return false;
			} else {
				if ($this->before_validation_on_update($deferred_session_key) === false)
					return false;
					
				if ($this->validate($deferred_session_key) === false)
					return false;
				if ($this->after_validation_on_update($deferred_session_key) === false)
					return false;
			}

			if ($this->after_validation($deferred_session_key) === false)
				return false;
				
			return true;
		}

		public function validate($deferred_session_key = null) 
		{
			if ($this->validation && !$this->validation->validate($this, $deferred_session_key))
				$this->validation->throwException();
		}

		/**
		 * Triggered before the model column values are validated.
		 * Override this method in your model classes if you need to perform
		 * any operations before the model is validated.
		 * @documentable
		 * @param string $deferred_session_key Specifies the deferred session key passed to the {@link Db_ActiveRecord::save() save()} method.
		 */
		public function before_validation($deferred_session_key = null) 
		{
		}

		/**
		 * Triggered after the model column values are validated.
		 * Override this method in your model classes if you need to perform
		 * any operations after the model is validated.
		 * @documentable
		 * @param string $deferred_session_key Specifies the deferred session key passed to the {@link Db_ActiveRecord::save() save()} method.
		 */
		public function after_validation($deferred_session_key = null) 
		{
		}

		/**
		 * Triggered before a new model column values are validated.
		 * Override this method in your model classes if you need to perform
		 * any operations before a new model is validated.
		 * @documentable
		 * @param string $deferred_session_key Specifies the deferred session key passed to the {@link Db_ActiveRecord::save() save()} method.
		 */
		public function before_validation_on_create($deferred_session_key = null) 
		{
		}

		/**
		 * Triggered after a new model column values are validated.
		 * Override this method in your model classes if you need to perform
		 * any operations after a new model is validated.
		 * @documentable
		 * @param string $deferred_session_key Specifies the deferred session key passed to the {@link Db_ActiveRecord::save() save()} method.
		 */
		public function after_validation_on_create($deferred_session_key = null) 
		{
		}

		/**
		 * Triggered before an existing model column values are validated.
		 * Override this method in your model classes if you need to perform
		 * any operations before an existing model is validated.
		 * @documentable
		 * @param string $deferred_session_key Specifies the deferred session key passed to the {@link Db_ActiveRecord::save() save()} method.
		 */
		public function before_validation_on_update($deferred_session_key = null) 
		{
		}

		/**
		 * Triggered after an existing model column values are validated.
		 * Override this method in your model classes if you need to perform
		 * any operations after an existing model is validated.
		 * @documentable
		 * @param string $deferred_session_key Specifies the deferred session key passed to the {@link Db_ActiveRecord::save() save()} method.
		 */
		public function after_validation_on_update($deferred_session_key = null) 
		{
		}
		
		/**
		 * Visual representation and validation - column definition feature
		 */
		
		public function init_columns_info($context = null, $force = false)
		{
			global $activerecord_no_columns_info;
			
			if ($activerecord_no_columns_info)
				return false;
			
			if ($this->get_model_option('no_column_init'))
				return $this;
			
			if ($this->columns_loaded && !$force)
				return $this;

			$this->columns_loaded = true;
//			$context = $context ? $context : $this->column_definition_context;

			$this->define_columns($context);
			$this->fireEvent('onDefineColumns');
			
			return $this;
		}
		
		public function get_column_definitions($context = null)
		{
			$this->init_columns_info($context);
			
			$result = $this->column_definitions;
			
			if (!array_key_exists($this->className, self::$cached_column_definitions))
				return $result;
				
			foreach (self::$cached_column_definitions[$this->className] as $dbName=>$definition)
				$result[$dbName] = $definition->setContext($this);
			
			return $result;
		}
		
		public function disable_column_cache($context = null, $update_column_definitions = true)
		{
			$this->form_field_columns_initialized = true;
			self::$column_cache_disabled[$this->className] = true;
			$this->init_columns_info($context, true);
		}
		
		protected function is_column_cache_disabled()
		{
			return array_key_exists($this->className, self::$column_cache_disabled);
		}
		
		/**
		 * Adds a column definition to the model.
		 * Column definitions determine column labels, validation rules and other properties. The method
		 * returns {@link Db_ColumnDefinition column definition object} object which you can use to configure the column presentation and validation parameters.
		 * @documentable
		 * @param string $dbName Specifies a column database name, a calculated column name, or a relation name.
		 * @param string $displayName Specifies a name to display in lists and forms.
		 * @return Db_ColumnDefinition Returns the column definition object.
		 */
		public function define_column($dbName, $displayName)
		{
			$this->defined_column_list[$dbName] = 1;
			
			if (!$this->is_column_cache_disabled())
			{
				if (!array_key_exists($this->className, self::$cached_column_definitions))
					self::$cached_column_definitions[$this->className] = array();

				if (!array_key_exists($dbName, self::$cached_column_definitions[$this->className]))
					return self::$cached_column_definitions[$this->className][$dbName] = new Db_ColumnDefinition($this, $dbName, $displayName);

				return self::$cached_column_definitions[$this->className][$dbName]->setContext($this);
			} else
				return $this->column_definitions[$dbName] = new Db_ColumnDefinition($this, $dbName, $displayName);
		}
		
		/**
		 * Adds column definition for <em>has_on</em> or <em>belongs_to</em> {@link http://lemonstand.com/docs/creating_data_relations/ relation} field.
		 * You should define columns for a relation field if you want the field to be displayed in lists or forms. The method
		 * returns {@link Db_ColumnDefinition column definition object} object which you can use to configure the column presentation and validation parameters.
		 * Example:
		 * <pre>
		 * public $belongs_to = array(
		 *   'post'=>array('class_name'=>'Blog_Post', 'foreign_key'=>'post_id')
		 * );
		 *
		 * ...
		 *
		 * public function define_columns($context = null)
		 * {
		 *   $this->define_relation_column('post', 'post', 'Post', db_varchar, '@title');
		 *   ...
		 * }
		 * </pre>
		 * @documentable
		 * @param string $columnName Specifies the column name. Use any unique sql-compatible name. 
		 * @param string $relationName Specifies the relation name (should be declared as {@link Db_ActiveRecord::has_many has_many}, 
		 * {@link Db_ActiveRecord::has_and_belongs_to_many has_and_belongs_to_many}, 
		 * {@link Db_ActiveRecord::belongs_to belongs_to})
		 * @param string $displayName Specifies a name to display in lists and forms
		 * @param string $type Specifies a display value type (see <em>db_xxx</em> constants in the description of {@link Db_ActiveRecord} class)
		 * @param string $valueExpression Specifies SQL expression for evaluating the relation display value used in lists and forms.
		 * Use '@' symbol to indicate a joined table: concat(@first_name, ' ', @last_name).
		 * @return Db_ColumnDefinition Returns the column definition object.
		 */
		public function define_relation_column($columnName, $relationName, $displayName, $type, $valueExpression)
		{
			$this->defined_column_list[$columnName] = 1;

			if (!$this->is_column_cache_disabled())
			{
				if (!array_key_exists($this->className, self::$cached_column_definitions))
					self::$cached_column_definitions[$this->className] = array();

				if (!array_key_exists($columnName, self::$cached_column_definitions[$this->className]))
					return self::$cached_column_definitions[$this->className][$columnName] = new Db_ColumnDefinition($this, $columnName, $displayName, $type, $relationName, $valueExpression);

				return self::$cached_column_definitions[$this->className][$columnName]->extendModel($this);
			} else
				return $this->column_definitions[$columnName] = new Db_ColumnDefinition($this, $columnName, $displayName, $type, $relationName, $valueExpression);
		}
		
		/**
		 * Adds column definition for <em>has_and_belongs_to_many</em> or <em>has_many</em> {@link http://lemonstand.com/docs/creating_data_relations/ relation} field.
		 * You should define columns for a relation field if you want the field to be displayed in lists or forms. The method
		 * returns a {@link Db_ColumnDefinition column definition object} which you can use to configure the column presentation and validation parameters.
		 * Example:
		 * <pre>
		 * public $has_and_belongs_to_many = array(
		 *   'categories'=>array('class_name'=>'Blog_Category', 'join_table'=>'blog_posts_categories', 'order'=>'name')
		 * );
		 *
		 * ...
		 *
		 * public function define_columns($context = null)
		 * {
		 *   $this->define_multi_relation_column('categories', 'categories', 'Categories', '@name');
		 *   ...
		 * }
		 * </pre>
		 * @documentable
		 * @param string $columnName Specifies a column name. Use any unique sql-compatible name.
		 * @param string $relationName Specifies a relation name (should be declared as has_and_belongs_to_many or has_many)
		 * @param string $displayName Specifies a name to display in lists and forms
		 * @param string $valueExpression Specifies SQL expression for evaluating the relation display value used in lists and forms.
		 * Use '@' symbol to indicate a joined table: concat(@first_name, ' ', @last_name).
		 * @return Db_ColumnDefinition Returns the column definition object.
		 */
		public function define_multi_relation_column($columnName, $relationName, $displayName, $valueExpression)
		{
			$this->defined_column_list[$columnName] = 1;

			if (!$this->is_column_cache_disabled())
			{
				if (!array_key_exists($this->className, self::$cached_column_definitions))
					self::$cached_column_definitions[$this->className] = array();

				if (!array_key_exists($columnName, self::$cached_column_definitions[$this->className]))
					return self::$cached_column_definitions[$this->className][$columnName] = new Db_ColumnDefinition($this, $columnName, $displayName, db_varchar, $relationName, $valueExpression);

				return self::$cached_column_definitions[$this->className][$columnName]->extendModel($this);
			} else
				return $this->column_definitions[$columnName] = new Db_ColumnDefinition($this, $columnName, $displayName, db_varchar, $relationName, $valueExpression);
		}
		
		/**
		 * Makes a column visible in forms.
		 * The column should be defined with {@link Db_ActiveRecord::define_column() define_column()}, 
		 * {@link Db_ActiveRecord::define_relation_column() define_relation_column()} or 
		 * {@link Db_ActiveRecord::define_multi_relation_column() define_multi_relation_column()} method before this method is called.
		 * The method returns the {@link Db_FormFieldDefinition form field definition} object which can be used for further configuration of the field. Example:
		 * <pre>$this->add_form_field('author_name', 'left');</pre>
		 * @documentable
		 * @param string $dbName Specifies a column database name or a calculated column name
		 * @param $side Specifies a side of the form the column should appear. Supported values: <em>left</em>, <em>right</em>, <em>full</em>
		 * @return Db_FormFieldDefinition Returns the form field definition object.
		 */
		public function add_form_field($dbName, $side = 'full')
		{
			if (!$this->form_field_columns_initialized)
				$this->disable_column_cache();

			return $this->form_elements[] = new Db_FormFieldDefinition($this, $dbName, $side);
		}
		
		/**
		 * Adds a form section.
		 * Form sections allow to separate form space with a title and description text.
		 * @documentable
		 * @param string $description Specifies a section description
		 * @param string $title Specifies a section title, optional
		 * @param string $html_id Specifies an id for the html element the form section will be rendered in on the form, optional
		 * @return Db_FormSection Returns the form section object.
		 */
		public function add_form_section($description, $title=null, $html_id=null)
		{
			if (!$this->form_field_columns_initialized)
				$this->disable_column_cache();

			return $this->form_elements[] = new Db_FormSection($title, $description, $html_id);
		}

		/**
		 * Adds a form custom area. 
		 * Form areas can contain arbitrary HTML markup. Form area contents should be defined in
		 * a partial with name _form_area_<em>name</em>.htm in the controller's views directory,
		 * where <em>id</em> is an area identifier specified in the method parameter. The optional
		 * $location parameter allows to specify a path of a directory containing the partial.
		 * @documentable
		 * @param string $id Specifies an area identifier
		 * @param string $location Optional path of a directory containing the form area partial.
		 * @return Db_FormCustomArea Returns the form custom area object.
		 */
		public function add_form_custom_area($id, $location = null)
		{
			if (!$this->form_field_columns_initialized)
				$this->disable_column_cache();

			return $this->form_elements[] = new Db_FormCustomArea($id, $location);
		}
		
		/**
		 * Adds a custom form partial.
		 * Form partials can contain arbitrary HTML markup. Example:
		 * <pre>$this->add_form_partial(PATH_APP.'/modules/mymodule/partials/_form_partial.htm');</pre>
		 * @documentable
		 * @param string $path Specifies an absolute path to the partial file.
		 * @return Db_FormPartial Returns the form partial object.
		 */
		public function add_form_partial($path)
		{
			if (!$this->form_field_columns_initialized)
				self::disable_column_cache($this->className);
			
			return $this->form_elements[] = new Db_FormPartial($path);
		}

		/**
		 * Sets a specific HTML ID value to a form tab element
		 * @param string $tabName Specifies a tab name
		 * @param string $tabId Specifies a tab identifier
		 * @return Db_ActiveRecord
		 */
		public function form_tab_id($tabName, $tabId)
		{
			$this->formTabIds[$tabName] = $tabId;
			return $this;
		}
		
		/**
		 * Sets initial tab visibility value
		 * @param string $tabName Specifies a tab name
		 * @param bool $value Determines whether the tab is visible
		 * @return Db_ActiveRecord
		 */
		public function form_tab_visibility($tabName, $value)
		{
			$this->formTabVisibility[$tabName] = $value;
			return $this;
		}
		
		/**
		 * Sets a CSS class value for a specific form tab
		 * @param string $tabName Specifies a tab name
		 * @param string $value Specifies the CSS class name
		 * @return Db_ActiveRecord
		 */
		public function form_tab_css_class($tabName, $value)
		{
			$this->formTabCssClasses[$tabName] = $value;
			return $this;
		}
		
		/**
		 * Finds a form field definition by its corresponding column name.
		 * The form field should be defined with {@link Db_ActiveRecord::define_form_field() define_form_field()} method.
		 * This method can be used for configuring a form field in an existing model
		 * before its form is rendered.
		 * @documentable
		 * @param string $dbName Specifies the column name.
		 * @return Db_FormFieldDefinition Returns the form field definition object or NULL if the field was not found.
		 */
		public function find_form_field($dbName)
		{
			foreach ($this->form_elements as $element)
			{
				if ($element instanceof Db_FormFieldDefinition && $element->dbName == $dbName)
					return $element;
			}
			
			return null;
		}
		
		/**
		 * Deletes a form field by its corresponding column name.
		 * This method can be used for removing a form field from an existing model
		 * before its form is rendered.
		 * @documentable
		 * @param string $dbName Specifies the column name.
		 * @return boolean Returns TRUE if the field has been found and deleted. Returns FALSE otherwise.
		 */
		public function delete_form_field($dbName)
		{
			foreach ($this->form_elements as $index=>$element)
			{
				if ($element instanceof Db_FormFieldDefinition && $element->dbName == $dbName)
				{
					unset($this->form_elements[$index]);
					return true;
				}
			}
			
			return false;
		}
		
		/**
		 * Finds a column definition by the column name.
		 * Columns are defined in {@link Db_ActiveRecord::define_columns() define_columns()} method.
		 * @documentable
		 * @param string $columnName Specifies the column name.
		 * @return Db_ColumnDefinition Returns the column definition object or NULL if the column was not found in the model.
		 */
		public function find_column_definition($columnName)
		{
			$this->init_columns_info();

			if (array_key_exists($columnName, $this->column_definitions))
				return $this->column_definitions[$columnName];
			
			if (!array_key_exists($this->className, self::$cached_column_definitions))
				return null;

			if (array_key_exists($columnName, self::$cached_column_definitions[$this->className]))
				return self::$cached_column_definitions[$this->className][$columnName]->setContext($this);

			return null;
		}

		/**
		 * Defines model columns.
		 * Override this method to define columns, references and form fields in your models. 
		 * By defining columns you inform the framework which fields from the database table we are going 
		 * to use in lists and forms, and what titles these fields should have. Columns are objects of
		 * {@link Db_ColumnDefinition} class, which has a number of properties determining how 
		 * the column is displayed and validated. You can call model's {@link Db_ActiveRecord::define_column() define_column()}, 
		 * {@link Db_ActiveRecord::define_relation_column() define_relation_column()} and 
		 * {@link Db_ActiveRecord::define_multi_relation_column() define_multi_relation_column()}
		 * methods inside this method. Example:
		 * <pre>
		 * public function define_columns($context = null)
		 * {
		 *   $this->define_column('title', 'Title')->order('asc')->validation()->fn('trim');
		 *   $this->define_multi_relation_column('categories', 'categories', 'Categories', '@name');
		 * }
		 * </pre>
		 * @see http://lemonstand.com/docs/administration_area_lists/ Lists
		 * @see http://lemonstand.com/docs/administration_area_forms/ Forms
		 * @documentable
		 * @param string $context Specifies the execution context.
		 */
		protected function define_columns($context = null)
		{
		}

		/**
		 * Defines form fields.
		 * Override this method to define the model's form fields. All column which you add to the form
		 * should be defined with {@link Db_ActiveRecord::define_columns() define_columns()} method.
		 * Use {@link Db_ActiveRecord::add_form_field() add_form_field()}, 
		 * {@link Db_ActiveRecord::add_form_custom_area() add_form_custom_area()}, 
		 * {@link Db_ActiveRecord::add_form_partial() add_form_partial()}, 
		 * and {@link Db_ActiveRecird::add_form_section() add_form_section()} 
		 * methods inside this method. Example:
		 * <pre>
		 * public function define_form_fields($context = null)
		 * {
		 *   $this->add_form_field('author_name', 'left');
		 *   $this->add_form_field('author_email', 'right');
		 * }
		 * </pre>
		 * @see http://lemonstand.com/docs/administration_area_forms/ Forms
		 * @documentable
		 * @param string $context Specifies the execution context. 
		 */
		public function define_form_fields($context = null)
		{
		}

		/**
		 * Returns a formatted column value. The field should be defined with {@link Db_ActiveRecord::define_column() define_column()}, 
		 * {@link Db_ActiveRecord::define_relation_column() define_relation_column()} or 
		 * {@link Db_ActiveRecord::define_multi_relation_column() define_multi_relation_column()} method.
		 * 
		 * For relation fields the method uses relation data loaded from database with the model data
		 * instead of issuing another SQL query. It makes this method efficient in record lists.
		 *
		 * <span class="note">By default datetime fields are converted to GMT during saving and displayField() returns value converted
		 * back to a time zone specified in <em>TIMEZONE</em> parameter in the configuration file (config.php). You can cancel this behavior
		 * by calling {@link Db_ColumnDefinition::dateAsIs() dateAsIs()} of the column definition object.</span>
		 * @documentable
		 * @param string $dbName Specifies the column name.
		 * @param string $media Specifies a media - a list or a form. Text values could be truncated for the list media.
		 * @return string Returns the formatted column value.
		 */
		public function displayField($dbName, $media = 'form')
		{
			$column_definitions = $this->get_column_definitions();
			
			if (!array_key_exists($dbName, $column_definitions))
				throw new Phpr_SystemException('Cannot execute method "displayField" for field '.$dbName.' - the field is not defined in column definition list.');

			return $column_definitions[$dbName]->displayValue($media);
		}
		
		/**
		 * Alias for the {@link Db_ActiveRecord::displayField() displayField()} method
		 * @documentable
		 * @param string $dbName Specifies the column name.
		 * @return string Returns the formatted column value.
		 */
		public function columnValue($dbName)
		{
			return $this->displayField($dbName);
		}
		
		/**
		 * Executes the find_all() method and returns a {@link Db_DataCollection collection}.
		 * This method simplifies front-end coding.
		 * @documentable
		 * @return Db_DataCollection Returns a collection
		 */
		public function collection()
		{
			return $this->find_all();
		}

		/*
		 * Event descriptions
		 */

		/**
		 * Triggered before a SQL query is sent to the database. 
		 * <span class="note">This event is triggered only if the <em>ENABLE_DEVELOPER_TOOLS</em> 
		 * {@link http://lemonstand.com/docs/lemonstand_configuration_options/ configuration option} is enabled. </span>
		 * Event handler example:
		 * <pre>
		 * public function subscribeEvents()
		 * {
		 *   Backend::$events->addEvent('core:onBeforeDatabaseQuery', $this, 'on_before_query');
		 *   Backend::$events->addEvent('core:onAfterDatabaseQuery', $this, 'on_after_query');
		 * }
		 * 
		 * public function on_before_query($sql)
		 * {
		 *   // Start timing for the query
		 *   Phpr_DebugHelper::start_timing($sql);
		 * }
		 * 
		 * public function on_after_query($sql, $result)
		 * {
		 *   // Stop timing and write the result to the trace log (logs/info.txt)
		 *   Phpr_DebugHelper::end_timing($sql);
		 * }
		 * </pre>
		 * @event core:onBeforeDatabaseQuery
		 * @triggered /phproad/modules/db/classes/db_sqlbase.php
		 * @see core:onAfterDatabaseQuery
		 * @package core.events
		 * @author LemonStand eCommerce Inc.
		 * @param string $query Specifies the SQL query string.
		 */
		private function event_onBeforeDatabaseQuery($query) {}
			
		/**
		 * Triggered before a SQL query is executed by the database.
		 * <span class="note">This event is triggered only if the <em>ENABLE_DEVELOPER_TOOLS</em> 
		 * {@link http://lemonstand.com/docs/lemonstand_configuration_options/ configuration option} is enabled. </span>
		 * The handler should accept two parameters - the SQL query string and MySQL query result value.
		 * The result value depends on the query type and described in {@link http://ru.php.net/manual/en/function.mysql-query.php PHP documentation}. 
		 * Event handler example:
		 * <pre>
		 * public function subscribeEvents()
		 * {
		 *   Backend::$events->addEvent('core:onBeforeDatabaseQuery', $this, 'on_before_query');
		 *   Backend::$events->addEvent('core:onAfterDatabaseQuery', $this, 'on_after_query');
		 * }
		 * 
		 * public function on_before_query($sql)
		 * {
		 *   // Start timing for the query
		 *   Phpr_DebugHelper::start_timing($sql);
		 * }
		 * 
		 * public function on_after_query($sql, $result)
		 * {
		 *   // Stop timing and write the result to the trace log (logs/info.txt)
		 *   Phpr_DebugHelper::end_timing($sql);
		 * }
		 * </pre>
		 * @event core:onAfterDatabaseQuery
		 * @triggered /phproad/modules/db/classes/db_sqlbase.php
		 * @see core:onBeforeDatabaseQuery
		 * @package core.events
		 * @author LemonStand eCommerce Inc.
		 * @param string $query Specifies the SQL query string.
		 * @param mixed $result Specifies the query result value.
		 */
		private function event_onAfterDatabaseQuery($query, $result) {}

		/**
		 * Triggered before database connection is established.
		 * You can use this event for creating custom MySQL connection. The handler
		 * should return MySQL connection resource - a result of <em>mysql_pconnect()</em> or <em>mysql_connect()</em> functions.
		 * @event core:onBeforeDatabaseConnect
		 * @triggered /phproad/modules/db/classes/db_mysqldriver.php
		 * @package core.events
		 * @author LemonStand eCommerce Inc.
		 * @param Db_MySQLDriver $driver Specifies MySQL driver object.
		 * @return mixed Returns the MySQL connection resource.
		 */
		private function event_onBeforeDatabaseConnect($driver) {}
	}

?>