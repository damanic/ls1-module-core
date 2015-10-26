<?

	/**
	 * Core number helpers
	 */
	class Core_Number {
		/**
		 * Returns centimeters (cm) from inches (in)
		 * @param number $cm number
		 * @return number Returns centimeters (cm)
		 */
		public static function in_to_cm($in, $precision = 2) {
			return round($in * 2.54, $precision);
		}
		
		/**
		 * Returns inches (in) from centimeters (cm)
		 * @param mixed $in number
		 * @return number Returns inches (in)
		 */
		public static function cm_to_in($cm, $precision = 2) {
			return round($cm / 2.54, $precision);
		}
		
		/**
		 * Returns kilograms (kg) from pounds (lb)
		 * @param number $lb number
		 * @return number Returns kilograms (kg)
		 */
		public static function lb_to_kg($lb, $precision = 2) {
			return round($lb * 0.45359237, $precision);
		}
		
		/**
		 * Returns pounds (lb) from kilograms (kg)
		 * @param number $kg number
		 * @return number Returns pounds (lb)
		 */
		public static function kg_to_lb($kg, $precision = 2) {
			return round($kg / 0.45359237, $precision);
		}
		
		/**
		 * Returns true if the passed value is a floating point number
		 * @param number $value number
		 * @return boolean Returns boolean
		 */
		public static function is_valid($value) {
			return preg_match('/^[0-9]*?\.?[0-9]*$/', $value);
		}
		
		/**
		 * Returns true if the passed value is an integer value
		 * @param number $value number
		 * @return boolean Returns boolean
		 */
		public static function is_valid_int($value) {
			return preg_match('/^[0-9]*$/', $value);
		}

		/**
		 * Compares two floating point values.
		 * The function returns 1 if the first number is more the second number.
		 * The function returns -1 if the first number is less the second number.
		 * The function returns 0 if the numbers are equal (considering the precision specified with the epsilon parameter)
		 * @param number $num_1 The first number to compare
		 * @param number $num_2 The first number to compare
		 * @param number $epsilon The precision
		 * @return integer Returns integer value indicating whether the first number is more, equal or less than the second number.
		 */
		public static function compare_float($num_1, $num_2, $epsilon = 0.0001) {
			if(abs($num_1-$num_2) < $epsilon)
				return 0;

			if ($num_1 > $num_2)
				return 1;

			if ($num_1 < $num_2)
				return -1;
		}
	}