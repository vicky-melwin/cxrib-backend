<?php
require_once __DIR__ . "/db.php";
echo json_encode([
    "status" => "success",
    "message" => "DB CONNECTED"
]);
