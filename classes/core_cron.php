<?php
class Core_Cron{

	public static $execute_cron_time_limit_seconds = null;
	public static $execute_cron_start_time = null;

	public static $cronjob_batch_size = null;
	public static $cronjob_batch_shuffle = null;
	public static $cronjob_max_duration_seconds = 3600; //one hour
	public static $cronjob_timeout_retries = 5;

	public static $tracelog_events = false;

	public static function update_interval($code, $now=null)
	{
		$bind = array(
			'record_code' => $code,
			'now' => $now ? $now : Phpr_DateTime::now()->toSqlDateTime()
		);
		Db_DbHelper::query('insert into core_cron_table (record_code, updated_at) values (:record_code, :now) on duplicate key update updated_at =:now', $bind);
	}

	public static function get_interval($code)
	{
		$interval = Db_DbHelper::scalar('select GREATEST(COALESCE(updated_at, 0),COALESCE(postpone_until, 0)) AS run_check from core_cron_table where record_code =:record_code', array('record_code'=>$code));
		if (!$interval)
		{
			self::update_interval($code);
			$interval = Phpr_DateTime::now()->toSqlDateTime();
		}

		return $interval;
	}


	/**
	 * Postpone CRON TAB
	 * @param string $code The record code for the crontab task you would like to defer
	 * @param Phpr_DateTime $datetime The datetime the crontab task should be deferred until
	 * @return void
	 */
	public static function postpone_until($code, $datetime=null)
	{
		$bind = array(
			'record_code' => $code,
			'datetime' => $datetime ? $datetime : Phpr_DateTime::now()
		);
		Db_DbHelper::query('insert into core_cron_table (record_code, postpone_until) values (:record_code, :datetime) on duplicate key update postpone_until =:datetime', $bind);
	}


	/**
	 * Execute CRON tasks
	 *
	 * This will process tabs and all job ques by default
	 * You can choose to process just tabs or just jobs by passing TRUE/FALSE in the parameters
	 * You can also process jobs in a specific que by passing the que_name (string) to the $process_job_que parameter
	 *
	 * @documentable
	 *
	 * @param bool $process_tabs Set to True if the cron table should be processed on this run
	 * @param mixed $process_job_que Set to TRUE to process jobs in all ques OR FALSE to skip jobs on this run.  OR pass a que name (string) to process jobs in a named que.
	 * @return void
	 */
	public static function execute_cron($process_tabs=true, $process_job_que=true)
	{

		//
		// TIME LIMIT AWARENESS
		//
		self::$execute_cron_start_time = time();
		$time_limit = self::$execute_cron_time_limit_seconds;
		if(!$time_limit) {
			self::$execute_cron_time_limit_seconds = (int) ini_get( 'max_execution_time' );
		}

		try {

			if ( $process_job_que !== false ){
				//
				// CRON JOBS
				// one off executions
				//
				$job_batch_size = self::$cronjob_batch_size;
				if ( !$job_batch_size ) {
					$job_batch_size = Phpr::$config->get( 'CRON_JOB_BATCH_SIZE', false );
				}
				$job_batch_size    = is_numeric( $job_batch_size ) ? $job_batch_size : 5;
				$job_batch_shuffle = self::$cronjob_batch_shuffle ? true : false;
				$job_que_name      = is_string($process_job_que) ? $process_job_que : null;
				self::execute_cronjobs( $job_batch_size, $job_que_name, $job_batch_shuffle );
			}

			if($process_tabs) {
				//
				// CRON TABS
				// regular executions
				//
				self::execute_crontabs();
			}

		}
		catch (Exception $ex) {
			if(self::$tracelog_events){
				traceLog('Cron Exception : '. $ex->getMessage());
			}
			Backend::$events->fire_event('core:on_execute_cron_exception',$ex);
		}

		self::reconcile_cronjobs();
	}


