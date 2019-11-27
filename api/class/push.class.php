<?php

	class push_notification {

		/*public function push($pushID, $message, $type) {
			$GOOGLE_API_KEY = "AIzaSyCnqDao2_Ph7x7GXa7IAxs5HtDm16efowQ";
			$url = 'https://android.googleapis.com/gcm/send';
 
			$fields = array(
				'content_available' => true,
				'registration_ids' => $pushID,
				'notification' => array(
					'sound' => 'default',
					'badge' => '1',
					'body' => htmlspecialchars_decode($message, ENT_QUOTES),
					'title' => 'Bench',
					'click_action' => 'OPEN_MAIN_ACTIVITY',
					'icon' => 'notification_icon_s'
				),
				'data' => array(
					'message' => $message,
					'title' => 'Bench'
				),
				'priority' => 'high'
			);

			$headers = array(
				'Authorization: key=' . $GOOGLE_API_KEY,
				'Content-Type: application/json'
			);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

			$result = curl_exec($ch);
			if ($result === FALSE) {
				die('Curl failed: ' . curl_error($ch));
			} else {
				return "Success";
			}

			curl_close($ch);

		}*/

		public function push($pushID, $message, $type) {
			$message = str_replace("&#34;", '"', $message);
			$message = str_replace("&#39;", "'", $message);
			// $GOOGLE_API_KEY = "AIzaSyCjiEZBF2EWmY7IX9xCv6-fdt_j-gw9ZV8";
			// $url = 'https://android.googleapis.com/gcm/send';
			$GOOGLE_API_KEY = "AAAAxQUBz8Y:APA91bGZHcTKWCqWvvSht4qwEYPNhYH6AjMkjXeujByvyPyeYwR0WoKnAocGIUGyMw_SMxMn8BysQXVKEiSI3dHMs9F4lKEAfxp7gQKuftcR82OemnogrtXXaqcARXf-pRQtdTNw__ip";
			$url = 'https://fcm.googleapis.com/fcm/send';

			$fields = array(
				'content_available' => true,
				'registration_ids' => $pushID,
				'notification' => array(
					'sound' => 'default',
					'badge' => '1',
					'body' => htmlspecialchars_decode($message, ENT_QUOTES),
					'title' => MERCHANT_APPNAME,
					'click_action' => 'OPEN_MAIN_ACTIVITY',
					'icon' => 'notification_icon_s'
				),
				'data' => array(
					'message' => $message,
					'title' => MERCHANT_APPNAME
				),
				'priority' => 'high'
			);

			$headers = array(
				'Authorization: key=' . $GOOGLE_API_KEY,
				'Content-Type: application/json'
			);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

			$result = curl_exec($ch);
			if ($result === FALSE) {
				die('Curl failed: ' . curl_error($ch));
			} else {
				return "Success";
			}

			curl_close($ch);
		}

	}

?>