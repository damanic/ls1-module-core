<?

	class Core_CronManager
	{
		public static function access_allowed()
		{
			$ip = Phpr::$request->getUserIp();
			$allowed_ips = Phpr::$config->get('CRON_ALLOWED_IPS', array());

			try
			{
				if (!in_array($ip, $allowed_ips))
					throw new Phpr_SystemException('Cron access from the IP address '.$ip.' is denied.');
			} catch (Exception $ex)
			{
				echo "Error. ".h($ex->getMessage());
				return false;
			}
			
			return true;
		}

	}

?>