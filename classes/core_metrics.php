<?
/**
 * Class Core_Metrics
 * @deprecated
 *
 *            This class was used by Lemonstand Inc. to compile usage data and
 * 			  submit a report to their servers.
 *
 *            Communication of these metric are no longer sent to lemonstand servers
 * 			  as they are now offline!
 *
 *            Database logging of these metrics has been disabled by default to improve
 *            site performance.
 *
 *            You can set the config setting 'DISABLE_USAGE_STATISTICS' to true to maintain
 *            database level logging of core metrics.
 *
 */
	class Core_Metrics
	{
		public static function update_metrics()
		{
			if (Phpr::$config->get('DISABLE_USAGE_STATISTICS', true))
				return;


			try
			{
				$stats = Db_DbHelper::object('select 
						core_metrics.*, 
						datediff(CURRENT_DATE(), updated) as update_diff, 
						(NOW()-update_lock) as lock_diff 
					from core_metrics');

				if ($stats->update_diff < 7 || (strlen($stats->lock_diff) && $stats->lock_diff < 120))
					return;

				Db_DbHelper::query('update core_metrics set update_lock=NOW()');
				
				$shipping_module_records = self::extract_shipping_module_usage($stats->updated);
				$payment_module_records = self::extract_payment_module_usage($stats->updated);
				
				$success = false;

				try
				{
					$data = array(
						'views' => $stats->page_views,
						'total' => $stats->total_amount,
						'ord_num' => $stats->total_order_num,
						'sm_usage' => serialize($shipping_module_records),
						'pm_usage' => serialize($payment_module_records)
					);

//					$ch = curl_init('https://v1.lemonstand.com/ls_process_usage_stats/');
//					@curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//					@curl_setopt($ch, CURLOPT_POST, 1);
//					@curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
//					@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//					@curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
//					@curl_setopt($ch, CURLOPT_HTTPHEADER, array('LS_STATS: 1', 'LS-STATS: 1'));
//					@curl_setopt($ch, CURLOPT_TIMEOUT, 5);
//					@curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
//					$result = @curl_exec($ch);
//					$success = $result == 'thanks';
					$success = true;
				} catch (exception $ex) {}
				
				if ($success)
					Db_DbHelper::query('update core_metrics set total_amount=0, total_order_num=0, page_views=0, update_lock=null, updated=NOW()');
				else
					Db_DbHelper::query('update core_metrics set update_lock=null');
			} catch (exception $ex) {}
		}
		
		public static function log_pageview()
		{
			try
			{
				if (Phpr::$config->get('DISABLE_USAGE_STATISTICS', true))
					return;
				
				Db_DbHelper::query('update core_metrics set page_views = page_views+1');
			} catch (exception $ex) {}
		}
		
		public static function log_order($order)
		{
			try
			{
				if (Phpr::$config->get('DISABLE_USAGE_STATISTICS', true))
					return;

				Db_DbHelper::query(
					'update core_metrics set total_order_num = total_order_num+1, total_amount = total_amount + :order_total', 
					array('order_total'=>$order->total)
				);
			} catch (exception $ex) {}
		}
		
		protected static function extract_shipping_module_usage($last_update)
		{
			$shipping_module_records = Db_DbHelper::objectArray('select 
					shop_shipping_options.class_name as class_name,
					count(shop_orders.id) as order_num,
					shop_shipping_options.class_name as module_name
				from 
					shop_shipping_options, 
					shop_orders 
				where 
					shop_orders.shipping_method_id = shop_shipping_options.id
					and shop_orders.order_datetime >= :update_date
				group by shop_shipping_options.class_name', array(
					'update_date'=>$last_update
			));

			foreach ($shipping_module_records as &$record)
			{
				try
				{
					if (class_exists($record->class_name))
					{
						$method = new $record->class_name();
						$info = $method->get_info();
						if (isset($info['name']))
							$record->module_name = $info['name'];
					}
				} catch (exception $ex) {}
			}
			
			return $shipping_module_records;
		}
		
		protected static function extract_payment_module_usage($last_update)
		{
			$payment_module_records = Db_DbHelper::objectArray('select 
					shop_payment_methods.class_name as class_name,
					count(shop_orders.id) as order_num,
					shop_payment_methods.class_name as module_name,
					sum(shop_orders.total) as totals
				from 
					shop_payment_methods, 
					shop_orders 
				where 
					shop_orders.payment_method_id = shop_payment_methods.id
					and shop_orders.order_datetime >= :update_date
				group by shop_payment_methods.class_name', array(
					'update_date'=>$last_update
			));

			foreach ($payment_module_records as &$record)
			{
				try
				{
					if (class_exists($record->class_name))
					{
						$method = new $record->class_name();
						$info = $method->get_info();
						if (isset($info['name']))
							$record->module_name = $info['name'];
					}
				} catch (exception $ex) {}
			}
			
			return $payment_module_records;
		}
	}

?>