	/**
	 * QUE A CRON JOB
	 * Adds a one off job to a job que for background processing
	 *
	 * WARNING: The job que is not strictly sequential.
	 * It uses a 'Optimistic Locking' strategy for broader database support
	 * and will send jobs to the back of the que if retries are enabled.
	 *
	 * A retry will only be qued when $retry_on_fail is set to TRUE and the handler method returns FALSE explicitly
	 *
	 *  Options
	 * 	'available_at' : Specify a Phr_DateTime after which the job should be picked up
	 *  'que_name' : Add the job to a specific que identifier
	 *
	 * Usage example:
	 * <pre>
	 * 	<?
	 *  	 Core_Cron::queue_job('User_Model::static_method', array('param1', 'param2', 'param3'));
	 * 	?>
	 * </pre>
	 * Executes:
	 *  User_Model::static_method('param1', 'param2', 'param3');
	 *
	 * @documentable
	 *
	 * @param string $handler_name The class::static_method that should be called
     * @param array $param_data An array of parameters that should be sent to the handler method
     * @param bool $retry_on_fail When set to TRUE the called handler method can return FALSE to re-que the job
	 * @param bool $allow_duplicate When set to FALSE, attempts to add a job that already exists will be ignored
	 * @param array $options Options.
	 * @return void
	 */
	public static function queue_job($handler_name, $param_data=array(), $retry_on_fail=false, $allow_duplicate = true , $options = array())
	{

		$default_options = array(
			'available_at' => null,
			'que_name' => null
		);

		$options = array_merge($default_options, $options);
		$que_name = is_string($options['que_name']) ? $options['que_name'] : null;
		$available_at = $options['available_at'];
		if($available_at){
			if(is_a($available_at, 'Phpr_DateTime')){
				$available_at = $available_at->toSqlDateTime();
			} else {
				$available_at = null;
			}
		}

		$bind = array(
			'handler_name' => $handler_name,
			'param_data' => serialize($param_data),
			'now' => Phpr_DateTime::now()->toSqlDateTime(),
			'retry' => $retry_on_fail ? 1 : null,
			'version' => 1,
			'attempts' => 0,
			'available_at' => $available_at ? $available_at : null,
			'que_name' => $que_name
		);


		if(!$allow_duplicate){
			$check_sql = "SELECT id FROM core_cron_jobs
				WHERE handler_name = :handler_name 
				AND param_data = :param_data LIMIT 1";
			$exists_id = Db_DbHelper::scalar($check_sql, $bind);
			if($exists_id){
				return; //identical job already in the que
			}
		}

		$insert_sql = "INSERT INTO core_cron_jobs 
    				   (que_name, handler_name, param_data, created_at, retry, version, attempts, available_at) 
    				   VALUES (:que_name, :handler_name, :param_data, :now, :retry, :version, :attempts, :available_at)";
		Db_DbHelper::query($insert_sql, $bind);
	}

	public static function execute_cronjobs($limit=5, $que_name=null, $shuffle=false)
	{
		$limit = is_numeric($limit) ? $limit : 5;
		$where = "WHERE (started_at IS NULL AND (available_at <= NOW() || available_at IS NULL))";
		if(!empty($que_name)){
			$where .= " AND que_name = ?";
		}
		$sql = "SELECT * FROM core_cron_jobs 
				$where
				ORDER BY attempts, id ASC
				LIMIT $limit";
		$jobs = Db_DbHelper::objectArray( $sql, $que_name );

		if(!$jobs){
			return;
		}

		if($shuffle){
			shuffle($jobs); //helps to avoid collisions when running multi thread calls
		}

		$count = 0;
		foreach($jobs as $job){


			if(!self::cron_has_time()){
				break;
			}

			$bind = array(
				'id' => $job->id,
				'version' => $job->version
			);

			//update version if not already taken
			$processing_sql = "UPDATE core_cron_jobs 
							   SET started_at = NOW(), attempts = attempts + 1, version = version + 1 
							   WHERE id = :id AND version=:version";
			$db = Db_Sql::create();
			$db->query($db->prepare($processing_sql, $bind));
			$can_process = ($db->row_count() == 1) ? true : false; //if successful we have the job

			if($can_process){
				$job->version++;
				$result = null;
				$retry = $job->retry;

				try {
					$executable = self::get_job_executable($job);
					if ( $executable  ) {
						register_shutdown_function(array('Core_Cron', '_on_job_shutdown'), $job);
						$result = call_user_func_array( array( $executable['class'], $executable['method'] ), $executable['params'] );
					} else {
						$retry = false; //not executable no point retrying
						throw new Phpr_ApplicationException('Cron Job is not executable');
					}
				} catch ( Exception $ex ) {
					if(self::$tracelog_events){
						traceLog('Cronjob Exception ['.$job->handler_name.'] - '. $ex->getMessage());
					}
					Backend::$events->fire_event( 'core:on_execute_cronjob_exception', $ex, $job );
				}

				if ( $retry && $result === false ) {
					//send job to back of que, try again later
					$processing_sql = "UPDATE core_cron_jobs 
											       SET started_at=NULL 
											       WHERE id = :id";
					Db_DbHelper::query( $processing_sql, $bind );
					continue;
				}

				//no further processing required for this job -> delete
				self::delete_job_version($job);
			}
		}
	}

