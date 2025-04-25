<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    if (empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Falta la contraseña.'
        ]);
        exit;
    }
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    echo json_encode([
        'success' => true,
        'hash' => $hash
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido.'
    ]);
}