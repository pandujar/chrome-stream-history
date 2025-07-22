<?php
$log_file = '/home/data/www/logger.log';

// Leer el cuerpo JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validar que sea un JSON válido
if (json_last_error() !== JSON_ERROR_NONE || !$data) {
    http_response_code(400);
    echo "Invalid JSON";
    exit;
}

// Añadir timestamp del servidor
$data['server_received'] = date('c');

// Convertir a línea de log
$log_line = json_encode($data) . PHP_EOL;

// Escribir al archivo
file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);

// Confirmar recepción
http_response_code(200);
echo "OK";
?>
