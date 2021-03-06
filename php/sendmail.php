<?php
	/**
		* Sets error header and json error message response.
		*
		* @param  String $messsage error message of response
		* @return void
	*/
	function errorResponse ($messsage) {
		header('HTTP/1.1 500 Internal Server Error');
		die(json_encode(array('message' => $messsage)));
	}
	
	/**
		* Pulls posted values for all fields in $fields_req array.
		* If a required field does not have a value, an error response is given.
	*/
	function constructMessageBody () {
		$fields_req =  array("name" => true, "email" => true, "phone" => true, "message" => true);
		$message_body = "";
		foreach ($fields_req as $name => $required) {
			$postedValue = $_POST[$name];
			if ($required && empty($postedValue)) {
				errorResponse("$name is empty.");
				} else {
				$message_body .= ucfirst($name) . ":  " . $postedValue . "\n";
			}
		}
		return $message_body;
	}
	
	header('Content-type: application/json');
	/* 
		//do Captcha check, make sure the submitter is not a robot:)...
		$url = 'https://www.google.com/recaptcha/api/siteverify';
		$opts = array('http' =>
		array(
		'method'  => 'POST',
		'header'  => 'Content-type: application/x-www-form-urlencoded',
		'content' => http_build_query(array('secret' => getenv('RECAPTCHA_SECRET_KEY'), 'response' => $_POST["g-recaptcha-response"]))
		)
		);
		$context  = stream_context_create($opts);
		$result = json_decode(file_get_contents($url, false, $context, -1, 40000));
		
		if (!$result->success) {
		errorResponse('reCAPTCHA checked failed!');
		}
	*/
	
	
	require_once('recaptchalib.php');
	
	// Get a key from https://www.google.com/recaptcha/admin/create
	$publickey = "6Le9mg0TAAAAACRPQcSX2FZS_I-Mz4-fC8fBskYJ";
	$privatekey = "6Le9mg0TAAAAAM1cUyCl5dpMNtOmwPxZHP6ISHL7";
	
	# the response from reCAPTCHA
	$resp = null;
	# the error code from reCAPTCHA, if any
	$error = null;
	
	# was there a reCAPTCHA response?
	if (isset($_POST["recaptcha_response_field"]) && $_POST["recaptcha_response_field"])
	{
        $resp = recaptcha_check_answer ($privatekey,
		$_SERVER["REMOTE_ADDR"],
		$_POST["recaptcha_challenge_field"],
		$_POST["recaptcha_response_field"]);

        if ($resp->is_valid)
		{
			$messageBody = constructMessageBody();
			require dirname(__FILE__).'/vender/php_mailer/PHPMailerAutoload.php';
			
			$mail = new PHPMailer;
			$mail->CharSet = 'UTF-8';
			$mail->isSMTP();
			$mail->SMTPAuth = true;
                        $mail->Host = "localhost";
                      //$mail->Host = "115.112.232.28";
			$mail->Username = "enquiry@maxiscom.com";
			$mail->Password = "SAMSUNG#*1";
			//$mail->AddCC("shankar@maxiscom.com");
			//$mail->SMTPSecure = 'tls';
			//$mail->SMTPSecure = 'ssl';
			// $mail->Port = 587;
			$mail->Port = 25;
			
			$mail->setFrom($_POST['email'], $_POST['name']);
			//$mail->addAddress("santhosh@maxiscom.com");
			$mail->addAddress("enquiry@maxiscom.com");
			
			$mail->Subject = $_POST['reason'];
			$mail->Body  = $messageBody;
			
			//try to send the message
			if($mail->send()) {
				echo json_encode(array('message' => 'Your message was successfully submitted.'));
			} else {
				errorResponse('An expected error occured while attempting to send the email: ' . $mail->ErrorInfo);
			}
		} else {
			# set the error code so that we can display it
			$error = $resp->error;
			echo json_encode(array('message' => $error));
		}
	}
?>

