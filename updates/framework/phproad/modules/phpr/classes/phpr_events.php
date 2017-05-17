<?

	/*
	 * Events extension
	 * 
	 * Firing: $this->fireEvent('onEventName', $Param1);
	 * Handling: 
	 * Function handler: $Tester->addEvent('OnAfterShow', 'handlerFunction');
	 * Function handler with extra params: $Tester->addEvent('OnAfterShow', pass('handlerFunctionExt', '3.14'));
	 * Class method handler: $Tester->addEvent('OnAfterShow', $Reciever, 'onShowMessage');
	 * Class method handler with extra params: $Tester->addEvent('OnAfterShow', pass($Reciever, 'onShowMessageExt', '3.14'));
	 */
	class Phpr_Events extends Phpr_Extension {
		public $events = array();
		public $events_disabled = false;
		
		public function add_event($options = array()) {
			if(is_string($options)) { // backwards compat with addEvent's first param being the event name
				$options = array(
					'name' => $options
				);
			}
			else {
				$options = array_merge(array(
					'name' => null
				), $options);
			}
			
			extract($options);
			
			$args = func_get_args();
			$handler = extractFunctionArg($args, 1);
			
			if(count($args) > 0)
				$priority = (int)$args[0];
			else
				$priority = 500;

			if(!isset($this->events[$name]))
				$this->events[$name] = array();

			$this->events[$name][] = array('handler' => $handler, 'priority' => $priority);
		}

		public function fire_event($options = array()) {
			if(is_string($options)) { // backwards compat with addEvent's first param being the event name
				$options = array(
					'name' => $options, 
					'type' => 'combine'
				);
			}
			else {
				$options = array_merge(array(
					'name' => null, 
					'type' => 'combine'
				), $options);
			}
		
			extract($options);

			$params = func_get_args();
			
			array_shift($params);
			
			if($this->events_disabled || !isset($this->events[$name]))
				if($type === 'combine')
					return array(); // backwards compat
				else if($type === 'filter' || $type === 'update_result')
					return count($params) > 0 ? $params[0] : null;
				else
					return null;

			uasort($this->events[$name], array($this, 'sort_by_priority'));
					
			if($type === 'combine') {
				$result = array();
				
				foreach($this->events[$name] as $event) {
					$result[] = callFunction($event['handler'], $params);
				}
			}
			else if($type === 'filter') {
				$result = count($params) > 0 ? $params[0] : null;
				foreach($this->events[$name] as $event) {
					$result = callFunction($event['handler'], array($result));
				}
			}
			else if($type === 'update_result') {
				$result = count($params) > 0 ? $params[0] : null;
				$args = count($params) > 1 ? $params[1] : array();
				foreach($this->events[$name] as $event) {
					$result = callFunction($event['handler'], array($result,$args));
				}
			} else {
				$result = null;
			}
			
			return $result;
		}
		
		public function remove_event_by_handler_substring($handler_substring)
		{
			$events = array();
			foreach ($this->events as $event_name=>$handlers)
			{
				$event_handlers = array();
				foreach ($handlers as $handler_data)
				{
					if (!isset($handler_data['handler'][1]) || strpos($handler_data['handler'][1], $handler_substring) === false)
						$event_handlers[] = $handler_data;
				}
				
				$events[$event_name] = $event_handlers;
			}
			
			$this->events = $events;
		}
		
		public function listeners_exist() {
			$listeners = func_get_args();
			foreach ($listeners as $name)
				if (array_key_exists($name, $this->events))
					return true;
					
			return false;
		}
		
		private function sort_by_priority($a, $b) {
			if($a['priority'] == $b['priority']) {
 				return 0;
			}
			
			return ($a['priority'] < $b['priority']) ? 1 : -1;
		}
		
		/**
		 * @deprecated
		 */
		public function addEvent($name) {
			$params = func_get_args();
			$params[0] = array('name' => $name);
			
			return callFunction(array($this, 'add_event'), $params);
		}
		
		/**
		 * @deprecated
		 */
		public function fireEvent($name) {
			$params = func_get_args();
			$params[0] = array('name' => $name);
			
			return callFunction(array($this, 'fire_event'), $params);
		}
	}