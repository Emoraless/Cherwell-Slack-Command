<?php

header('Content-type: application/json');

class slackAPI{

	//creates slackAPI
	function __construct($type, $num){
		$this->typ = $type;
		$this->num = $num;
	}

	//gets a token from the leprechauns in cherwell land
	function requestToken() {
		$conn = new Connection('DB-Name');

		//instantiate login information, api_key, username, and password
		$api = $conn->tokenGrab(x);
		$user = $conn->tokenGrab(x);
		$pass = $conn->tokenGrab(x);

		//build fields to send to the token giver
		$fields = array(
			'grant_type' 	=> urlencode('password')
			, 'client_id'  	=> urlencode($api)
			, 'username'   	=> urlencode($user)
			, 'password'	=> urlencode($pass));

		$fields_string = '';
		foreach ($fields as $key=>$value) { $fields_string .= $key . '=' . $value . '&'; }
		rtrim($fields_string, '&');

		//send the request to token giver via cURL
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $conn->tokenGrab(x));
		curl_setopt($ch, CURLOPT_POST, count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			  'Content-Type'   => urlencode('application/x-www-form-urlencoded')
			, 'Content-Length' => urlencode(strlen($fields_string))
		));

		$cherwellApiResponse = json_decode(curl_exec($ch), TRUE);
		return $cherwellApiResponse['access_token'];
	}

	//gets a ticket with a given token
	function getTicket($token){
		$conn = new Connection('slackdb');

		#incident
		if (($this->typ == 'I' || $this->typ == 'i') && $this->num != null) {
			$busObId = $conn->tokenGrab(x);
			$fieldID = $conn->tokenGrab(x);
			return $this->getInfo($busObId, $fieldID, $token);
		}

		#task
		elseif (($this->typ == 'T' || $this->typ == 't') && $this->num != null) {
			$busObId = $conn->tokenGrab(x);
			$fieldID = $conn->tokenGrab(x);
			return $this->getInfo($busObId, $fieldID, $token);
		}

		#change request
		elseif (($this->typ == 'C' || $this->typ == 'c') && $this->num != null) {
			$busObId = $conn->tokenGrab(x);
			$fieldID = $conn->tokenGrab(x);
			return $this->getInfo($busObId, $fieldID, $token);
		}

		#problem ticket
		elseif (($this->typ == 'P' || $this->typ == 'p') && $this->num != null) {
			$busObId = 'x';
			$fieldID = 'x';
			return $this->getInfo($busObId, $fieldID, $token);
		}

		#invalid input
		else {return $this->writeHelp();}
	}

	//makes the JSON for the ticket itself
	function getInfo($busObId, $fieldId, $token) {
		$rec = $this->num;
		$conn = new Connection('DB-Name');

		#assign variables according to ticket type
		if($busObId === $conn->tokenGrab(x)){
			$type = "INCIDENT";
		}
		else if($busObId === $conn->tokenGrab(x)){
			$type = "TASK";
		}
		else if($busObId === $conn->tokenGrab(x)){
			$type = "CHANGE";
		}
		else if($busObId === 'x'){
			$type = "PROBLEM";
		}
		#build request
        $headers = array(
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        );
        $data = array(
            'busObId'           => $busObId,
            'includeAllFields'  => TRUE,
            'filters'           => array (
                array(
                    'fieldId'   => $fieldId,
                    'value'     => $rec,
					'dirty'		=> TRUE
                )
            )
        );
        $data_string = json_encode($data);

		//send request via cURL
        $ch = curl_init($conn->tokenGrab(x));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);


		#execute request
        $results = json_decode(curl_exec($ch), TRUE);

		#if request not empty, interpret
        if (isset($results['totalRows']) && $results['totalRows'] === 1) {
            $recId = $results['businessObjects'][0]['busObRecId'];
			#write direct url
			$url = "Cherwell-URL";
			if ($type === 'INCIDENT') {
				$description = $results['businessObjects'][0]['fields'][12]['value'];
				$requestor = $results['businessObjects'][0]['fields'][51]['value'];
				$shorty = $results['businessObjects'][0]['fields'][182]['value'];
				$color = "#0091F2";
				$lcolor = "#5EBEFF";
				$llcolor = "#A8DCFF";
			}
			else if ($type === 'TASK')
			{
				$description = $results['businessObjects'][0]['fields'][15]['value'];
				$requestor = $results['businessObjects'][0]['fields'][94]['value'];
				$shorty = $results['businessObjects'][0]['fields'][51]['value'];
				$ownedBy = $results['businessObjects'][0]['fields'][8]['value'];
				$color = "#00e64d";
				$lcolor = "#39FA7A";
				$llcolor = "#BAFFD1";
			}
			elseif ($type === 'CHANGE')
			{
				$description = $results['businessObjects'][0]['fields'][13]['value'];
				$requestor = $results['businessObjects'][0]['fields'][21]['value'];
				$shorty = $results['businessObjects'][0]['fields'][61]['value'];
				$color = "#8900BF";
				$lcolor = "#C636FF";
				$llcolor = "#E094FF";
			}
			elseif ($type === "PROBLEM")
			{
				$shorty = $results['businessObjects'][0]['fields'][24]['value'];
				$description = $results['businessObjects'][0]['fields'][15]['value'];
				$resolution = trim($results['businessObjects'][0]['fields'][22]['value']);
				if(empty($resolution)){
					$res = "There is no resolution at this time";
				}
				else{
					$res = $resolution;
				}
				$color = "#FFF83F";
				$lcolor = "#FFF960";
				$llcolor = "#FFFBAE";
			}

			#build json structure
			if(isset($ownedBy)){ ##if it is a task it will go through this, as it was checked to be owned by
				$interior = array(	'fallback' => "Your requested ticket: $type $rec",'color' => $color,
								'title' => $type.' '.$rec,
								'title_link' => $url,
								'text' => 'Requested by '.$requestor . "\nOwned by " . $ownedBy);
			}
			elseif(isset($resolution)){
				$interior = array(	'fallback' => "Your requested ticket: $type $rec",'color' => $color,
								'title' => $type.' '.$rec,
								'title_link' => $url,
								'text' => 'Resolution: '.$res);
			}
			else{ ##else it will generate a title without owned by
				$interior = array(	'fallback' => "Your requested ticket: $type $rec",'color' => $color,
								'title' => $type.' '.$rec,
								'title_link' => $url,
								'text' => 'Requested by '.$requestor);
			}
			$interior2 = array(	'fallback' => "Your requested ticket: $type $rec",'color' => $lcolor,
								'title' => 'Short Description:',
								'text' => $shorty);

			$interior3 = array(	'fallback' => "Your requested ticket: $type $rec",'color' => $llcolor,
								'title' => 'Full Description:',
								'text' => $description);

			$exterior = array(	'response_type' => 'in_channel',
								'attachments' => array('fallback' => "Your requested ticket: $type $rec",
													$interior, $interior2, $interior3));

			#return json structure (response on Slack)
			return json_encode($exterior);
        }
		#else, the ticket was not found (return error message)
		else {
			$pt1 = array(	'color'=>'#ff3333',
							'title'=>'Ticket not found');
			$pt2 = array(	'fallback'=>'Ticket not found',
							'attachments'=>array($pt1));
			return json_encode($pt2);
        }

	}

	//write help if formatting is off
	function writeHelp() {
		$pt1 = array(	'title' => 'Valid commands',
						'text' => "Please enter a request in the following format:\n/cherwell I ##### for an incident\n/cherwell T ##### for a task\n/cherwell C ##### for a change request",
						'color' => "#b3003b");
		$pt2 = array(	'fallback'=>"Help", 'attachments' => array($pt1));
		return json_encode($pt2);
	}
}


?>
