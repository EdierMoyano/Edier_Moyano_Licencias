<?php
session_start();
require_once '../conexion.php'; 

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) {
        $mensaje = "Por favor, complete todos los campos.";
        $tipo_mensaje = "danger";
    } else {
        try {
            $db = new Database();
            $conn = $db->connect();
            
            $stmt = $conn->prepare("SELECT u.*, r.nombre_rol, e.nombre as empresa_nombre 
                                FROM usuarios u 
                                LEFT JOIN rol r ON u.rol = r.id 
                                LEFT JOIN empresa e ON u.id_empresa = e.id 
                                WHERE u.email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($password, $usuario['contraseña'])) {
                    // Verificar que el usuario sea administrador (rol 2)
                    if ($usuario['rol'] != 2) {
                        $mensaje = "Acceso denegado. No tiene permisos para acceder a este panel.";
                        $tipo_mensaje = "danger";
                    } else {
                        // Verificar si la empresa tiene licencia activa
                        $licencia_valida = true;
                        $mensaje_licencia = "";
                        
                        if (!empty($usuario['id_empresa'])) {
                            $stmt = $conn->prepare("SELECT COUNT(*) FROM licencias 
                                                WHERE id_empresa = ? AND estado = 'Activa' 
                                                AND fecha_fin >= CURDATE()");
                            $stmt->execute([$usuario['id_empresa']]);
                            
                            if ($stmt->fetchColumn() == 0) {
                                $licencia_valida = false;
                                $mensaje_licencia = "La licencia de su empresa ha expirado. Contacte con el proveedor.";
                            }
                        }
                        
                        if ($licencia_valida) {
                            $_SESSION['user_id'] = $usuario['id'];
                            $_SESSION['user_name'] = $usuario['nombres'] . ' ' . $usuario['apellidos'];
                            $_SESSION['user_email'] = $usuario['email'];
                            $_SESSION['user_role'] = $usuario['rol'];
                            $_SESSION['user_role_name'] = $usuario['nombre_rol'];
                            
                            if (!empty($usuario['id_empresa'])) {
                                $_SESSION['user_empresa_id'] = $usuario['id_empresa'];
                                $_SESSION['user_empresa'] = $usuario['empresa_nombre'];
                            }
                            header('Location: dashboard.php');
                            exit;
                        } else {
                            $_SESSION['licencia_expirada'] = true;
                            $_SESSION['mensaje_licencia'] = $mensaje_licencia;
                            
                            $mensaje = "No se puede iniciar sesión.";
                            $tipo_mensaje = "danger";
                        }
                    }
                } else {
                    $mensaje = "Credenciales incorrectas.";
                    $tipo_mensaje = "danger";
                }
            } else {
                $mensaje = "Credenciales incorrectas.";
                $tipo_mensaje = "danger";
            }
        } catch (PDOException $e) {
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background-color: #007bff;
            color: white;
            text-align: center;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-shield-lock"></i> Panel de Administrador</h3>
                <p class="mb-0">Iniciar Sesión</p>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Recordarme</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                </form>
                
                <div class="text-center mt-3">
                    <a href="../recuperar_password.php">¿Olvidó su contraseña?</a>
                </div>
                <div class="text-center mt-2">
                    <a href="../index.php">Volver al inicio</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (isset($_SESSION['licencia_expirada']) && $_SESSION['licencia_expirada']): ?>
    <div class="modal fade" id="licenciaModal" tabindex="-1" aria-labelledby="licenciaModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="licenciaModalLabel"><i class="bi bi-exclamation-triangle-fill"></i> Licencia Expirada</h5>
                </div>
                <div class="modal-body">
                    <p><?php echo $_SESSION['mensaje_licencia']; ?></p>
                    <p>Por favor, contacte con el proveedor para renovar su licencia.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var licenciaModal = new bootstrap.Modal(document.getElementById('licenciaModal'));
            licenciaModal.show();
        });
    </script>
    <?php 
    unset($_SESSION['licencia_expirada']);
    unset($_SESSION['mensaje_licencia']);
    endif; 
    ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>