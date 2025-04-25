<?php
session_start();

// Verificar si el usuario está logueado y es super admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: index.php');
    exit;
}

// Incluir archivo de conexión
require_once '../conexion.php';

// Inicializar variables
$mensaje = '';
$tipo_mensaje = '';
$id = '';
$nombres = '';
$apellidos = '';
$codigo_barras = '';
$email = '';
$id_empresa = isset($_GET['empresa']) ? (int)$_GET['empresa'] : '';
$rol = '';

// Generar código de barras aleatorio de 10 dígitos
function generarCodigoBarras() {
    return str_pad(mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
}

// Obtener empresas y roles
try {
    $db = new Database();
    $conn = $db->connect();
    
    // Obtener empresas
    $stmt = $conn->query("SELECT id, nombre FROM empresa ORDER BY nombre");
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener roles
    $stmt = $conn->query("SELECT id, nombre_rol FROM rol ORDER BY id");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generar código de barras único
    $codigo_barras = generarCodigoBarras();
    $codigo_unico = false;
    
    while (!$codigo_unico) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE codigo_barras = ?");
        $stmt->execute([$codigo_barras]);
        if ($stmt->fetchColumn() == 0) {
            $codigo_unico = true;
        } else {
            $codigo_barras = generarCodigoBarras();
        }
    }
    
} catch (PDOException $e) {
    $mensaje = "Error al obtener datos: " . $e->getMessage();
    $tipo_mensaje = "danger";
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = trim($_POST['id']);
    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    $codigo_barras = trim($_POST['codigo_barras']);
    $email = trim($_POST['email']);
    $contraseña = trim($_POST['contraseña']);
    $confirmar_contraseña = trim($_POST['confirmar_contraseña']);
    $id_empresa = !empty($_POST['id_empresa']) ? (int)$_POST['id_empresa'] : null;
    $rol = (int)$_POST['rol'];
    
    // Validaciones
    $errores = [];
    
    if (empty($id)) {
        $errores[] = "El documento de identidad es obligatorio.";
    }
    
    if (empty($nombres)) {
        $errores[] = "Los nombres son obligatorios.";
    }
    
    if (empty($apellidos)) {
        $errores[] = "Los apellidos son obligatorios.";
    }
    
    if (empty($email)) {
        $errores[] = "El email es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El formato del email no es válido.";
    }
    
    if (empty($contraseña)) {
        $errores[] = "La contraseña es obligatoria.";
    } elseif (strlen($contraseña) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres.";
    }
    
    if ($contraseña !== $confirmar_contraseña) {
        $errores[] = "Las contraseñas no coinciden.";
    }
    
    if (empty($rol)) {
        $errores[] = "El rol es obligatorio.";
    }
    
    // Verificar si el usuario ya existe
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $errores[] = "Ya existe un usuario con ese documento de identidad.";
        }
        
        $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errores[] = "Ya existe un usuario con ese email.";
        }
    } catch (PDOException $e) {
        $errores[] = "Error al verificar usuario: " . $e->getMessage();
    }
    
    if (empty($errores)) {
        try {
            // Encriptar contraseña
            $contraseña_hash = password_hash($contraseña, PASSWORD_DEFAULT);
            
            // Insertar usuario
            $stmt = $conn->prepare("INSERT INTO usuarios (id, nombres, apellidos, codigo_barras, email, contraseña, id_empresa, rol) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $nombres, $apellidos, $codigo_barras, $email, $contraseña_hash, $id_empresa, $rol]);
            
            $mensaje = "Usuario creado correctamente.";
            $tipo_mensaje = "success";
            
            // Redirigir a la página de ver usuario
            header("Location: usuarios_ver.php?id=$id&mensaje=creado");
            exit;
        } catch (PDOException $e) {
            $mensaje = "Error al crear el usuario: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    } else {
        $mensaje = implode("<br>", $errores);
        $tipo_mensaje = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-shield-lock"></i> Panel Super Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="empresas.php">Empresas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="licencias.php">Licencias</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="usuarios.php">Usuarios</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="empresas.php">
                                <i class="bi bi-building"></i> Empresas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="licencias.php">
                                <i class="bi bi-key"></i> Licencias
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="usuarios.php">
                                <i class="bi bi-people"></i> Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="tipos_licencia.php">
                                <i class="bi bi-tags"></i> Tipos de Licencia
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reportes.php">
                                <i class="bi bi-file-earmark-bar-graph"></i> Reportes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="configuracion.php">
                                <i class="bi bi-gear"></i> Configuración
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Crear Nuevo Usuario</h1>
                </div>
                
                <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-person-plus-fill me-1"></i> Formulario de Registro
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="id" class="form-label">Documento de Identidad *</label>
                                    <input type="text" class="form-control" id="id" name="id" value="<?php echo htmlspecialchars($id); ?>" required>
                                    <div class="form-text">Ingrese el número de documento sin puntos ni guiones.</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="codigo_barras" class="form-label">Código de Barras</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="codigo_barras" name="codigo_barras" value="<?php echo htmlspecialchars($codigo_barras); ?>" readonly>
                                        <button type="button" class="btn btn-outline-secondary" onclick="generarNuevoCodigo()">
                                            <i class="bi bi-arrow-repeat"></i> Generar nuevo
                                        </button>
                                    </div>
                                    <div class="form-text">Código de barras generado automáticamente (10 dígitos).</div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nombres" class="form-label">Nombres *</label>
                                    <input type="text" class="form-control" id="nombres" name="nombres" value="<?php echo htmlspecialchars($nombres); ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="apellidos" class="form-label">Apellidos *</label>
                                    <input type="text" class="form-control" id="apellidos" name="apellidos" value="<?php echo htmlspecialchars($apellidos); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="id_empresa" class="form-label">Empresa</label>
                                    <select class="form-select" id="id_empresa" name="id_empresa">
                                        <option value="">Sin empresa</option>
                                        <?php foreach ($empresas as $empresa): ?>
                                        <option value="<?php echo $empresa['id']; ?>" <?php echo ($id_empresa == $empresa['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($empresa['nombre']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="contraseña" class="form-label">Contraseña *</label>
                                    <input type="password" class="form-control" id="contraseña" name="contraseña" required>
                                    <div class="form-text">Mínimo 6 caracteres.</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="confirmar_contraseña" class="form-label">Confirmar Contraseña *</label>
                                    <input type="password" class="form-control" id="confirmar_contraseña" name="confirmar_contraseña" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="rol" class="form-label">Rol *</label>
                                    <select class="form-select" id="rol" name="rol" required>
                                        <option value="">Seleccione un rol</option>
                                        <?php foreach ($roles as $r): ?>
                                        <option value="<?php echo $r['id']; ?>" <?php echo ($rol == $r['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($r['nombre_rol']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="usuarios.php" class="btn btn-secondary me-md-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Usuario</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function generarNuevoCodigo() {
            // Generar un código aleatorio de 10 dígitos
            let codigo = '';
            for (let i = 0; i < 10; i++) {
                codigo += Math.floor(Math.random() * 10);
            }
            document.getElementById('codigo_barras').value = codigo;
        }
    </script>
</body>
</html>