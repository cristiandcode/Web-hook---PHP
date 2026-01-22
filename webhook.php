<?php

$VERIFY_TOKEN = "verify_token_123";
$ACCESS_TOKEN = "EAARN9flC6kkBQX93kHHqpZAHPiw9oyRGp05ilwXDG2xGZBN7jZAtndIpUNRTRGXmLUQpRKZBHlXkrbOvPq5dZCDyO4LUwrPiZCxsnPLECkBROKMIlvQUgbSj4voqHsVVmeFHX0FCgMBZBbCMFwThz7XKgVfAzF0wDsiQ9oPDIaw4nheDnZBgPQAhYzpbNMCKKw5x5nDeIyryUGYQnJrHc9AkLlatNaiSM0aaj0tkxveexG5gZBsLl62wLD59NQO0ca4nw65NQ4sCtVypiX3aFLwZDZD"; 
$PHONE_NUMBER_ID = "935891152946340";


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode      = $_GET['hub.mode'] ?? null;
    $token     = $_GET['hub.verify_token'] ?? null;
    $challenge = $_GET['hub.challenge'] ?? null;

    if ($mode === 'subscribe' && $token === $VERIFY_TOKEN) {
        http_response_code(200);
        echo $challenge;
        exit;
    }
    http_response_code(403);
    exit;
}


$input = file_get_contents("php://input");
$data  = json_decode($input, true);

// Log de entrada para ver qué nos envía Meta
file_put_contents("webhook.log", date('Y-m-d H:i:s') . " [ENTRADA]: " . $input . PHP_EOL, FILE_APPEND);

if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
    
    $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
    $from    = $message['from']; // Ejemplo: 549381...
    $text    = strtolower(trim($message['text']['body'] ?? ''));

    // 🇦🇷 CORRECCIÓN PARA ARGENTINA:
    // Meta recibe 549... pero para responder a veces requiere 54... (quitar el 9)
    if (strlen($from) > 11 && strpos($from, '549') === 0) {
        $from = '54' . substr($from, 3);
    }

    // 🧠 LÓGICA SIMPLE
    if (strpos($text, 'hola') !== false) {
        $reply = "Hola 👋 Bienvenido a DrBeauty ✨\n\n¿Querés sacar un turno o ver servicios?";
    } elseif (strpos($text, 'turno') !== false) {
        $reply = "📅 Perfecto. Decime qué tratamiento te interesa.";
    } else {
        $reply = "Mensaje recibido ✅ Escribí 'hola' para empezar.";
    }

    // Enviar respuesta
    sendMessage($from, $reply, $ACCESS_TOKEN, $PHONE_NUMBER_ID);
}

// Responder a Meta siempre con 200
http_response_code(200);
echo "EVENT_RECEIVED";

// ==================================================
// 3️⃣ FUNCIÓN DE ENVÍO
// ==================================================
function sendMessage($to, $text, $token, $phoneId) {
    $url = "https://graph.facebook.com/v22.0/$phoneId/messages";

    $payload = [
        "messaging_product" => "whatsapp",
        "recipient_type"    => "individual",
        "to"                => $to,
        "type"              => "text",
        "text"              => ["body" => $text]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 📝 LOG DE SALIDA - ESTO TE DIRÁ POR QUÉ NO LLEGA EL MENSAJE
    $logMsg = date('Y-m-d H:i:s') . " [SALIDA] Para: $to | HTTP_CODE: $httpCode | Respuesta: $response" . PHP_EOL;
    file_put_contents("webhook.log", $logMsg, FILE_APPEND);
}