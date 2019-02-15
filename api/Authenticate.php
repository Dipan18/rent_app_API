<?php 

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');


include_once '../config/Database.php';
include_once '../models/login.php';

$database = new Database();
$db = $database->connect();

$login = new Login($db);

$login_status = $login->authenticate();
// $user_data['data'] = $login_status->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($login_status);
