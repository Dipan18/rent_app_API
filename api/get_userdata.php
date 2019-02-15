<?php 

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control: application/json');
header('Access-Control-Allow-Methods: GET');


include_once '../config/Database.php';
include_once '../models/login.php';

$database = new Database();
$db = $database->connect();

$login = new Login($db);

$user_data = $login->get_userdata();

echo json_encode($user_data);

