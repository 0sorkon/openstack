<?php
header('Content-Type: application/json');

die();
$projectID="PROJECT_ID";		// project "Admin"
$userID="USER_ID";		// user admin
$password = "PASSWORD";

$method = "password";
$user = "admin";


$keystone="https://1.1.1.1:5000/v3/";
$nova="https://1.1.1.1:8774/v2.1";

function auth($method, $user, $password) {
	$ch = curl_init('https://1.1.1.1:5000/v3/auth/tokens');
	curl_setopt($ch,CURLOPT_POST,true);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
	curl_setopt($ch,CURLOPT_HTTPHEADER,array(
		"Accept: application/json",
		"Content-Type: application/json"
	));

	$data = [
	"auth" => [
			"identity" => [
				"methods" => [
				"password" 
				], 
				"password" => [
					"user" => [
						"name" => $user, 
						"domain" => [
							"name" => "Default" 
						], 
						"password" => $password 
					] 
				] 
			]
		] 
	];  
	curl_setopt($ch,CURLOPT_POSTFIELDS,"" . json_encode($data));
	$response = curl_exec($ch);
	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$headers = substr($response, 0, $header_size);
	$body = substr($response, $header_size);
	
	curl_reset($ch);
	curl_close($ch);

	$headers_indexed_arr = explode("\r\n", $headers);
	$headers_arr = array();
	$status_message = array_shift($headers_indexed_arr);
	foreach ($headers_indexed_arr as $value) {
		if(false !== ($matches = explode(':', $value, 2))) {
			$headers_arr["{$matches[0]}"] = trim($matches[1]);
			}
		}
	$headers_arr = array_filter($headers_arr);
	$token = $headers_arr['X-Subject-Token'];
	return $token;
}
	
function get_token_info($token) {
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,"https://1.1.1.1:5000/v3/auth/tokens"); 
	curl_setopt($ch,CURLOPT_POST,false);
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_HTTPHEADER,
		array("X-Auth-Token:" . $token, "X-Subject-Token:" . $token)
		);
	$response = json_decode(curl_exec($ch));
	return $response;
}

function get_flavors($token) {
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,"https://1.1.1.1:8774/v2.1/flavors/detail"); 
	curl_setopt($ch,CURLOPT_POST,false); // Again, GET request.
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_HTTPHEADER,
		array( "X-Auth-Token:" . $token)
	);
	$response = json_decode(curl_exec($ch));
	

	//curl_reset($ch);
	//curl_close($ch);
	
	return $response;
}

function get_images($token) {
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,"https://1.1.1.1:9292/v2/images?sort=name:asc,status:desc");
	curl_setopt($ch,CURLOPT_POST,false);
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_HTTPHEADER,
		array("X-Auth-Token:" . $token)
	);
	$response = json_decode(curl_exec($ch));
	
	return $response;
}

function get_instances($token) {
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,"https://1.1.1.1:8774/v2.1/servers"); 
	curl_setopt($ch,CURLOPT_POST,false);
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_HTTPHEADER,
    array("X-Auth-Token:" . $token)
	);
	$response = json_decode(curl_exec($ch));
	return $response;

}

