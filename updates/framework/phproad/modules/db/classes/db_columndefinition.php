<?php

	/**
	 * Represents a model column definition. 
	 * Objects of this class are used for defining presentation and validation field properties in models.
	 * {@link Db_ListBehavioer List Behavior} and {@link Db_FormBehavior Form Behavior} use data from the 
	 * column definition objects to output correct field labels and format field data.
	 * @documentable
	 * @author LemonStand eCommerce Inc.
	 * @package core.classes
	 */
	class Db_ColumnDefinition
	{
		/**
		 * @var string Specifies the database column name.
		 * @documentable
		 */

		public $dbName;

		/**
		 * @var string Specifies the visual column name. This value is used
		 * in list column titles and form labels.
		 * @documentable
		 */
		public $displayName;
		public $defaultOrder = null;
		
		/**
		 * @var string Specifies the column type.
		 * @documentable
		 */
		public $type;
		public $isCalculated;
		public $isCustom;
		public $isReference;
		public $referenceType = null;
		public $referenceValueExpr;
		public $relationName;
		public $referenceForeignKey;
		public $referenceClassName;
		public $visible = true;
		public $defaultVisible = true;
		public $listTitle = null;
		public $listNoTitle = false;
		public $noLog = false;
		public $log = false;
		public $dateAsIs = false;
		public $currency = false;
		public $noSorting = false;
		
		private $_model;
		private $_columnInfo;
		private $_calculated_column_name;
		private $_validationObj = null;
		
		private static $_relation_joins = array();
		private static $_cached_models = array();
		private static $_cached_class_instances = array();
		
		public $index;

		/**
		 * Date/time display format
		 * @var string
		 */
		private $_dateFormat = '%x';
		private $_dateTimeFormat = '%x %X';
		private $_timeFormat = '%X';
		
		/**
		 * Floating point numbers display precision.
		 * @var int
		 */
		private $_precision = 2;
		
		/**
		 * Text display length
		 */
		private $_length = null;

		public function __construct($model, $dbName, $displayName, $type=null, $relationName=null, $valueExpression=null)
		{
			// traceLog('Column definition for '.get_class($model).':'.$dbName.' #'.$model->id);
			$this->dbName = $dbName;
			$this->displayName = $displayName;
			$this->_model = $model;
			$this->isReference = strlen($relationName);
			$this->relationName = $relationName;

			if (!$this->isReference)
			{
				$this->_columnInfo = $this->_model->column($dbName);
				if ($this->_columnInfo)
					$this->type = $this->_columnInfo->type;

				if ($this->_columnInfo)
				{
					$this->isCalculated = $this->_columnInfo->calculated;
					$this->isCustom = $this->_columnInfo->custom;
				}
			} else
			{
				$this->type = $type;
				
				if (strlen($valueExpression))
				{
					$this->referenceValueExpr = $valueExpression;
					$this->defineReferenceColumn();
				}
			}
			
			if ($this->type == db_date || $this->type == db_datetime)
				$this->validation();
		}
		
		public function extendModel($model)
		{
			$this->setContext($model);

			if ($this->isReference && strlen($this->referenceValueExpr))
				$this->defineReferenceColumn();
				
			return $this;
		}

		/*
		 *
		 * Common column properties
		 *
		 */

		/**
		 * Specifies the column type.
		 * By default column types match the database column types, but you can use
		 * this method to override the database column type and thus change the field
		 * display parameters. For example, if you set the field type to db_number
		 * for a varchar field, its value will be aligned to the right in {@link Db_ListBehavior lists} and {@link Db_FormBehavior forms}.
		 * @documentable
		 * @param string $typeName Specifies the type name 
		 * (see <em>db_xxx</em> constants in the description of {@link Db_ActiveRecord} class)
		 * @return Db_ColumnDefinition Returns the updated column definition object.
		 */
		public function type($typeName)
		{
			$validTypes = array(db_varchar, db_number, db_float, db_bool, db_datetime, db_date, db_time, db_text);
			if (!in_array($typeName, $validTypes))
				throw new Phpr_SystemException('Invalid database type: '.$typeName);
				
			$this->type = $typeName;
			$this->_columnInfo = null;
			
			return $this;
		}
		
		/**
		 * Specifies the date format.
		 * The date format is used for displaying date and date/time field values in {@link Db_ListBehavior lists} and {@link Db_FormBehavior forms}.
		 * @documentable
		 * @param string $displayFormat Specifies the display format, compatible with {@link http://php.net/manual/en/function.strftime.php strftime} PHP function.
		 * @return Db_ColumnDefinition Returns the updated column definition object.
		 */
		public function dateFormat($displayFormat)
		{
			if ($this->type == db_datetime || $this->type == db_date || $this->type == db_time)
				$this->_dateFormat = $displayFormat;
			else 
				throw new Phpr_SystemException('Error in column definition for: '.$this->dbName.' column. Method "dateFormat" is applicable only for date or time fields.');
			$this->validation(null, true);
			
			return $this;
		}
		
		/**
		 * Specifies the time format.
		 * The date format is used for displaying date and date/time field values in {@link Db_ListBehavior lists} and {@link Db_FormBehavior forms}.
		 * @documentable
		 * @param string $displayFormat Specifies the display format, compatible with {@link http://php.net/manual/en/function.strftime.php strftime} PHP function.
		 * @return Db_ColumnDefinition Returns the updated column definition object.
		 */
		public function timeFormat($displayFormat)
		{
			if ($this->type == db_datetime || $this->type == db_time)
				$this->_timeFormat = $displayFormat;
			else
				throw new Phpr_SystemException('Error in column definition for: '.$this->dbName.' column. Method "timeFormat" is applicable only for datetime or time fields.');
			$this->validation(null, true);
			
			return $this;
		}
		
		public function dateTimeFormat($displayFormat)
		{
			if ($this->type == db_datetime)
				$this->_dateTimeFormat = $displayFormat;
			else
				throw new Phpr_SystemException('Error in column definition for: '.$this->dbName.' column. Method "dateTimeFormat" is applicable only for datetime fields.');
			
			return $this;
		}
		
		/**
		 * Disables timezone conversion for datetime fields.
		 * By default datetime fields are converted to GMT during saving and {@link Db_ActiveRecord::displayField() displayField()} returns value converted
		 * back to a time zone specified in <em>TIMEZONE</em> parameter in the configuration file (config.php). You can cancel this behavior
		 * by calling this method.
		 * @documentable
		 * @return Db_ColumnDefinition Returns the updated column definition object.
		 */
		public function dateAsIs()
		{
			$this->dateAsIs = true;
			$this->validation(null, true);
			return $this;
		}
		
		/**
		 * Sets the precision for displaying floating point numbers in {@link Db_ListBehavior lists}.
		 * @documentable
		 * @param integer $precision Specifies the number of decimal places.
		 * @return Db_ColumnDefinition Returns the updated column definition object.
		 */
		public function precision($precision)
		{
			if ($this->type == db_float)
				$this->_precision = $precision;
			else 
				throw new Phpr_SystemException('Error in column definition for: '.$this->dbName.' column. Method "precision" is applicable only for floating point number fields.');
				
			return $this;
		}
		
		/**
		 * Sets the maximum length for displaying varchar and text values in {@link Db_ListBehavior lists}.
		 * Text values longer than the specified length get truncated.
		 * @documentable
		 * @param integer $length Specifies the length value.
		 * @return Db_ColumnDefinition Returns the updated column definition object.
		 */
		public function length($length)
		{
			if ($this->type == db_varchar || $this->type == db_text)
				$this->_length = $length;
			else 
				throw new Phpr_SystemException('Error in column definition for: '.$this->dbName.' column. Method "length" is applicable only for varchar or text fields.');

			return $this;
		}
		
		/**
		 * Hides the column from {@link Db_ListBehavior lists}. 
		 * @documentable
		 * @return Db_ColumnDefinition Returns the updated column definition object.
		 */
		public function invisible()
		{
			$this->visible = false;
			return $this;
		}
		
		/**
		 * Makes the column invisible in {@link Db_ListBehavior lists} by default. 
		 * Users can make the column visible by updating the list settings.
		 * @documentable
		 * @return Db_ColumnDefinition Returns the updated column definition object.
		 */
		public function defaultInvisible()
		{
			$this->defaultVisible = false;
			return $this;
		}
		
		/**
		 * Sets column title for {@link Db_ListBehavior lists}. 
		 * By default list column titles match column names. You can override the column name with this method.
		 * @documentable
		 * @param string $title Specifies the column {@link Db_ListBehavior list} title.
		 * @return Db_ColumnDefinition Returns the updated column definition object.
		 */
		public function listTitle($title)
		{
			$this->listTitle = $title;
			return $this;
		}
		
		/**
		 * Allows to hide the column {@link Db_ListBehavior list} title.
		 * @documentable
		 * @param boolean $value Determines whether the title is invisible.
		 * @return Db_ColumnDefinition Returns the updated column definition object.
		 */
		public function listNoTitle($value = true)
		{
			$this->listNoTitle = $value;
			return $this;
		}
		
		/**
		 * Do not log changes of the column.
		 */
		public function noLog()
		{
			$this->noLog = true;
			return $this;
		}
		
		/**
		 * Disables or enables sorting for the column in {@link Db_ListBehavior lists}.
		 * By default all columns are sortable in {@link Db_ListBehavior lists}. You can use this method to disable sorting by a specific column.
		 * @documentable
		 * @param boolean $value Determines whether the column is not sortable.
		 * @return Db_ColumnDefinition Returns the updated column definition object.
		 */
		public function noSorting($value = true)
		{
			$this->noSorting = true;
			return $this;
		}
		
		/**
		 * Log changes of the column. By default changes are not logged for calculated and custom columns.
		 */
		public function log()
		{
			$this->log = true;
			return $this;
		}
		
		/**
		 * Indicates that lists should use this column as a sorting column by default.
		 * {@link Db_ListBehavior List Behavior} uses this feature until the user
		 * chooses selects another sorting column.
		 * @documentable
		 * @param string $direction Specifies the sorting direction - <em>asc</em> or <em>desc</em>.
		 * @return Db_ColumnDefinition Returns the updated column definition object.
		 */
		public function order($directon = 'asc')
		{
			$this->defaultOrder = $directon;
			
			return $this;
		}
		
		/**
		 * Indicates that the column value should be formatted as currency.
		 * @documentable
		 * @param boolean $value Enables or disables the feature. Pass TRUE value to display values as currency.
		 * @return Db_ColumnDefinition Returns the updated column definition object.
		 */
		public function currency($value)
		{
			$this->currency = $value;
			return $this;
		}

		/**
		 * Initializes and returns the validation rule set object.
		 * Use the {@link Phpr_ValidationRules validation rule set object} to configure the column validation parameters.
		 * Note that validation is automatically enabled for date, datetime, float and numeric fields.
		 * @documentable
		 * @param string $customFormatMessage Specifies the format-specific validation message.
		 * @param boolean $re_add This parameter is for internal use.
		 * @return Phpr_ValidationRules Returns the validation object.
		 */
		public function validation($customFormatMessage = null, $readd = false)
		{
			if (!strlen($this->type))
				throw new Phpr_SystemException('Error applying validation to '.$this->dbName.' column. Column type is unknown. Probably this is a calculated column. Please call the "type" method to set the column type.');
				
			if ($this->_validationObj && !$readd)
				return $this->_validationObj;

			$dbName = $this->isReference ? $this->referenceForeignKey : $this->dbName;

			$rule = $this->_model->validation->add($dbName, $this->displayName);
			if ($this->type == db_date)
				$rule->date($this->_dateFormat, $customFormatMessage);
			elseif ($this->type == db_datetime)
				$rule->dateTime($this->_dateFormat.' '.$this->_timeFormat, $customFormatMessage, $this->dateAsIs);
			elseif ($this->type == db_float)
				$rule->float($customFormatMessage);
			elseif ($this->type == db_number)
				$rule->numeric($customFormatMessage);

			return $this->_validationObj = $rule;
		}
		
		/*
		 *
		 * Internal methods - used by the framework
		 *
		 */
		
		public function getColumnInfo()
		{
			return $this->_columnInfo;
		}
		
		public function getDateFormat()
		{
			return $this->_dateFormat;
		}
		
		public function getTimeFormat()
		{
			return $this->_timeFormat;
		}

		/*
		 * Datetime fields are converted to GMT during saving and displayValue returns value converted
	 	 * back to a time zone specified in the configuration file.
	 	 */
		public function displayValue($media)
		{
			$dbName = $this->dbName;

			if (!$this->isReference)
				$value = $this->_model->$dbName;
			else
			{
				$columName = $this->_calculated_column_name;
				$value = $this->_model->$columName;
			}

			switch ($this->type)
			{
				case db_varchar:
				case db_text:
					if ($media == 'form' || $this->_length === null)
						return $value;
					
					return Phpr_Html::strTrim($value, $this->_length);
				case db_number:
				case db_bool:
					return $value;
				case db_float:
					if ($media != 'form')
					{
						if ($this->currency)
							return format_currency($value);

						return Phpr::$lang->num($value, $this->_precision);
					}
					else
						return $value;
				case db_date:
					if(gettype($value) == 'string' && strlen($value))
					{
						$value = new Phpr_Datetime($value.' 00:00:00');
					}
					return $value ? $value->format($this->_dateFormat) : null;
				case db_datetime:
					if(gettype($value) == 'string' && strlen($value))
					{
						if(strlen($value) == 10) $value.=' 00:00:00';
						$value = new Phpr_Datetime($value);
					}
					if(!$this->dateAsIs)
					{
						if($media == 'time')
							return $value ? Phpr_Date::display($value, $this->_timeFormat) : null;
						elseif($media == 'date')
							return $value ? Phpr_Date::display($value, $this->_dateFormat) : null;
						else
							return $value ? Phpr_Date::display($value, $this->_dateTimeFormat) : null;
					}
					else
					{
						if($media == 'time')
							return $value ? $value->format($this->_timeFormat) : null;
						elseif($media == 'date')
							return $value ? $value->format($this->_dateFormat) : null;
						else
							return $value ? $value->format($this->_dateTimeFormat) : null;
					}
				case db_time:
					return $value;
				default:
					return $value;
			}
		}
		
		public function getSortingColumnName()
		{
			if (!$this->isReference)
				return $this->dbName;

			return $this->_calculated_column_name;
		}

		protected function defineReferenceColumn()
		{
			if (!array_key_exists($this->relationName, $this->_model->has_models))
				throw new Phpr_SystemException('Error defining reference "'.$this->relationName.'". Relation '.$this->relationName.' is not found in model '.get_class($this->_model));

			$relationType = $this->_model->has_models[$this->relationName];

			$has_primary_key = $has_foreign_key = false;
			$options = $this->_model->get_relation_options($relationType, $this->relationName, $has_primary_key, $has_foreign_key);

			if (!is_null($options['finder_sql'])) 
				throw new Phpr_SystemException('Error defining reference "'.$this->relationName.'". Relation finder_sql option is not supported.');

			$this->referenceType = $relationType;
			
			$columnName = $this->_calculated_column_name = $this->dbName.'_calculated';
			
			$colDefinition = array();
			$colDefinition['type'] = $this->type;
			
			$this->referenceClassName = $options['class_name'];

			if (!array_key_exists($options['class_name'], self::$_cached_class_instances))
			{
				$object = new $options['class_name'](null, array('no_column_init'=>true, 'no_validation'=>true));
				self::$_cached_class_instances[$options['class_name']] = $object;
			}
			
			$object = self::$_cached_class_instances[$options['class_name']];
			
			if ($relationType == 'has_one' || $relationType == 'belongs_to')
			{
				$objectTableName = $this->relationName.'_calculated_join';
				$colDefinition['sql'] = str_replace('@', $objectTableName.'.', $this->referenceValueExpr);

				$joinExists = isset(self::$_relation_joins[$this->_model->objectId][$this->relationName]);

				if (!$joinExists)
				{
					switch($relationType) 
					{
						case 'has_one' : 
							if (!$has_foreign_key)
								$options['foreign_key'] = Phpr_Inflector::foreign_key($this->_model->table_name, $object->primary_key);

							$this->referenceForeignKey = $options['foreign_key'];
							$condition = "{$objectTableName}.{$options['foreign_key']} = {$this->_model->table_name}.{$options['primary_key']}";
							$colDefinition['join'] = array("{$object->table_name} as {$objectTableName}"=>$condition);
						break;
						case 'belongs_to' : 
							$condition = "{$objectTableName}.{$options['primary_key']} = {$this->_model->table_name}.{$options['foreign_key']}";
							$this->referenceForeignKey = $options['foreign_key'];
							$colDefinition['join'] = array("{$object->table_name} as {$objectTableName}"=>$condition);

						break;
					}
					self::$_relation_joins[$this->_model->objectId][$this->relationName] = $this->referenceForeignKey;
				} else
					$this->referenceForeignKey = self::$_relation_joins[$this->_model->objectId][$this->relationName];
			} else
			{
				$this->referenceForeignKey = $this->relationName;

				switch($relationType) 
				{
					case 'has_many' :
						$valueExpr = str_replace('@', $object->table_name.'.', $this->referenceValueExpr);
						$colDefinition['sql'] = "select group_concat($valueExpr ORDER BY 1 SEPARATOR ', ') from {$object->table_name} where
							{$object->table_name}.{$options['foreign_key']} = {$this->_model->table_name}.{$options['primary_key']}";
							
						if ($options['conditions'])
							$colDefinition['sql'] .= " and ({$options['conditions']})";
							
					break;
					case 'has_and_belongs_to_many':
						$join_table_alias = $this->relationName.'_relation_table';
						$valueExpr = str_replace('@', $join_table_alias.'.', $this->referenceValueExpr);

						if (!isset($options['join_table']))
							$options['join_table'] = $this->_model->get_join_table_name($this->_model->table_name, $object->table_name);

						if (!$has_primary_key)
							$options['primary_key'] = Phpr_Inflector::foreign_key($this->_model->table_name, $this->_model->primary_key);

						if (!$has_foreign_key)
							$options['foreign_key'] = Phpr_Inflector::foreign_key($object->table_name, $object->primary_key);

						$colDefinition['sql'] = "select group_concat($valueExpr ORDER BY 1 SEPARATOR ', ') from {$object->table_name} as {$join_table_alias}, {$options['join_table']} where
							{$join_table_alias}.{$object->primary_key}={$options['join_table']}.{$options['foreign_key']} and
							{$options['join_table']}.{$options['primary_key']}={$this->_model->table_name}.{$this->_model->primary_key}";
						
						if ($options['conditions'])
							$colDefinition['sql'] .= " and ({$options['conditions']})";
					break;
				}
			}

			$this->_model->calculated_columns[$columnName] = $colDefinition;
		}
		
		public function setContext($model)
		{		
			$this->_model = $model;
			return $this;
		}
	}

?>