<?php
	header('Content-type: application/json');
	require ".\Includes\Connection.php";
	require ".\Includes\slackAPI.php";

	//this program only runs if there is a post sent to it containing 'text'
	if(isset($_POST['text']) && isset($_POST['token'])){
		$raw = $_POST['text'];
		$slack = $_POST['token'];

		$rawInfo = explode(" ", $raw);
		// allows for space between type and number, or not
		$type = $rawInfo[0];
		$input = $rawInfo[1];

		//establishes a new database connection
		$conn = new Connection('slackdb');
		$slackToken = $conn->tokenGrab(x);

		// The program needs to instantiate to check if the slack token is authentic
		if($slack == $slackToken){
			//if there is text set in both arguments check the last time the token updated
			if(isset($type) && isset($input)){

				//create new instance of slackbot
				$slacker = new slackAPI($type,$input);

				//check the time of the last token update
				$time = $conn->getTime();

				//if the last update is greater than 19.5 minutes, update
				if((time() - $time) > 1170){
					$token = $slacker->requestToken();
					$conn->upToken($token,time());
				}
				//else just grab the existing token and return a ticket
				else{
					$token = $conn->tokenGrab(x);
				}
				echo $slacker->getTicket($token);
			}
			//or if you fail at this, write help
			else{
				echo writeHelp();
			}
		}
		//if it doesn't come from slack it goes through here
		else{
			echo "You're not coming from slack.";
		}
	}
	else{
		echo "You're not coming from slack.";
	}
  
	#in case of invalid/no input or help input
	function writeHelp() {
		$pt1 = array(	'title' => 'Valid commands',
						'text' => "Please enter a request in the following format:\n/cherwell I ##### for an incident\n/cherwell T ##### for a task\n/cherwell C ##### for a change request",
						'color' => "#b3003b");
		$pt2 = array(	'fallback'=>"Help", 'attachments' => array($pt1));
		return json_encode($pt2);
	}
