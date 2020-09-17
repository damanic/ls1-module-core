<?

	/**
	 * Core number helpers
	 */
	class Core_Number {
		/**
		 * Returns centimeters (cm) from inches (in)
		 * @param float $in value in inches
		 * @param int $precision round down to decimal places, default is 1 mm precision
		 * @return float Returns centimeters (cm)
		 */
		public static function in_to_cm($in, $precision = 1) {
			return round($in * 2.54, $precision);
		}
		
		/**
		 * Returns inches (in) from centimeters (cm)
		 * @param float $cm in centimeters
		 * @param int $precision round down to decimal places, default is 1 mm precision
		 * @return float  Returns inches (in)
		 */
		public static function cm_to_in($cm, $precision = 7) {
			return round($cm / 2.54, $precision);
		}
		
		/**
		 * Returns kilograms (kg) from pounds (lb)
		 * @param float $lb in pounds
		 * @param int $precision round down to decimal places, default precision down to 1 gram
		 * @return float Returns kilograms (kg)
		 */
		public static function lb_to_kg($lb, $precision = 3) {
			return round($lb * 0.45359237, $precision);
		}
		
		/**
		 * Returns pounds (lb) from kilograms (kg)
		 * @param float $kg in kilograms
		 * @param int $precision round down to decimal places, default precision down to 1 gram
		 * @return float Returns pounds (lb)
		 */
		public static function kg_to_lb($kg, $precision = 8) {
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