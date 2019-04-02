<?php

	/**
	 * Sends email messages.
	 * LemonStand uses this class for sending email notifications to customers and back-end users.
	 * @has_documentable_methods
	 * @see http://lemonstandapp.com/docs/caching_api Caching API
	 * @author LemonStand eCommerce Inc.
	 * @package core.classes
	 */
	class Core_Email
	{
		/**
		 * Sends an email message.
		 * @param string $module_id Specifies an identifier of a module the email view belongs to
		 * @param string $view Specifies a name of the email view file
		 * @param mixed $viewData Message-specific information to be passed into a email view
		 * @param string $subject Specifies a message subject
		 * @param string $recipientName Specifies a name of a recipient
		 * @param string $recipientEmail Specifies an email address of a recipient
		 * @param mixed $attachments A list of file attachemnts in format path=>name
		 */
		public static function send( $moduleId, $view, &$viewData, $subject, $recipientName, $recipientEmail, $recipients = array(), $settingsObj = null, $replyTo = array(), $attachments = array() )
		{
			if (!$settingsObj)
				$settings = System_EmailParams::get();
			else
				$settings = $settingsObj;

			if (!$settings)
				throw new Phpr_SystemException( "Email system is not configured." );

			if (!$settings->isConfigured())
				throw new Phpr_SystemException( "Email system is not configured." );

			/**
			 * Load the view contents
			 */
			$Wrapper = new Core_EmailViewWrapper( $moduleId, $view, $viewData );

			/*
			 * Send the message
			 */
			require_once PATH_APP."/modules/core/thirdpart/phpmailer/autoload.php";

			$Mail = new PHPMailer();

			$Mail->Encoding = "8bit";
			$Mail->CharSet = "utf-8";
			$Mail->From = $settings->sender_email;
			$Mail->FromName = $settings->sender_name;
			$Mail->Sender = $settings->sender_email;
			$Mail->Subject = $subject;
			$Mail->WordWrap = 0;

			if ($replyTo)
			{
				foreach ($replyTo as $address=>$name)
					$Mail->AddReplyTo($address, $name);
			}

			$settings->configure_mailer($Mail);

			$Mail->IsHTML(true);

			$Wrapper->ViewData['RecipientName'] = $recipientName;
			$HtmlBody = $Wrapper->execute();
			
			/* 
			 * Apply common email variables
			 */
			
			foreach ($attachments as $file_path=>$file_name)
				$Mail->AddAttachment($file_path, $file_name);

			/*
			 * Format the message and send
			 */

			$Mail->ClearAddresses();

			$external_recipients = array();
			if ( !count($recipients) )
			{
				$Mail->AddAddress($recipientEmail, $recipientName);
				$external_recipients[$recipientEmail] = $recipientName;
			}

			foreach ( $recipients as $Recipient=>$Email )
			{
				if (!is_object($Email))
				{
					$Mail->AddAddress($Email, $Recipient);
					$external_recipients[$Email] = $Recipient;
				}
				elseif ($Email instanceof Phpr_User)
				{
					$Mail->AddAddress($Email->email, $Email->name);
					$external_recipients[$Email->email] = $Email->name;
				}
			}
			
			$HtmlBody = str_replace('{recipient_email}', implode(', ', array_keys($external_recipients)), $HtmlBody);
			$HtmlBody = str_replace('{email_subject}', $subject, $HtmlBody);
			
			$TextBody = trim(strip_tags( preg_replace('|\<style\s*[^\>]*\>[^\<]*\</style\>|m', '', $HtmlBody) ));
			
			$Mail->Body = $HtmlBody;
			$Mail->AltBody = $TextBody;
			
			$custom_data = array_key_exists('custom_data', $viewData) ? $viewData['custom_data'] : null;
			
			$external_sender_params = array(
				'content'=>$HtmlBody,
				'reply_to'=>$replyTo,
				'attachments'=>$attachments,
				'recipients'=>$external_recipients,
				'from'=>$settings->sender_email,
				'from_name'=>$settings->sender_name,
				'sender'=>$settings->sender_email,
				'subject'=>$subject,
				'data'=>$custom_data
			);

			$external_sender_params = (object)$external_sender_params;
			$send_result = Backend::$events->fireEvent('core:onSendEmail', $external_sender_params);
			foreach ($send_result as $result)
			{
				if ($result)
					return $send_result;
			}

			if ( !$Mail->Send() )
				throw new Phpr_SystemException( 'Error sending message '.$subject.': '.$Mail->ErrorInfo );
		}
		
		public static function sendOne($moduleId, $view, &$viewData, $subject, $userId)
		{
			$result = false;
			
			try
			{
				$user = is_object($userId) ? $userId : Users_User::create()->find($userId);
				if (!$user)
					return;
				
				self::send($moduleId, $view, $viewData, $subject, $user->short_name, $user->email);
				return true;
			}
			catch (Exception $ex)
			{
			}
			
			return false;
		}
		
		public static function sendToList($moduleId, $view, &$viewData, $subject, $recipients, $throw = false, $replyTo = array(), $settingsObj = null)
		{
			try
			{
				if (is_array($recipients) && !count($recipients))
					return;
					
				if (is_object($recipients) && !$recipients->count)
					return;
				
				self::send($moduleId, $view, $viewData, $subject, null, null, $recipients, $settingsObj, $replyTo);
			}
			catch (Exception $ex)
			{
				if ($throw)
					throw $ex;
			}
		}
		
		/*
		 * Event descriptions
		 */

		/**
		 * Triggered before LemonStand sends an email message. 
		 * You can use this event to send email messages with third-party software or services. 
		 * The event handler should accept a single parameter - the object containing information about the message to be sent. 
		 * The object has the following fields:
		 * <ul>
		 * <li><em>content</em> - the message content in HTML format.</li>
		 * <li><em>reply_to</em> - an array containing the reply-to address: array('sales@example.com'=>'Sales Department').</li>
		 * <li><em>attachments</em> - an array containing a list of paths to the attachment files.</li>
		 * <li><em>recipients</em> - an array containing a list of recipients: array('demo@example.com'=>'Demo User').</li>
		 * <li><em>from</em> - "from" email address.</li>
		 * <li><em>from_name</em> - "from" name.</li>
		 * <li><em>sender</em> - email sender email address.</li>
		 * <li><em>subject</em> - message subject.</li>
		 * <li><em>data</em> - custom parameters which could be passed by the email sending code.</li>
		 * </ul>
		 * The event handler should return TRUE in order to prevent the default message sending way. Example event handler:
		 * <pre>
		 * public function subscribeEvents()
		 * {
		 *   Backend::$events->addEvent('core:onSendEmail', $this, 'send_email');
		 * }
		 * 
		 * public function send_email($email_info)
		 * {
		 *   //
		 *   // Send the message using the external service
		 *   //
		 *   
		 *   ...
		 *   
		 *   //
		 *   // Return TRUE to stop LemonStand from sending the message
		 *   //
		 *   
		 *   return true;
		 * }
		 * </pre>
		 *
		 * @event core:onSendEmail
		 * @package core.events
		 * @author LemonStand eCommerce Inc.
		 * @param array $params An list of the method parameters.
		 * @return boolean Return TRUE if the default message sending should be stopped. Returns FALSE otherwise.
		 */
		private function event_onSendEmail($params) {}
	}

?>