	public static function execute_crontabs()
	{

		$modules = Core_ModuleManager::listModules();
		foreach ($modules as $module)
		{
			if(!method_exists($module, 'subscribe_crontab'))
				continue;

			$module_id = $module->getId();
			$cron_items = $module->subscribe_crontab();

			if (!is_array($cron_items))
				continue;

			foreach ($cron_items as $code=>$options)
			{
				$code = $module_id . '_' . $code;
				if (!isset($options['interval']) || !isset($options['method']))
					continue;


				$now = Phpr_DateTime::now();
				$last_exec = Phpr_DateTime::parse(self::get_interval($code), Phpr_DateTime::universalDateTimeFormat);
				$next_exec = $last_exec->addMinutes($options['interval']);
				$can_execute = $now->compare($next_exec);

				if ($can_execute == -1)
					continue;

				if(!self::cron_has_time()){
					break;
				}

				try
				{
					self::update_interval( $code );//set last run to now to help prevent repeat triggers on long tasks
					$method = $options['method'];
					if ($module->$method()){
						self::update_interval( $code ); //time of completion
					} else {
						self::update_interval( $code, $last_exec ); //not run, revert to last completed time
					}

				}
				catch (Exception $ex)
				{
					if(self::$tracelog_events){
						traceLog('Crontab exception on ['.$code.'] : '. $ex->getMessage());
					}
					Backend::$events->fire_event('core:on_execute_crontab_exception', $ex, $code);
				}
			}
		}
	}

	protected static function cron_has_time(){
		$start_time = self::$execute_cron_start_time;
		$time_limit = self::$execute_cron_time_limit_seconds;
		$safe_allowance = 0.8;

		if(!is_numeric($start_time) || !is_numeric($time_limit) || $time_limit == 0){
			return true;
		}
		$safe_limit = round($time_limit * $safe_allowance);

		if((time() - $start_time) >= $safe_limit){
			return false;
		}

		return true;
	}

	protected static function get_job_executable($job){
		$params = $job->param_data ? unserialize($job->param_data) : array();
		$parts = explode('::', $job->handler_name);
		if ( count( $parts ) < 1 ) {
			return false;
		}

		$model_class = $parts[0];
		if ( !isset( $parts[1] ) ) {
			return false;
		}

		$method_name = $parts[1];

		if (!method_exists($model_class, $method_name)){
			return false;
		}

		return array(
			'method' => $method_name,
			'class' => $model_class,
			'params' => $params
		);
	}

	protected static function reconcile_cronjobs(){

		//remove jobs that are taking too long to process or failing to clear out of the que
		$max_job_duration= self::$cronjob_max_duration_seconds;
		if($max_job_duration && is_numeric($max_job_duration)) {
			$jobs_sql = "SELECT id FROM core_cron_jobs 
						 WHERE started_at IS NOT NULL 
						 AND started_at < NOW() - INTERVAL ? SECOND";
			$jobs = Db_DbHelper::objectArray($jobs_sql, $max_job_duration);
			if($jobs){
				shuffle($jobs);
				foreach($jobs as $job){
					self::handle_timeout_cronjob($job);
				}
			}
		}

	}

