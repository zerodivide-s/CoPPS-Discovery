<?php

require_once '../bootstrap.php';
require_once COMPONENTS . 'Session.php';
require_once COMPONENTS . 'JsonHelper.php';
require_once MODELS . 'PollutionSrcDAOPsql.php';
require_once MODELS . 'DiagnosisDAOPsql.php';
require_once MODELS . 'PathologyDAOPsql.php';

Session::sec_session_start();
Session::check(POWERGUEST); //every user will be able to access this functionality

$response = array();
$filters = array(FILTER_VALIDATE_INT);
$id_loaded_pollution = array();
$id_loaded_diagnosis = array();

$loaded = filter_input(INPUT_POST, 'loaded_pollution'); //Array containing the Id of shapes already loaded on the map. They will not be fetched again from the database
if ($loaded !== false && $loaded !== NULL) {
    $json = false;
    try {
        $json = JsonHelper::decode($loaded, FILTER_VALIDATE_INT);
    } catch (Exception $ex) {
        $response["success"] = false;
        $response["msg"] = "Searching error. Please, press F5 to refresh the page.";
        exit(); //Flash messages showed using js
    }
    if (is_array($json)) {
        $id_loaded_pollution = $json;
    } else {
        $response["success"] = false;
        $response["msg"] = "Searching error. Please, press F5 to refresh the page.";
        echo json_encode($response);
        exit(); //Flash messages showed using js
    }
}
$loaded = filter_input(INPUT_POST, 'loaded_diagnosis'); //Array containing the Id of shapes already loaded on the map. They will not be fetched again from the database
if ($loaded !== false && $loaded !== NULL) {
    $json = false;
    try {
        $json = JsonHelper::decode($loaded, FILTER_VALIDATE_INT);
    } catch (Exception $ex) {
        $response["success"] = false;
        $response["msg"] = "Searching error. Please, press F5 to refresh the page.";
        exit(); //Flash messages showed using js
    }
    if (is_array($json)) {
        $id_loaded_diagnosis = $json;
    } else {
        $response["success"] = false;
        $response["msg"] = "Searching error. Please, press F5 to refresh the page.";
        echo json_encode($response);
        exit(); //Flash messages showed using js
    }
}
//Fetch the North-East and South-West points of the map
$ne_lat = filter_input(INPUT_POST, 'ne_lat', FILTER_VALIDATE_FLOAT);
$ne_lng = filter_input(INPUT_POST, 'ne_lng', FILTER_VALIDATE_FLOAT);
$sw_lat = filter_input(INPUT_POST, 'sw_lat', FILTER_VALIDATE_FLOAT);
$sw_lng = filter_input(INPUT_POST, 'sw_lng', FILTER_VALIDATE_FLOAT);
if (!$ne_lat || !$ne_lng || !$sw_lat || !$sw_lng) {
    $response["success"] = false;
    $response["msg"] = "Searching error.";
} else { //Generate the other 2 points
    $nw_lat = $ne_lat;
    $nw_lng = $sw_lng;
    $se_lat = $sw_lat;
    $se_lng = $ne_lng;
    $location = array(
        array('lat' => $ne_lat, 'lng' => $ne_lng),
        array('lat' => $se_lat, 'lng' => $se_lng),
        array('lat' => $sw_lat, 'lng' => $sw_lng),
        array('lat' => $nw_lat, 'lng' => $nw_lng)
    );
    $global_shapes = array();
    $global_shapes['pollution_srcs'] = array();
    $global_shapes['diagnoses'] = array();
    $pollutions = PollutionDAOPsql::getByAreaPolygon($location, $id_loaded_pollution); //Search all shapes in $location excluding $id_loaded_pollution
    $diagnosis = DiagnosisDAOPsql::getByAreaPolygon($location, $id_loaded_diagnosis);
    foreach ($pollutions as $g) {
        $data = PostGisHelper::extractData($g['shape']);
        $g['shape'] = $data;
        $g['date_from'] = DateHelper::convertData($g['date_from'], DATETIMEYMD, DATETIMEDMY);
        $g['date_to'] = DateHelper::convertData($g['date_to'], DATETIMEYMD, DATETIMEDMY);
        $global_shapes['pollution_srcs'][] = $g; //Writing js code for google maps
    }
    foreach ($diagnosis as $g) {
        $data = PostGisHelper::extractData($g['shape']);
        $g['shape'] = $data;
        $g['date'] = DateHelper::convertData($g['date'], DATETIMEYMD, DATETIMEDMY);
        $global_shapes['diagnoses'][] = $g; //Writing js code for google maps
    }
    $response["success"] = true;
    $response["data_found"] = $global_shapes;
}

echo json_encode($response);
exit();
