<?php

	/**
	 * Core string helpers
	 */
	class Core_String
	{
		/**
		 * Removes slash from the beginning of a specified string
		 * and adds a slash to the end of the string.
		 * @param string $str Specifies a string to process
		 * @return string
		 */
		public static function normalizeUri($str)
		{
			if (substr($str, 0, 1) == '/')
				$str = substr($str, 1);
				
			if (substr($str, -1) != '/')
				$str .= '/';
				
			if ($str == '/')
				return null;
				
			return $str;
		}
		
		/**
		 * Puts a dot to the end of a string if last character is not a punctuation symbol.
		 */
		public static function finalize($str)
		{
			$str = trim($str);
			$lastChar = substr($str, -1);
			
			$punctuation = array(',', '.', ';', '!', '?');
			
			if (!in_array($lastChar, $punctuation))
				$str .= '.';
				
			return $str;
		}

		/**
		 * Returns JavaScript-safe string
		 */
		public static function js_encode($str)
		{
			return str_replace('"', '\"', $str);
		}

		/**
		 *	Make a string's first character uppercase
		 */
		public static function ucfirst($str)
		{
			$str = trim($str);
			return mb_strtoupper(mb_substr($str, 0, 1)).mb_substr($str, 1);
		}
		
		/**
		 * Splits a string to words and phrases and returns an array
		 * @param string $query A string to split
		 * @return array
		 */
		public static function split_to_words($query)
		{
			$query = trim($query);
			
			$matches = array();
			$phrases = array();
			if (preg_match_all('/("[^"]+")/', $query, $matches))
				$phrases = $matches[0];

			foreach ($phrases as $phrase)
				$query = str_replace($phrase, '', $query);

			$result = array();
			foreach ($phrases as $phrase)
			{
				$phrase = trim(substr($phrase, 1, -1));
				if (strlen($phrase))
					$result[] = $phrase;
			}

			$words = explode(' ', $query);
			foreach ($words as $word)
			{
				$word = trim($word);
				if (strlen($word))
					$result[] = $word;
			}
			
			return $result;
		}
		
		/**
		 * Replaces accented characters with ASCII equivalents.
		 * @param string $string String to process.
		 * @param boolean $remove_non_ascii Remove non-ascii characters after asciifying.
		 * @return string Returns the processed string.
		 */
		public static function asciify($string, $remove_non_ascii = false)
		{
			$from = array( 'Á', 'À', 'Â', 'Ä', 'Ǎ', 'Ă', 'Ā', 'Ã', 'Å', 'Ǻ', 'Ą', 'Ɓ', 'Ć', 'Ċ', 'Ĉ', 'Č', 'Ç', 'Ď', 'Ḍ', 'Ɗ', 'É', 'È', 'Ė', 'Ê', 'Ë', 'Ě', 'Ĕ', 'Ē', 'Ę', 'Ẹ', 'Ǝ', 'Ə', 'Ɛ', 'Ġ', 'Ĝ', 'Ǧ', 'Ğ', 'Ģ', 'Ɣ', 'Ĥ', 'Ḥ', 'Ħ', 'I', 'Í', 'Ì', 'İ', 'Î', 'Ï', 'Ǐ', 'Ĭ', 'Ī', 'Ĩ', 'Į', 'Ị', 'Ĵ', 'Ķ', 'Ƙ', 'Ĺ', 'Ļ', 'Ł', 'Ľ', 'Ŀ', 'Ń', 'Ň', 'Ñ', 'Ņ', 'Ó', 'Ò', 'Ô', 'Ö', 'Ǒ', 'Ŏ', 'Ō', 'Õ', 'Ő', 'Ọ', 'Ø', 'Ǿ', 'Ơ', 'Ŕ', 'Ř', 'Ŗ', 'Ś', 'Ŝ', 'Š', 'Ş', 'Ș', 'Ṣ', 'Ť', 'Ţ', 'Ṭ', 'Ú', 'Ù', 'Û', 'Ü', 'Ǔ', 'Ŭ', 'Ū', 'Ũ', 'Ű', 'Ů', 'Ų', 'Ụ', 'Ư', 'Ẃ', 'Ẁ', 'Ŵ', 'Ẅ', 'Ƿ', 'Ý', 'Ỳ', 'Ŷ', 'Ÿ', 'Ȳ', 'Ỹ', 'Ƴ', 'Ź', 'Ż', 'Ž', 'Ẓ', 'á', 'à', 'â', 'ä', 'ǎ', 'ă', 'ā', 'ã', 'å', 'ǻ', 'ą', 'ɓ', 'ć', 'ċ', 'ĉ', 'č', 'ç', 'ď', 'ḍ', 'ɗ', 'é', 'è', 'ė', 'ê', 'ë', 'ě', 'ĕ', 'ē', 'ę', 'ẹ', 'ǝ', 'ə', 'ɛ', 'ġ', 'ĝ', 'ǧ', 'ğ', 'ģ', 'ɣ', 'ĥ', 'ḥ', 'ħ', 'ı', 'í', 'ì', 'i', 'î', 'ï', 'ǐ', 'ĭ', 'ī', 'ĩ', 'į', 'ị', 'ĵ', 'ķ', 'ƙ', 'ĸ', 'ĺ', 'ļ', 'ł', 'ľ', 'ŀ', 'ŉ', 'ń', 'ň', 'ñ', 'ņ', 'ó', 'ò', 'ô', 'ö', 'ǒ', 'ŏ', 'ō', 'õ', 'ő', 'ọ', 'ø', 'ǿ', 'ơ', 'ŕ', 'ř', 'ŗ', 'ś', 'ŝ', 'š', 'ş', 'ș', 'ṣ', 'ſ', 'ť', 'ţ', 'ṭ', 'ú', 'ù', 'û', 'ü', 'ǔ', 'ŭ', 'ū', 'ũ', 'ű', 'ů', 'ų', 'ụ', 'ư', 'ẃ', 'ẁ', 'ŵ', 'ẅ', 'ƿ', 'ý', 'ỳ', 'ŷ', 'ÿ', 'ȳ', 'ỹ', 'ƴ', 'ź', 'ż', 'ž', 'ẓ', 'Α', 'Ά', 'Β', 'Γ', 'Δ', 'Ε', 'Έ', 'Ζ', 'Η', 'Ή', 'Θ', 'Ι', 'Ί', 'Ϊ', 'Κ', 'Λ', 'Μ', 'Ν', 'Ξ', 'Ο', 'Ό', 'Π', 'Ρ', 'Σ', 'Τ', 'Υ', 'Ύ', 'Ϋ', 'Φ', 'Χ', 'Ψ', 'Ω', 'Ώ', 'α', 'ά', 'β', 'γ', 'δ', 'ε', 'έ', 'ζ', 'η', 'ή', 'θ', 'ι', 'ί', 'ϊ', 'ΐ', 'κ', 'λ', 'μ', 'ν', 'ξ', 'ο', 'ό', 'π', 'ρ', 'σ', 'ς', 'τ', 'υ', 'ύ', 'ϋ', 'ΰ', 'φ', 'χ', 'ψ', 'ω', 'ώ', 'Æ', 'Ǽ', 'Ǣ', 'Ð', 'Đ', 'Ĳ', 'Ŋ', 'Œ', 'Þ', 'Ŧ', 'æ', 'ǽ', 'ǣ', 'ð', 'đ', 'ĳ', 'ŋ', 'œ', 'ß', 'þ', 'ŧ' );
			$to = array( 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'B', 'C', 'C', 'C', 'C', 'C', 'D', 'D', 'D', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'G', 'G', 'G', 'G', 'G', 'G', 'H', 'H', 'H', 'I', 'I', 'I', 'I', 'I', 'I', 'I', 'I', 'I', 'I', 'I', 'I', 'J', 'K', 'K', 'L', 'L', 'L', 'L', 'L', 'N', 'N', 'N', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'R', 'R', 'R', 'S', 'S', 'S', 'S', 'S', 'S', 'T', 'T', 'T', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'W', 'W', 'W', 'W', 'W', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Z', 'Z', 'Z', 'Z', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'b', 'c', 'c', 'c', 'c', 'c', 'd', 'd', 'd', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'g', 'g', 'g', 'g', 'g', 'g', 'h', 'h', 'h', 'i', 'i', 'i', 'i', 'i', 'i', 'i', 'i', 'i', 'i', 'i', 'i', 'j', 'k', 'k', 'q', 'l', 'l', 'l', 'l', 'l', 'n', 'n', 'n', 'n', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'r', 'r', 'r', 's', 's', 's', 's', 's', 's', 's', 't', 't', 't', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'w', 'w', 'w', 'w', 'w', 'y', 'y', 'y', 'y', 'y', 'y', 'y', 'z', 'z', 'z', 'z', 'A', 'A', 'V', 'G', 'D', 'E', 'E', 'Z', 'I', 'I', 'Th', 'I', 'I', 'I', 'K', 'L', 'M', 'N', 'X', 'O', 'O', 'P', 'R', 'S', 'T', 'Y', 'Y', 'Y', 'F', 'Ch', 'Ps', 'O', 'O', 'a', 'a', 'v', 'g', 'd', 'e', 'e', 'z', 'i', 'i', 'th', 'i', 'i', 'i', 'i', 'k', 'l', 'm', 'n', 'x', 'o', 'o', 'p', 'r', 's', 's', 't', 'y', 'y', 'y', 'y', 'f', 'ch', 'ps', 'o', 'o', 'Ae', 'Ae', 'Ae', 'Dh', 'Dj', 'Ij', 'Ng', 'Oe', 'Th', 'Th', 'ae', 'ae', 'ae', 'dh', 'dj', 'ij', 'ng', 'oe', 'ss', 'th', 'th' );

			$result = str_replace( $from, $to, $string );
			
			if ($remove_non_ascii)
				$result = preg_replace('/[^(\x20-\x7F)]*/','', $result);
	
			return $result;
		}
		
		public static function process_ls_tags($str)
		{
			$result = str_replace('{root_url}', root_url('/', true), $str);
			
			if (strpos($result, '{tax_inc_label}') !== false)
				$result = str_replace('{tax_inc_label}', tax_incl_label(), $result);
				
			return $result;
		}
	}

?>