<?php
	require_once "../vendor/autoload.php";

	$dotenv = new Dotenv\Dotenv(__DIR__);
	$dotenv->load();

	define("JIRA_USERNAME", getenv('JIRA_USERNAME'));
	define("JIRA_PASSWORD", getenv('JIRA_PASSWORD'));
	define("JIRA_TOKEN", getenv('JIRA_TOKEN'));
	define("JIRA_URL", getenv('JIRA_URL') . "/rest/api/2/issue/");
	define("JIRA_URL_SCRIPT", getenv('JIRA_URL') . "/rest/scriptrunner/latest/custom/");

	/**
	 * Function to create jira ticket using the jira API 
	 *
	 */

	function jira_new_issue_create($data){
		$username = JIRA_USERNAME;
		$password = JIRA_PASSWORD;

		$url = JIRA_URL;

		$ch = curl_init();

		$headers = array(
			'Accept: application/json',
			'Content-Type: application/json',
		);


		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

		$response = curl_exec($ch);
		$ch_error = curl_error($ch);
		curl_close($ch);
		if ($ch_error){
		//      echo "cURL Error: $ch_error";
			return array(
				"ok" => false,
				"error" => "Error : Jira API error",
				);
		}else{
			$body = json_decode($response, true);
			//error_log(print_r($body, true));  
		}
		return array(
				"ok" => true,
				"body" => json_decode($response, true),
			);
	}

	function jira_new_issue_get($epic){
		$username = JIRA_USERNAME;
		$password = JIRA_PASSWORD;
		$url = JIRA_URL . $epic;

		$ch = curl_init();

		$headers = array(
			'Accept: application/json',
			'Content-Type: application/json',
		);


		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		//curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

		$response = curl_exec($ch);
		$ch_error = curl_error($ch);
		if ($ch_error){
		//      echo "cURL Error: $ch_error";
			curl_close($ch);
			return array(
				"ok" => false,
				"error" => "Error : Jira API error",
			);

		}else{
			// $header = substr($response, 0, $header_size);
			$body = json_decode($response, true);
			//error_log(print_r($body, true));  

			curl_close($ch);
			return array(
				"ok" => true,
				"body" => $body ,
			);
		}
	}


	function jira_new_issue_add_checklistitem($issue_id, $customfield_id, $checklist_items){
		$username = JIRA_USERNAME;
		$password = JIRA_PASSWORD;
		$url = JIRA_URL_SCRIPT . "addChecklistItems";

		$payload = array(
			'issue_id' => $issue_id,
			'customfield_id' => $customfield_id,
			'items' => $checklist_items,
		);


		$ch = curl_init();

		$headers = array(
			'Accept: application/json',
			'Content-Type: application/json',
		);


		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

		$response = curl_exec($ch);
		$ch_error = curl_error($ch);
		
		if ($ch_error){
			// echo "cURL Error: $ch_error";
			curl_close($ch);
			return array(
				"ok" => false,
				"error" => "Error : Jira API error",
			);

		}else{
			// $header = substr($response, 0, $header_size);
			$body = json_decode($response, true);

			curl_close($ch);
			return array(
				"ok" => true,
				"body" => $body ,
			);
		}
	}
