<?php

	/**
	 * This class adds some Core functions and filters to Twig engine.
	 * @has_documentable_methods
	 */
	class Core_TwigExtension extends Twig_Extension
	{
		public static function create()
		{
			return new self();
		}
		
		public function getName()
		{
			return 'Core extension';
		}
		
		public function getFunctions()
		{
			return array(
				'method',
				'field'
			);
		}
		
		public function getFilters()
		{
			return array(
				'currency' => new Twig_Filter_Method($this, 'currency_filter'),
				'unescape' => new Twig_Filter_Method($this, 'unescape_filter', array('is_safe' => array('html'))),
				'unset' => new Twig_Filter_Method($this, 'unset_filter'),
				'repeat' => new Twig_Filter_Method($this, 'repeat_filter')
			);
		}
		
		public function getTests()
		{
			return array(
				'instance_of' => new Twig_Filter_Method($this, 'instance_of_test'),
				'array' => new Twig_Filter_Method($this, 'array_test'),
			);
		}
		
		public function instance_of_test($obj, $class_name)
		{
			return $obj instanceof $class_name;
		}
		
		public function array_test($var)
		{
			return is_array($var);
		}
		
		public function method()
		{
			$args = func_get_args();
			
			if (count($args) < 2)
				throw new Twig_Error_Runtime('The method() function should have at least 2 arguments - the class name and the method name.');
				
			$class = trim(array_shift($args));
			$method = trim(array_shift($args));
			
			if (!Core_Configuration::is_php_allowed())
			{
				$prohibited_class_map = Phpr::$config->get('CORE_PROHIBITED_CLASS_MAP', array());
				if ($prohibited_class_map)
				{
					foreach ($prohibited_class_map as $prohibited_class_name=>$prohibited_methods)
					{
						if (strtoupper($prohibited_class_name) == strtoupper($class) || $class instanceof $prohibited_class_name)
						{
							if ($prohibited_methods == '*')
								throw new Twig_Error_Runtime(sprintf('Using the %s class in Twig templates is prohibited.', $class));
								
							foreach ($prohibited_methods as $prohibited_method)
							{
								$prohibited_method = strtoupper($prohibited_method);
								if ($prohibited_method == strtoupper($method))
									throw new Twig_Error_Runtime(sprintf('Using the %s::%s() method in Twig templates is prohibited.', $class, $method));
							}
						}
					}
				}
			}
			
			if (class_exists($class) && method_exists($class, $method))
				return call_user_func_array(array($class, $method), $args);
				
			return null;
		}
		
		public function field($object, $field)
		{
			return $object->$field;
		}
		
		public function currency_filter($num, $decimals = 2)
		{
			return format_currency($num, $decimals);
		}
		
		public function unescape_filter($value)
		{
			return $value;
		}
		
		public function unset_filter($array, $element)
		{
			if (array_key_exists($element, $array))
				unset($array[$element]);
				
			return $array;
		}
		
		public function repeat_filter($str, $count)
		{
			return str_repeat($str, $count);
		}

		/**
		 * Invokes a method of an object or class. 
		 * Usually this function is needed only when you want to invoke a static class method. 
		 * Non-static object methods can be invoked directly. The following example
		 * calls the {@link Shop_Cart::list_active_items() list_active_items()} method of the {@link Shop_Cart} class.
		 * <pre twig>{% set items = method('Shop_Cart', 'list_active_items') %}</pre>
		 * @package core.twig functions
		 * @name method
		 * @twigtype function
		 * @author LemonStand eCommerce Inc.
		 * @param mixed $object_or_class_name Object or class name. Use class names to call static methods.
		 * @param string $method_name Specifies the method name to invoke.
		 * @param mixed $param_1 Parameter to pass to the invoked method. The function supports unlimited
		 * number of parameters.
		 * @return mixed The function returns the result of the invoked method.
		 */
		private function function_method($object_or_class_name, $method_name, $param_1 = null) {}
		
		/**
		 * Returns an object's field value. 
		 * Use this function to access virtual object fields, which cannot be accessed directly by Twig engine. 
		 * If you are sure that an object property does exist, but Twig reports that the method doesn't exist, use this function.
		 * The following example outputs the current customer name.
		 * <pre twig>{{ field(this.customer, 'name') }}</pre>
		 * @package core.twig functions
		 * @name field
		 * @twigtype function
		 * @author LemonStand eCommerce Inc.
		 * @param mixed $object Object to load the field value from.
		 * @param string $field_name Specifies the field name.
		 * @return mixed The function returns the object field value.
		 */
		private function function_field($object, $field_name) {}
		
		/**
		 * Returns a currency representation of a number.
		 * The currency filter is a Twig filter equivalent of the {@link format_currency()} function. Applies currency formatting to a number. 
		 * The following example outputs a product price: 
		 * <pre twig>{{ product.price()|currency }}</pre>
		 * The second optional argument allows to specify the number of decimal digits to return. The next example displays a product price
		 * with a single decimal digit: 
		 * <pre twig>{{ product.price()|currency(1) }}</pre>
		 * @package shop.twig filters
		 * @name currency
		 * @twigtype filter
		 * @see format_currency()
		 * @author LemonStand eCommerce Inc.
		 * @param string $num specifies a value to format.
		 * @param integer $decimals specifies a number of decimal digits. Optional parameter, the default value is 2.
		 * @return mixed The function returns the object field value.
		 */
		private function function_currency($num, $decimals = 2) {}
			
		/**
		 * Cancels HTML escaping for a displayed value. 
		 * By default HTML escaping is enabled for all values which you output in Twig templates. However in some cases you may
		 * want to cancel escaping, for example when a value should contain HTML tags, like product or category long descriptions. 
		 * The following example outputs a product description.
		 * <pre twig>{{ product.description|unescape }}</pre>
		 * @package core.twig filters
		 * @name unescape
		 * @twigtype filter
		 * @author LemonStand eCommerce Inc.
		 * @param string $str String to process.
		 * @return mixed Returns the unescape HTML string.
		 */
		private function function_unescape($str) {}

		/**
		 * Removes an array element.
		 * The unset filter allows to delete an array element. The element index should be specified in the filter parameter. 
		 * The following example removes <em>shipping_method</em> element from the array: 
		 * <pre twig>
		 * {# Define some array #}
		 * {%
		 *   set steps = {
		 *     'billing_info': 'Billing Information',
		 *     'shipping_info': 'Shipping Information',
		 *     'shipping_method': 'Shipping Method',
		 *     'payment_method': 'Payment Method',
		 *     'review': 'Order Review',
		 *     'pay': 'Pay'
		 *   }
		 * %}
		 * 
		 * {# Remove the shipping_method element #}
		 * {% set steps = steps|unset('shipping_method') %}
		 * </pre>
		 * @package core.twig filters
		 * @name unset
		 * @twigtype filter
		 * @author LemonStand eCommerce Inc.
		 * @param array $array Array to remove the element from.
		 * @param string $element The element index to remove.
		 * @return array Returns the updated array.
		 */
		private function function_unset($array, $element) {}
			
		/**
		 * Returns a string repeated multiple times.
		 * This Twig filter is an equivalent of PHP {@link http://php.net/manual/en/function.str-repeat.php str_repeat()} function.
		 * Example: 
		 * <pre twig>{{ "&nbsp;"|repeat(5) }}</pre>
		 * @package core.twig filters
		 * @name repeat
		 * @twigtype filter
		 * @author LemonStand eCommerce Inc.
		 * @param string $str Specifies a string to repeat
		 * @param integer $count Specifies the number of times the string should be repeated.
		 * @return string Returns the processed string.
		 */
		private function function_repeat($str, $count) {}
			
		/**
		 * Tests whether an object is instance of a specific class. 
		 * This is an equivalent of PHP {@link http://php.net/manual/en/language.operators.type.php instance_of} operator. Usage example: 
		 * <pre twig>{% set products = products is instance_of('Shop_Product')  ? products.find_all() : products %}</pre>
		 * @package core.twig tests
		 * @name instance_of
		 * @twigtype test
		 * @author LemonStand eCommerce Inc.
		 * @param mixed $obj Specifies the object to test.
		 * @param string $class_name Specifies the class name to test the object against.
		 * @return boolean Returns TRUE if the specified object is instance of the specified class name. Returns FALSE otherwise.
		 */
		private function function_instance_of($obj, $class_name) {}
			
		/**
		 * Tests whether the specified variable is array. 
		 * Usage example: 
		 * <pre twig>
		 * {% if value is not array %}
		 *   <input type="hidden" name="{{ name }}" value="{{ value }}"/>
		 * {% else %}
		 *   {% for item in value %}
		 *     <input type="hidden" name="{{ name }}" value="{{ item }}"/>
		 *   {% endfor %}
		 * {% endif %}
		 * </pre>
		 * @package core.twig tests
		 * @name array
		 * @twigtype test
		 * @author LemonStand eCommerce Inc.
		 * @param mixed $var Specifies the variable to test.
		 * @return boolean Returns TRUE if the specified variable is an array. Returns FALSE otherwise.
		 */
		private function function_array($var) {}
	}

?>