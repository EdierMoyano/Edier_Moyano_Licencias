<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $hash = $_POST['hash'] ?? '';
    
    if (empty($password) || empty($hash)) {
        echo json_encode([
            'success' => false,
            'message' => 'Faltan parámetros requeridos.'
        ]);
        exit;
    }
    
    $result = password_verify($password, $hash);
    
    echo json_encode([
        'success' => $result,
        'message' => $result 
            ? 'La contraseña coincide con el hash.' 
            : 'La contraseña NO coincide con el hash.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido.'
    ]);
}