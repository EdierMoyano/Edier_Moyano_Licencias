<?php
session_start();

// Redirigir si ya está logueado como super admin
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1) {
    header('Location: dashboard.php');
    exit;
}

// Incluir archivo de conexión
require_once '../conexion.php';

$error = '';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        try {
            // Crear instancia de la base de datos
            $db = new Database();
            $conn = $db->connect();
            
            // Consulta para verificar el usuario (solo rol 1 - Super Admin)
            $query = "SELECT u.id, u.nombres, u.apellidos, u.email, u.contraseña, r.id as rol_id, r.nombre_rol 
                    FROM usuarios u 
                    JOIN rol r ON u.rol = r.id 
                    WHERE u.email = :email AND r.id = 1";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verificar contraseña
                if (password_verify($password, $user['contraseña'])) {
                    // Iniciar sesión
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nombres'] . ' ' . $user['apellidos'];
                    $_SESSION['user_role'] = $user['rol_id'];
                    
                    // Redirigir al dashboard
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Contraseña incorrecta.';
                }
            } else {
                $error = 'Usuario no encontrado o no tiene permisos de Super Administrador.';
            }
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Super Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Super Administrador</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger">Acceso Restringido</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <a href="../index.php" class="text-light">Volver al inicio</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>