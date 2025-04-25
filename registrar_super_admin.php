<?php
// Incluir archivo de conexión
require_once 'conexion.php';

$mensaje = '';
$exito = false;

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $nombres = $_POST['nombres'];
    $apellidos = $_POST['apellidos'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if (empty($id) || empty($nombres) || empty($apellidos) || empty($email) || empty($password)) {
        $mensaje = '<div class="alert alert-danger">Por favor, complete todos los campos.</div>';
    } else {
        try {
            $db = new Database();
            $conn = $db->connect();
            
            // Verificar si existe el rol de Super Admin (id = 1)
            $stmt = $conn->query("SELECT id FROM rol WHERE id = 1");
            if ($stmt->rowCount() == 0) {
                // Crear rol de Super Admin si no existe
                $conn->exec("INSERT INTO rol (id, nombre_rol) VALUES (1, 'S_Admin')");
                $mensaje .= '<div class="alert alert-info">Rol de Super Admin creado correctamente.</div>';
            }
            
            // Verificar si existe la empresa para el Super Admin
            $stmt = $conn->query("SELECT id FROM empresa WHERE nombre = 'Desarrolladores' LIMIT 1");
            if ($stmt->rowCount() > 0) {
                $empresa_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
            } else {
                // Crear la empresa para el Super Admin
                $conn->exec("INSERT INTO empresa (nombre) VALUES ('Desarrolladores')");
                $empresa_id = $conn->lastInsertId();
                $mensaje .= '<div class="alert alert-info">Empresa "Desarrolladores" creada correctamente.</div>';
            }
            
            // Verificar si ya existe un usuario con ese ID o email
            $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE id = ? OR email = ?");
            $stmt->execute([$id, $email]);
            $existe = ($stmt->fetchColumn() > 0);
            
            if ($existe) {
                $mensaje .= '<div class="alert alert-danger">Ya existe un usuario con ese ID o email.</div>';
            } else {
                // Crear hash de la contraseña
                $hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Mostrar el hash generado para verificación
                $hash_info = '<div class="alert alert-info">
                    <p><strong>Hash generado:</strong> ' . $hash . '</p>
                    <p>Guarda esta información para referencia.</p>
                </div>';
                
                // Insertar el usuario
                $stmt = $conn->prepare("INSERT INTO usuarios (id, nombres, apellidos, email, contraseña, id_empresa, rol) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$id, $nombres, $apellidos, $email, $hash, $empresa_id, 1]);
                
                $mensaje = '<div class="alert alert-success">
                    <h4>¡Usuario Super Admin creado correctamente!</h4>
                    <p>Ahora puedes iniciar sesión con:</p>
                    <p><strong>Email:</strong> ' . $email . '</p>
                    <p><strong>Contraseña:</strong> ' . $password . '</p>
                </div>' . $hash_info;
                $exito = true;
                
                // Verificar si se puede autenticar con la contraseña
                $stmt = $conn->prepare("SELECT contraseña FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);
                $stored_hash = $stmt->fetchColumn();
                
                $auth_test = password_verify($password, $stored_hash);
                $mensaje .= '<div class="alert alert-' . ($auth_test ? 'success' : 'danger') . '">
                    <p><strong>Prueba de autenticación:</strong> ' . ($auth_test ? 'EXITOSA' : 'FALLIDA') . '</p>
                    <p>La contraseña ' . ($auth_test ? 'coincide' : 'NO coincide') . ' con el hash almacenado.</p>
                </div>';
            }
        } catch (PDOException $e) {
            $mensaje = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">Registrar Super Administrador</h4>
                    </div>
                    <div class="card-body">
                        <?php echo $mensaje; ?>
                        
                        <?php if (!$exito): ?>
                        <form method="post" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="id" class="form-label">Documento de Identidad</label>
                                    <input type="text" class="form-control" id="id" name="id" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nombres" class="form-label">Nombres</label>
                                    <input type="text" class="form-control" id="nombres" name="nombres" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="apellidos" class="form-label">Apellidos</label>
                                    <input type="text" class="form-control" id="apellidos" name="apellidos" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="text" class="form-control" id="password" name="password" required>
                                <div class="form-text">La contraseña se mostrará en texto plano para verificación.</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-danger">Registrar Super Admin</button>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="d-grid gap-2 mt-3">
                            <a href="super_admin/index.php" class="btn btn-primary">Ir al Login de Super Admin</a>
                            <button onclick="window.location.reload()" class="btn btn-secondary">Registrar otro usuario</button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card shadow mt-4">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0">Información de Depuración</h4>
                    </div>
                    <div class="card-body">
                        <h5>Verificación de Contraseña</h5>
                        <form id="verificarForm" class="mb-4">
                            <div class="row g-3 align-items-center">
                                <div class="col-auto">
                                    <label for="testPassword" class="col-form-label">Contraseña:</label>
                                </div>
                                <div class="col-auto">
                                    <input type="text" id="testPassword" class="form-control">
                                </div>
                                <div class="col-auto">
                                    <label for="testHash" class="col-form-label">Hash:</label>
                                </div>
                                <div class="col">
                                    <input type="text" id="testHash" class="form-control">
                                </div>
                                <div class="col-auto">
                                    <button type="button" id="btnVerificar" class="btn btn-primary">Verificar</button>
                                </div>
                            </div>
                        </form>
                        <div id="resultadoVerificacion" class="alert alert-info d-none">
                            Resultado de la verificación aparecerá aquí.
                        </div>
                        
                        <h5>Generar Hash</h5>
                        <form id="generarHashForm">
                            <div class="row g-3 align-items-center">
                                <div class="col-auto">
                                    <label for="rawPassword" class="col-form-label">Contraseña:</label>
                                </div>
                                <div class="col-auto">
                                    <input type="text" id="rawPassword" class="form-control">
                                </div>
                                <div class="col-auto">
                                    <button type="button" id="btnGenerar" class="btn btn-success">Generar Hash</button>
                                </div>
                            </div>
                        </form>
                        <div id="resultadoHash" class="alert alert-info mt-3 d-none">
                            Hash generado aparecerá aquí.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar contraseña contra hash
            document.getElementById('btnVerificar').addEventListener('click', function() {
                const password = document.getElementById('testPassword').value;
                const hash = document.getElementById('testHash').value;
                const resultDiv = document.getElementById('resultadoVerificacion');
                
                if (!password || !hash) {
                    resultDiv.className = 'alert alert-warning';
                    resultDiv.textContent = 'Por favor, ingrese tanto la contraseña como el hash.';
                    resultDiv.classList.remove('d-none');
                    return;
                }
                
                // Hacer la solicitud al servidor para verificar
                fetch('verificar_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `password=${encodeURIComponent(password)}&hash=${encodeURIComponent(hash)}`
                })
                .then(response => response.json())
                .then(data => {
                    resultDiv.className = `alert alert-${data.success ? 'success' : 'danger'}`;
                    resultDiv.textContent = data.message;
                    resultDiv.classList.remove('d-none');
                })
                .catch(error => {
                    resultDiv.className = 'alert alert-danger';
                    resultDiv.textContent = 'Error al verificar: ' + error.message;
                    resultDiv.classList.remove('d-none');
                });
            });
            
            // Generar hash para una contraseña
            document.getElementById('btnGenerar').addEventListener('click', function() {
                const password = document.getElementById('rawPassword').value;
                const resultDiv = document.getElementById('resultadoHash');
                
                if (!password) {
                    resultDiv.className = 'alert alert-warning';
                    resultDiv.textContent = 'Por favor, ingrese una contraseña.';
                    resultDiv.classList.remove('d-none');
                    return;
                }
                
                // Hacer la solicitud al servidor para generar hash
                fetch('generar_hash.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `password=${encodeURIComponent(password)}`
                })
                .then(response => response.json())
                .then(data => {
                    resultDiv.className = 'alert alert-info';
                    resultDiv.innerHTML = `<p><strong>Hash generado:</strong> ${data.hash}</p>`;
                    resultDiv.classList.remove('d-none');
                })
                .catch(error => {
                    resultDiv.className = 'alert alert-danger';
                    resultDiv.textContent = 'Error al generar hash: ' + error.message;
                    resultDiv.classList.remove('d-none');
                });
            });
        });
    </script>
</body>
</html>