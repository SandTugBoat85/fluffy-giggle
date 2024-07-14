<?php

// Setup a bunch of variables & include config file
$today = date("Y-m-d");
include('config/config.php');
$mapping_path = "mappings";
$log_path = "logs";
$log_todays_file = $log_path . "/log-" . $today . ".log";
$token_url = "https://$halo_instance/auth/token";
$update_status_url = "https://$halo_instance/api/agent";


function fixTimes($startType, $endType, $startDate, $endDate, $half_day_start)
{
    $startDate = date('Y-m-d', strtotime($startDate));
    $endDate = date('Y-m-d', strtotime($endDate));
    if ($startType == "Afternoon") {
        $startDate = date('Y-m-d H:i:s', strtotime($startDate . ' + ' . $half_day_start . 'hours'));
        echo "Start: " . $startDate . "\n";
    }
    if ($endType == "Morning") {
        $endDate = date('Y-m-d H:i:s', strtotime($endDate . ' + ' . $half_day_start . 'hours'));
        echo "End: " . $endDate . "\n";
    }
    if ($endType == "Afternoon") {
        $endDate = date('Y-m-d H:i:s', strtotime($endDate . ' + 23 hours 59 minutes 59 seconds'));
        echo "The end of the day:" . $endDate;
    }
    return [$startDate, $endDate];

}

// Function to check if the agent is off at this moment
function isAgentOff($startDate, $endDate)
{
    $now = date('Y-m-d H:i:s');
    $startDate = date('Y-m-d H:i:s', strtotime($startDate));
    $endDate = date('Y-m-d H:i:s', strtotime($endDate));

    if ($startDate < $now && $now < $endDate) {
        return true;
    }
}

// Function to check if leave is half a day
function partDayOff($startType, $endType, $startDate, $endDate)
{
    $today = date("Y-m-d") . "T00:00:00";
    if ($startDate == $endDate) {
        return $startType == $endType;
    }

    if ($startDate == $today && $startType == "Afternoon") {
        return "Afternoon";
    } elseif ($endDate == $today && $endType == "Morning") {
        return "Morning";
    } else {
        return false;
    }
}

// Function to get access token for Halo API
function getAccessToken($client_id, $client_secret, $token_url)
{
    $data = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'client_credentials',
        'scope' => 'admin'
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

    $responseData = json_decode($response, true);
    return $responseData['access_token'];
}

// Function to update Halo Agent Status
function UpdateHaloStatus($access_token, $update_status_url, $new_status, $agent_id, $debug)
{
    if ($debug == "info" || $debug == "debug") {
        print htmlspecialchars("Updating Agent ID: " . $agent_id . " With Status: " . $new_status . "\n");
    }

    $data = "[ {
        'onlinestatus' : $new_status,
        'isonline' : 'false',
        'id' : $agent_id
    }]";
    //var_dump($data);

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
    if ($status != 201) {
        die('Error updating agent status: ' . $status);
    }
    curl_close($ch);

    $responseData = json_decode($response, true);
    if ($responseData === null) {
        die('Error decoding JSON response: ' . json_last_error_msg());
    }
}

// Function to translate userID to Halo Agent ID
function getAgentID($userID, $mapping_path)
{
    $agentsJson = file_get_contents($mapping_path . '/agents.json');
    $agents = json_decode($agentsJson, true);

    foreach ($agents['agents'] as $agentID => $agent) {
        if (isset($agent['TimeTastic']) && $agent['TimeTastic'] == $userID) {
            if ($agentID != null) {
                return htmlspecialchars($agentID, ENT_QUOTES, 'UTF-8');
            }
        }
    }

    return null;
}

$access_token = getAccessToken($client_id, $client_secret, $token_url);

// Function to get Annual Leave
$ch = curl_init($tt_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer $tt_auth"));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$json = json_decode($response, true);

// Handle the JSON response here
$leavetypesJson = file_get_contents($mapping_path . '/leavetypes.json');
$leavetypes = json_decode($leavetypesJson, true);

$filteredHolidays = array_filter($json['holidays'], function ($holiday) use ($leavetypes) {
    $leaveType = $holiday['leaveType'];
    return isset($leavetypes['leavetypes'][$leaveType]) && in_array($leaveType, array_keys($leavetypes['leavetypes']));
});

$filteredHolidays = array_map(function ($holiday) {
    return [
        'startDate' => $holiday['startDate'],
        'startType' => $holiday['startType'],
        'endDate' => $holiday['endDate'],
        'endType' => $holiday['endType'],
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

if ($debug == "info" || $debug == "debug") {
    $prettyJson = json_encode($filteredJson, JSON_PRETTY_PRINT);
    echo $prettyJson;
}
if (count($filteredHolidays) > 0) {
    echo "\nCount of People off: ", count($filteredHolidays), "\n";
    $agentsoff = []; // Initialize the $agentsoff array

    foreach ($filteredHolidays as $holiday) {
        // Look up the agent ID from the mapping file using the userID
        $agent_id = getAgentID($holiday['userID'], $mapping_path);
        // Call the function to update the agent status - Currently all agents are marked as Annual Leave
        [$startDate,$endDate] = fixTimes($holiday['startType'], $holiday['endType'], $holiday['startDate'], $holiday['endDate'], $half_day_start);
        $agentisoff = isAgentOff($startDate, $endDate);
        if ($agentisoff) {
            UpdateHaloStatus($access_token, $update_status_url, "5", $agent_id, $debug);
            // Add the agent ID to the $agentsoff array
            $agentsoff[] = $agent_id;
        }
    }

    // Convert the $agentsoff array to JSON format
    $agentsoffJson = json_encode($agentsoff);
} else {
    $agentsoffJson = json_encode([]);
}

// Function to make a list of agents that aren't having status updated
function getAgentsNotUpdated($mapping_path, $agentsoffJson, $access_token, $update_status_url, $debug)
{
    $agentsJson = file_get_contents($mapping_path . '/agents.json');
    $agents = json_decode($agentsJson, true);
    $agentsoff = json_decode($agentsoffJson, true);

    $agentsNotUpdated = [];

    foreach ($agents['agents'] as $agentID => $agent) {
        if (!in_array($agentID, $agentsoff)) {
            $agentsNotUpdated[] = $agent;
            UpdateHaloStatus($access_token, $update_status_url, "0", $agentID, $debug);
        }
    }
}

// Call function to update agents not on leave to available
getAgentsNotUpdated($mapping_path, $agentsoffJson, $access_token, $update_status_url, $debug);
