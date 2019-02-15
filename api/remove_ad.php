<?php 

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-: application/json');
header('Access-Control-Allow-Methods: GET');


include_once '../config/Database.php';
include_once '../models/Products.php';

$database = new Database();
$db = $database->connect();

$product = new Products($db);

echo json_encode($product->remove_ad());
