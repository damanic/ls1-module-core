<?php

	class Core_EulaInfo extends Db_ActiveRecord
	{
		public $table_name = 'core_eula_info';

		public static function create()
		{
			return new self();
		}

		public static function update_info($text)
		{
			$obj = self::get();
				
			$obj->agreement_text = $text;
			
			$current_user = Phpr::$security->getUser();
			$obj->accepted_by = $current_user ? $current_user->id : null;
			$obj->accepted_on = Phpr_DateTime::now();
			$obj->save();
			
			Db_DbHelper::query('delete from core_eula_unread_users');
			$users = Users_User::listAdministrators();
			foreach ($users as $user)
			{
				if ($current_user && $current_user->id == $user->id)
					continue;
				
				Db_DbHelper::query('insert into core_eula_unread_users(user_id) values (:user_id)', array('user_id'=>$user->id));
			}
		}
		
		public static function get()
		{
			$obj = self::create();
			if ($existing = $obj->find())
				return $existing;
				
			return $obj;
		}
		
		public function get_accepted_user_name()
		{
			if (!$this->accepted_by)
				return null;
			
			$user = Users_User::create()->find($this->accepted_by);
			if (!$user)
				return 'Unknown user';
				
			return $user->name;
		}
		
		public static function is_unread($user_id)
		{
			return Db_DbHelper::scalar('select count(*) from core_eula_unread_users where user_id=:id', array('id'=>$user_id)) > 0;
		}
		
		public static function mark_read($user_id)
		{
			Db_DbHelper::query('delete from core_eula_unread_users where user_id=:id', array('id'=>$user_id));
		}
	}
	
?>