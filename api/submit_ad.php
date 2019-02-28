<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');


include_once '../config/Database.php';
include_once '../models/Ad.php';

$database = new Database();
$db = $database->connect();

$ad = new Ad($db);

$result = $ad->submit_ad();

echo json_encode($result);