	//cronjobs that cause fatal errors are deleted from the que
	public static function _on_job_shutdown($job){

		if($job && $job->id) {
			$error = error_get_last();
			if ( $error ) {

			$bind = array(
				'id' => $job->id,
				'version' => $job->version
			);
			$job_processing = Db_DbHelper::scalar( 'SELECT id FROM core_cron_jobs 
													WHERE started_at IS NOT NULL
													AND id = :id
													AND version = :version', $bind);

			//Take action if job is still locked on version
			if($job_processing && ($job_processing == $job->id)){

				//max execution limits can end up here if script limit is lower than global
				if($error && isset($error['message'])){
					if(stristr($error['message'],'Maximum execution time of')){
						self::handle_timeout_cronjob($job);
						return;
					}
				}

				//all other shutdowns are considered fatal, unrecoverable
				self::delete_job_version($job);
			}

			//notify
			if(self::$tracelog_events){
				$error_message = isset($error['message']) ? $error['message'] : 'fatal error';
					traceLog( 'Cronjob shutdown on [' . $job->handler_name . '] [' . memory_get_usage() . '] : ' . $error_message );
					traceLog( $error );
			}
			Backend::$events->fire_event( 'core:on_cronjob_shutdown', $job, $error );
			}
		}
	}

	protected static function handle_timeout_cronjob($job){
		if($job->attempts >= self::$cronjob_timeout_retries) {
			self::delete_job_version($job);
		} else {
			Db_DbHelper::query('UPDATE core_cron_jobs SET started_at = NULL WHERE id = ?', $job->id); //allow retry
		}
		if(self::$tracelog_events){
			traceLog('Cronjob timeout on ['.$job->handler_name.']');
		}
		Backend::$events->fire_event( 'core:on_cronjob_exceeded_max_duration', $job );
	}

	protected static function delete_job_version($job){
		$bind = array(
			'id' => $job->id,
			'version' => $job->version
		);
		Db_DbHelper::query('DELETE FROM core_cron_jobs WHERE id = :id AND version = :version', $bind);
	}


	/**
	 * Triggered when an exception occurs on execute_cron()
	 * @event core:on_execute_cron_exception
	 * @package core.events
	 * @author Matt Manning (github:damanic)
	 * @param Exception $ex The exception returned
	 * return void
	 */
	private function event_on_execute_cron_exception($ex) {}

	/**
	 * Triggered when an exception occurs on execute_cronjobs()
	 * @event core:on_execute_cronjob_exception
	 * @package core.events
	 * @author Matt Manning (github:damanic)
	 * @param Exception $ex The exception returned
	 * @param object $job The job that caused the exception
	 * return void
	 */
	private function event_on_execute_cronjob_exception($ex, $job) {}

	/**
	 * Triggered when an exception occurs on execute_crontabs()
	 * @event core:on_execute_crontab_exception
	 * @package core.events
	 * @author Matt Manning (github:damanic)
	 * @param Exception $ex The exception returned
	 * @param string $record_code The record code for the crontab
	 * return void
	 */
	private function event_on_execute_crontab_exception($ex, $record_code) {}

	/**
	 * Triggered when a cronjob triggers an unexpected shutdown (fatal error)
	 * @event core:on_cronjob_shutdown
	 * @package core.events
	 * @author Matt Manning (github:damanic)
	 * @param object $job The job that caused the shutdown
	 * @param array $error The last php error returned from error_get_last()
	 * return void
	 */
	private function event_on_cronjob_shutdown($job, $error) {}

	/**
	 * Triggered when a cronjob timesout before completion.
	 * @event core:on_cronjob_exceeded_max_duration
	 * @package core.events
	 * @author Matt Manning (github:damanic)
	 * @param object $job The job that caused the shutdown
	 * return void
	 */
	private function event_on_cronjob_exceeded_max_duration($job) {}

}


