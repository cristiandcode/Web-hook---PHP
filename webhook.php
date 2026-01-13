<?php


$input = file_get_contents("php://input");
$data = json_decode($input, true);


$logLine = date("Y-m-d H:i:s") . " - " . $input . PHP_EOL;
file_put_contents("webhook.log", $logLine, FILE_APPEND);


try {
    $message = $data["entry"][0]["changes"][0]["value"]["messages"][0];
    $sender = $message["from"] ?? null;
    $text   = $message["text"]["body"] ?? "";

    if ($sender && $text) {
        error_log("Mensaje de $sender: $text");
    }
} catch (Exception $e) {

}

// Respuesta requerida por WhatsApp
http_response_code(200);
echo "EVENT_RECEIVED";
