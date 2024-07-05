<?php
$today = date("Y-m-d");
$mapping_path = "mappings";
include('config/config.php');

// Function to get access token for Halo API
function getAccessToken($client_id, $client_secret, $token_url) {
    $data = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'client_credentials',
        'scope' => 'all'
    ];

    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

    $response = curl_exec($ch);
    if ($response === FALSE) {
        die('Error fetching access token: ' . curl_error($ch));
    }

    curl_close($ch);
  //  echo $response;
    $responseData = json_decode($response, true);
    return $responseData['access_token'];
}

// Function to update Halo Agent Status
function UpdateHaloStatus($access_token, $update_status_url, $new_status, $agent_id) {
    echo "Function Updating Agent ID: ", $agent_id, "\n";
    echo "Function Updating Status: ", $new_status, "\n";

    $data = "[ {
        'onlinestatus' : $new_status,
        'isonline' : 'false',
        'id' : $agent_id
    }]";
    var_dump($data);

    $ch = curl_init($update_status_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $access_token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $response = curl_exec($ch);
    if ($response === FALSE) {
        die('Error updating agent status: ' . curl_error($ch));
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo "\nStatus: ", $status, "\n";
var_dump($response);
    curl_close($ch);

    $responseData = json_decode($response, true);
    if ($responseData === null) {
        die('Error decoding JSON response: ' . json_last_error_msg());
    }

    echo json_encode($responseData, JSON_PRETTY_PRINT);
}

// Function to translate userID to Halo Agent ID
function getAgentID($userID, $mapping_path) {
 //   echo "\nFunction User ID: ", $userID, "\n";
    $agentsJson = file_get_contents($mapping_path . '/agents.json');
    $agents = json_decode($agentsJson, true);
   // echo "\nAgents: ", json_encode($agents, JSON_PRETTY_PRINT);

    foreach ($agents['agents'] as $agentID => $agent) {
      //  echo "For each loop ran\n";
 //       echo "\nTT ID: ", $agent['TimeTastic'], "\n";
        if (isset($agent['TimeTastic']) && $agent['TimeTastic'] == $userID) {
     //       echo "If statement ran\n";
     //       echo "\nAgent ID from if: ", $agentID;
             return $agentID;
        //    echo "\nAgent ID In the function: ", $agentID;

        }
    }

    return null;
}

$access_token = getAccessToken($client_id, $client_secret, $token_url);
// Function to get Annual Leave
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: $authorization"));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$json = json_decode($response, true);

// Handle the JSON response here
$filteredHolidays = array_filter($json['holidays'], function($holiday) {
    return in_array($holiday['leaveType'], ['Holiday', 'Sick Leave', 'Unpaid Leave', 'Maternity', 'Paternity']);
});

$filteredHolidays = array_map(function($holiday) {
    return [
        'startDate' => $holiday['startDate'],
        'endDate' => $holiday['endDate'],
        'leaveType' => $holiday['leaveType'],
        'userID' => $holiday['userId'],
        'userName' => $holiday['userName']
    ];
}, $filteredHolidays);

$filteredJson = [
    'holidays' => $filteredHolidays,
    'totalRecords' => count($filteredHolidays),
    'pageNumber' => $json['pageNumber'],
    'nextPageLink' => $json['nextPageLink'],
    'previousPageLink' => $json['previousPageLink']
];

$prettyJson = json_encode($filteredJson, JSON_PRETTY_PRINT);
echo $prettyJson;
if (count($filteredHolidays) > 0) {
    echo "\nCount of People off: ", count($filteredHolidays), "\n";
    foreach ($filteredHolidays as $holiday) {
        echo "\nUser ID: ", $holiday['userID'];
        $agent_id = getAgentID($holiday['userID'],$mapping_path); 
        echo "\nAgent ID: ", $agent_id, "\n";
        UpdateHaloStatus($access_token, $update_status_url, "5", $agent_id);
        //if($agent_id == 27) {
        //    echo "Updating Agent ID: ", $agent_id, "\n";
         // UpdateHaloStatus($access_token, $update_status_url, "5", "27");
       // }
        echo "Leave Type: ", $holiday['leaveType'], "\n";
    }
}




?>