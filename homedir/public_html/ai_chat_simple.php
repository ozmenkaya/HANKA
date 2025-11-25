<?php
// Minimum test - sadece JSON döndür
header("Content-Type: application/json; charset=utf-8");
echo json_encode(["test" => "OK", "time" => date("Y-m-d H:i:s")]);
