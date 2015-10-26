<?

	/**
	 * Command-line helper functions
	 */
	class Core_Cli
	{
		public static function print_line($str = '')
		{
			fwrite(STDOUT, $str."\n"); 
		}

		public static function print_str($str = '')
		{
			fwrite(STDOUT, $str); 
		}

		public static function print_error($str)
		{
			fwrite(STDOUT, "\033[31m"); 
			fwrite(STDOUT, 'ERROR: '.$str."\n"); 
			fwrite(STDOUT, "\033[0m");
		}

		public static function print_warning($str)
		{
			fwrite(STDOUT, "\033[31m"); 
			fwrite(STDOUT, 'WARNING: '.$str."\n"); 
			fwrite(STDOUT, "\033[0m");
		}

		public static function read_line()
		{
			return fgets(STDIN); 
		}
		
		public static function authenticate()
		{
			Core_Cli::print_line();
			Core_Cli::print_line('LemonStand Command-Line Interface');
			Core_Cli::print_line();
			Core_Cli::print_line('Please enter your user name and password.');

			$username = self::read_option("User name: ");
			$password = self::read_option("Password: ");
			
			$user = Users_User::create()->findUser($username, $password);
			if (!$user)
			{
				self::print_error('We are sorry, you have no permissions to access the command-line interface.');
				exit(0);
			}

			if (!$user->is_administrator())
			{
				self::print_error('We are sorry, only administrators can access the command-line interface.');
				exit(0);
			}
		}

		public static function read_option($label, $required_message = null)
		{
			while (true)
			{
				self::print_str($label);
				$value = trim(self::read_line());

				if (!strlen($value))
				{
					if ($required_message === null)
						return $value;
					else
						self::print_line($required_message);
				}
					else return $value;
			}
		}

		public static function read_bool_option($label)
		{
			while (true)
			{
				self::print_str($label);
				$value = strtolower(trim(self::read_line()));

				if (!strlen($value))
					self::print_line("Please enter Y or N and press the Return key");
				else
				{
					if ($value == 'y')
						return true;

					if ($value == 'n')
						return false;

					self::print_line("Please enter Y or N and press the Return key");
				}
			}
		}
	}

?>