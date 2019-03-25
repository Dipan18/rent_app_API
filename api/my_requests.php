<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');


include_once '../config/Database.php';
include_once '../models/Rent.php';

$database = new Database();
$db = $database->connect();

$rent = new Rent($db);

$result = $rent->get_all_requests_on_my_products();

echo json_encode($result);

