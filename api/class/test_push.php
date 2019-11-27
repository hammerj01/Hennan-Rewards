<?php
	$pushID = array(
		// "fudtqp_efXI:APA91bEM-qvdaJM7ZGknLN7wAGNphUNo2jQNwCVyODz8oSeXa65xsMZc8RNb4iZi9jDBpUbNW-sV5F3VnoNAUTKwC01aEH1_w6t5-cUzB-mqrR9iiz3Mwc9ArFjdV36D1jIDS8jjc9Bh",
		// "ftjJTNYNREw:APA91bGv7rB_iL-LZ5NLd5AowPA_oKWaonPVMn_LuS3yrNOblT3Bz___kGLSXt72Gcr8Y9YZmFwluz-BsqsMm4_24H8kCv6bxedLBKufIlSANh7EugNsgreVbMvf308U7yKbDOZA6lf1",
		// "cWkFDesBWJc:APA91bHVlSPWq5zWQZZ8tQlAQ-R8YnfGCZwaPKAFH5hCTewcnPJg7aX0BEC-LabL2maEqksckGpZu1xLe1sXlKtv2_dGdukT4CZBNLFvwCs5woLTgqjhUxvnT0rqvaZOqgZdFqeeYPFf"
		// "fiZrlAhC5bg:APA91bFWMdFbfOP7NNIkKW4YWLk6Ik8nJc9iINHvlQOAvzCnbCCK-XAodFaoqRHv3YWgQ6Gdlec8vqIy2Yu-9ncmsKCNXwf5peP4k_CuxbNx2SYn2IITWgLd40arkP5vWTiIJeCSbsPp"
		// "c_LkMLH7RlE:APA91bGn5Nhu3Ake_euevnU6iqZt-IwiAxqK2t30fPL0vi_F5_a2yqL5WwNNfX45mu4ejs1NeCi6a93KT6FUO9vA-_YGrq7Gocbvc2b8wdYqQiLsGejBMAyukRgL7VC8i0KJK1j8PO4N"
		// "feV3-YJt8mQ:APA91bEBXsKAiPc1w-gdNstF-garIi2OarNvf-aHdXAe4fBrD4hzY1GxxzJRWnJbEO5PO6kCeg1JYUs2xaxo_TmIobAn7OJaKiPQs1Z7ND-9qJpUA9glUWy_aWenrOrKg16uXOKB8_Y8"
		"cqzrPeJmg9M:APA91bEyDcxitDueNfakqHSjydA-wwKTAvSGDk9RgFdBZgirc9AXaS6mVUhJUohPV-Y3jxfmyqIyooLT9MJ23gfHAO6ET-MexO8W2MN4b3aJiuH_gvbWLrd9nmhj3K0rxwiBWLg2CR5l"
	);
	$message = "This is only a test. ".date("H:i:s")." ".json_decode('"\uD83D\uDE00"');
	$GOOGLE_API_KEY = "AAAAxQUBz8Y:APA91bGZHcTKWCqWvvSht4qwEYPNhYH6AjMkjXeujByvyPyeYwR0WoKnAocGIUGyMw_SMxMn8BysQXVKEiSI3dHMs9F4lKEAfxp7gQKuftcR82OemnogrtXXaqcARXf-pRQtdTNw__ip";
	$url = 'https://fcm.googleapis.com/fcm/send';

	$fields = array(
		'content_available' => true,
		'registration_ids' => $pushID,
		'notification' => array(
			'sound' => 'default',
			'badge' => '1',
			'body' => htmlspecialchars_decode($message, ENT_QUOTES),
			'title' => 'HENANN APP',
			'click_action' => 'OPEN_MAIN_ACTIVITY',
			'icon' => 'notification_icon_s'
		),
		'data' => array(
			'message' => $message,
			'title' => 'HENANN APP'
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
		echo "Success";
	}

	curl_close($ch);

?>