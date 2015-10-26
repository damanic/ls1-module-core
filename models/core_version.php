<?php

	class Core_Version extends Db_ActiveRecord
	{
		public $table_name = 'core_versions';
		
		private static $build_cache = null;
		private static $db_version_string = null;
		
		public static function create()
		{
			return new self();
		}

		public static function getModuleVersion($module_id)
		{
			$module_id = mb_strtolower($module_id);

			$version = self::create()->find_by_moduleId($module_id);
			if ($version)
				return $version->version_str;
				
			return '1.0.0';
		}
		
		public static function getModuleBuild($module_id)
		{
			$module_id = mb_strtolower($module_id);

			$version = self::create()->find_by_moduleId($module_id);
			if ($version)
				return $version->version;

			return 0;
		}
		
		public static function getModuleVersionCached($module_id)
		{
			if (self::$build_cache != null)
				return array_key_exists($module_id, self::$build_cache) ? self::$build_cache[$module_id] : 0;
				
			self::$build_cache = array();
			$versions = Db_DbHelper::objectArray('select * from core_versions');
			foreach ($versions as $version)
				self::$build_cache[$version->moduleId] = $version->version_str;
				
			return array_key_exists($module_id, self::$build_cache) ? self::$build_cache[$module_id] : 0;
		}
		
		public static function getModuleBuildsString()
		{
			if (self::$db_version_string === null)
				return self::$db_version_string = Db_DbHelper::scalar("select group_concat(version order by moduleId separator '|') from core_versions");
				
			return self::$db_version_string;
		}
	}

?>