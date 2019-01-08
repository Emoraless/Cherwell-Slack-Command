<?php
header('Content-Type: application/JSON');

// Connection class
class Connection {
	private $serverName = "SERVER-NAME";
	private $connectionInfo = array();
	private $conn;


	// Constructor function for building the class
	function __construct($databaseName) {
		$this->databaseName = $databaseName;
		$this->connectionInfo['Database'] = "$databaseName";
	}
	//connect to the DB
	function connectSql() {
		$conn = sqlsrv_connect( $this->serverName, $this->connectionInfo );
		if( $conn ) {
			 //echo "Connection established" . PHP_EOL;
			 $this->conn = $conn;
		} else {
			 echo "Connection could not be established" . PHP_EOL;
			 die( print_r( sqlsrv_errors(), true));
		}
	}
	//close the DB
	function closeSql() {
		sqlsrv_close($this->conn);
	}

	// Function to run the SQL query
	function runSql($params, $sql) {
		$this->connectSql();
		// Run the query and save the output to the $stmt variable
		if(isset($params)){
			$stmt = sqlsrv_query($this->conn, $sql, $params);
		} else {
			$stmt = sqlsrv_query($this->conn, $sql);
		}
		// Stop query if there's an error and print out the message
		if( $stmt === false ) {
			die( print_r( sqlsrv_errors(), true));
		}
		return $stmt;
	}

	// Function to update the token
	function runUpdate($sql) {
		$this->connectSql();
		// Run the query and save the output to the $stmt variable
		$stmt = sqlsrv_query($this->conn, $sql);
		// Stop query if there's an error and print out the message
		if( $stmt === false ) {
			die( print_r( sqlsrv_errors(), true));
		}
		return $stmt;
	}
  
	//updates the token being used
	public function upToken($token, $time){
		if(strlen($token) > 30){
			$sql = "UPDATE dbo.cherToken SET token ='". $token."', last = $time WHERE pid=1;";
			$this->runUpdate($sql);
		}
		else{
			$this->tokenAlert('');
			$this->tokenAlert('@eric');
		}
	}
	//gets the token of whatever is being used based on the pid in the databse
	public function tokenGrab($index){
		$sql = "EXEC dbo.tokenGrab @id = ?;";
		$params = array($index);
		$result = $this->runSql($params,$sql);
		$value = sqlsrv_fetch_array($result);
		$token = $value['token'];
		return $token;
	}
	//gets the last updated time of the cherwell token
	public function getTime(){
		$sql = "EXEC dbo.tokenGrab @id = ?;";
		$params = array(1);
		$result = $this->runSql($params,$sql);
		$value = sqlsrv_fetch_array($result);
		$last = $value['last'];
		return $last;
	}

	//update freq of IP Address, with possible slack token
	public function ipUP($ip,$slack){
		$sql = "EXEC dbo.ipGrab @ip = ?;";
		$params = array($ip);
		$result = $this->runSql($params,$sql);
		$value = sqlsrv_fetch_array($result);
		//updates the IP tables, if its one that has been inserted before, increment the frequency
		if(isset($value)){
			$freq = $value['freq'] + 1;
			$sql = "UPDATE dbo.cherIP SET freq = $freq WHERE ip = '$ip';";
			$this->runUpdate($sql);
		}
		//  else place new IP into the table
		else{
			$sql = "INSERT INTO dbo.cherIP VALUES ('$ip',1,'$slack');";
			$this->runUpdate($sql);
		}
	}

	//accesses the last time a user tried to create a ticket
	public function quickIPUP($ip){
		$sql = "EXEC dbo.QuickIpGrab @ip = ?;";
		$params = array($ip);
		$result = $this->runSql($params,$sql);
		$value = sqlsrv_fetch_array($result);
		if(isset($value)){
			$time = $value['time_since'];
			$nowtime = time() - $value['last_timestamp'];
			$timestamp = time();
			if($nowtime > 25){
				$sql = "UPDATE dbo.QuickTicketUpdate SET time_since = $nowtime, last_timestamp = $timestamp WHERE ip_addr = '$ip';";
				$this->runUpdate($sql);
			}
			return $nowtime;
		}
		else{
			$nowtime = time();
			$sql = "INSERT INTO dbo.QuickTicketUpdate VALUES ('$ip',0,$nowtime);";
			$this->runUpdate($sql);
			return 1000;
		}
	}

	//Pushes a curl request to incoming webhooks in slack if the token fails to update properly
	private function tokenAlert($name){
		$url = 'Slack-URL';

		$data = array('fallback'=>'fallback','text' => 'Cherwell token failed to update', 'color' => "#FF0000");
		$data0 = array('channel'=>$name,'$name' => 'Cherwell_Alert','attachments' => array($data));
		$data00 = json_encode($data0);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data00);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data00))
		);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_exec($ch);

	}
}
?>
