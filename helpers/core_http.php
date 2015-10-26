<?php

	class Core_Http
	{
		/**
		 * Posts data to a specified URL and returns response string
		 * @param string $endpoint An URL to send data to, without a protocol prefix.
		 * @param array @fields Specifies an array of name-value pairs to post
		 * @param bool $ssl Use SSL
		 */
		public static function post_data($endpoint, $fields = array(), $ssl = true, $force_port = null)
		{
			$errno = null;
			$errorstr = null;
			
			$slash_pos = strpos($endpoint, '/');
			
			if ($slash_pos === false)
			{
				$url = "/nvp";
			} else {
				$url = substr($endpoint, $slash_pos);
				$endpoint = substr($endpoint, 0, $slash_pos);
			}

			$fp = null;
			try
			{
				$port = $force_port == null ? 80 : $force_port;
				$sock_endpoint = $endpoint;
				if ($ssl)
				{
					$port = $force_port == null ? 443  : $force_port;
					$sock_endpoint = 'ssl://'.$endpoint;
				}
				
				$fp = @fsockopen($sock_endpoint, $port, $errno, $errorstr, 60);
			}
			catch (Exception $ex) {}
			if (!$fp)
				throw new Phpr_SystemException("Error number: $errno, error: $errorstr");

			if (is_array($fields))
			{
				$poststring = array();
				foreach($fields as $key=>$val)
				    $poststring[] = urlencode($key)."=".urlencode($val); 
			
				$poststring = implode('&', $poststring);
			} else
				$poststring = $fields;

			fputs($fp, "POST $url HTTP/1.1\r\n"); 
			fputs($fp, "Host: $endpoint\r\n"); 
			fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n"); 
			fputs($fp, "Content-length: ".strlen($poststring)."\r\n"); 
			fputs($fp, "Connection: close\r\n\r\n"); 
			fputs($fp, $poststring . "\r\n\r\n"); 

			$response = null;
			while(!feof($fp))
				$response .= fgets($fp, 4096);
				
			return $response;
		}

		/**
		 * Accepts an HTTP response string and returns fields it contains as array
		 * @param string $response Specifies a response string to parse
		 * @return array Returns an array of fields
		 */
		public static function parse_http_response($response)
		{
			$matches = array();
			preg_match('/Content\-Length:\s([0-9]+)/i', $response, $matches);
			if (!count($matches))
				throw new Phpr_ApplicationException('Invalid response');

			$elements = substr($response, $matches[1]*-1);
			$elements = explode('&', $elements);

			$result = array();
			foreach ($elements as $element)
			{
				$element = explode('=', $element);
				if (isset($element[0]) && isset($element[1]))
					$result[$element[0]] = urldecode($element[1]);
			}

			return $result;
		}
		
		public static function parce_http_response($response) { // deprecated
			return self::parse_http_response($response);
		}
		
		/**
		 * Posts data to the specified LemonStand URL and returns the response string
		 * @param string $url Specifies the URL relative to the LemonStand root, for example /blog
		 */
		public static function sub_request($url, $fields, $timeout = 60)
		{
			$ch = @curl_init(root_url(Core_String::normalizeUri($url), true));

			@curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			@curl_setopt($ch, CURLOPT_POST, 1);
			@curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			@curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			@curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); 
			
			if (isset($_SERVER['HTTP_COOKIE']))
				@curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE']);

			@curl_setopt($ch, CURLOPT_HTTPHEADER, array('LS_SUBQUERY: 1'));

			@session_write_close();

			$result = curl_exec($ch);
			
			return $result;
		}
	}

?>