function create_instance ($token, $name, $password, $image, $flavor) {
$ch = curl_init();
curl_setopt($ch,CURLOPT_URL,"https://1.1.1.1:8774/v2.1/servers"); 
curl_setopt($ch,CURLOPT_POST,true);
curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch,CURLOPT_HTTPHEADER,
array("Accept: application/json", "Content-Type: application/json", "X-Auth-Token:" . $token)
	);
	//$debianImage = "b9d23876-2394-47ea-bcf1-5758caa5c39b";
	//$cirrosImage = "10b47150-ba86-47e6-a20b-91527182ccde";
 	switch ($image) {
		case 'debian':
			$imageRef = 'b9d23876-2394-47ea-bcf1-5758caa5c39b';
			break;
		case 'cirros':
			$imageRef = '10b47150-ba86-47e6-a20b-91527182ccde';
			break;
	}

	$name = uniqid($name."-");

	$data = [
		"server" => [
			"adminPass" => $password,
			"name" => $name,
			"imageRef" => $imageRef, 
			"flavorRef" => $flavor, 
			"OS-DCF:diskConfig" => "AUTO", 
			"metadata" => [
				"origin" => "from api" 
			],
			"networks" => [[
				"uuid" => "97dc4c19-90e7-40fb-85e3-ea59fe786058",
			]],
			"personality" => [
				[
                  "path" => "/etc/banner.txt", 
                  "contents" => "IkZvbGxvdyB0aGUgd2hpdGUgcmFiYml0Ig==" 
               ] 
            ], 
			"security_groups" => [
				[
                        "name" => "default" 
                     ] 
                 ], 
        "user_data" => "IyEvYmluL2Jhc2gKL2Jpbi9zdQplY2hvICJIRUxMTyBJTlRST1NFUlYi" 
		], 
	];
	curl_setopt($ch,CURLOPT_POSTFIELDS,"" . json_encode($data));
	
	$response = json_decode(curl_exec($ch));
	
	if (is_null($response)) {
		die("Something went wring");
	}
	
	$instanceID = $response->server->id;
	// get instance creation progress
	$i = true;
	 while ($i) {
		curl_setopt($ch,CURLOPT_URL,"https://1.1.1.1:8774/v2.1/servers/$instanceID"); 
		curl_setopt($ch,CURLOPT_POST,false);
		curl_setopt($ch,CURLOPT_HTTPHEADER,
			array(
				"X-Auth-Token:" . $token
			)
		);
		
		$response = json_decode(curl_exec($ch));
		
		$status = $response->server->status;
		if ($status == 'ERROR') {
			$code = $response->server->fault->code;
			$message = $response->server->fault->message;
			echo "ERROR ".$code."\n";
			echo $message."\n";
			die();
		}
		$progress = $response->server->progress;
		$powerState = $response->server->{'OS-EXT-STS:power_state'};
		if ($status != "ACTIVE") {
			//echo ".";
		}

		if ($powerState == "1") {
			$instanceName = $response->server->name;
			$instanceIP = $response->server->addresses->external[0]->addr;
			//echo "\nInstanse with name ".$instanceName." and ID ".$instanceID." is ready. IP: ".$instanceIP."\n";
			//create instance console if instanse is up and running
			//echo "Console access: ";
			//get_instance_console($token,$instanceID);
			$i = false;
			return $instanceID;
		}
	sleep(1);

	}
}

function get_instance_console($token, $instanceID) {
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,"https://1.1.1.1:8774/v2.1/servers/$instanceID/remote-consoles"); 
	curl_setopt($ch,CURLOPT_POST,true);
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_HTTPHEADER,
	array(
		"Accept: application/json",
		"Content-Type: application/json",
		"OpenStack-API-Version: compute 2.87",
		"X-OpenStack-Nova-API-Version: 2.87",
		"X-Auth-Token:" . $token
		)
	);
	$data = [
		"remote_console" => [
		"protocol" => "spice", 
		"type" => "spice-html5" 
		]
	]; 
	curl_setopt($ch,CURLOPT_POSTFIELDS,"" . json_encode($data));
	$response = json_decode(curl_exec($ch));
	//print_r($response);
	$consoleUrl = $response->remote_console->url;
	$consoleType = $response->remote_console->type;
	$consoleProto = $response->remote_console->protocol;
	echo "Console URL: ".$consoleUrl."\n";
	echo "Console type: ".$consoleType."\n";
	echo "Console protocol: ".$consoleProto."\n";
}

function delete_instance($token, $id) {
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,"https://1.1.1.1:8774/v2.1/servers/$id"); 
	curl_setopt($ch,CURLOPT_POST,false);
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_HTTPHEADER,
	array(
        "X-Auth-Token:" . $token
	)
);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
$response = curl_exec($ch);
print_r($response);
}

// Get auth token
$token = auth("method", "user", "password");
$tInfo=get_token_info($token);
$flav=get_flavors($token);
$img=get_images($token);
$inst=get_instances($token);

/* echo "Creating instance...\n";
//USE: create_instance ($token, 'test', $password, $image, $flavor)
$id=create_instance($token, 'test', 'Qwerty1', 'debian', '2');
if($id) {
	echo "	Instance created with id: ".$id."\n";
} */

 
/* echo "Get console info by function get_instance_console()\n";
$id="296508f0-ca6f-43e0-a4c2-13760019bc13";
get_instance_console($token, $id); */


delete_instance($token, "296508f0-ca6f-43e0-a4c2-13760019bc13");

 ?>
