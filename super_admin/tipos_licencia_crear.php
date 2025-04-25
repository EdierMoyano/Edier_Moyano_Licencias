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
$nombre = '';
$descripcion = '';
$duracion_dias = '';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $duracion_dias = (int)$_POST['duracion_dias'];
    
    if (empty($nombre)) {
        $mensaje = "El nombre del tipo de licencia es obligatorio.";
        $tipo_mensaje = "danger";
    } elseif ($duracion_dias <= 0) {
        $mensaje = "La duración debe ser un número positivo de días.";
        $tipo_mensaje = "danger";
    } else {
        try {
            $db = new Database();
            $conn = $db->connect();
            
            // Insertar el tipo de licencia
            $stmt = $conn->prepare("INSERT INTO tipo_licencia (nombre, descripcion, duracion_dias) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $descripcion, $duracion_dias]);
            
            $id_tipo = $conn->lastInsertId();
            
            $mensaje = "Tipo de licencia creado correctamente.";
            $tipo_mensaje = "success";
            
            // Limpiar el formulario
            $nombre = '';
            $descripcion = '';
            $duracion_dias = '';
        } catch (PDOException $e) {
            $mensaje = "Error al crear el tipo de licencia: " . $e->getMessage();
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
    <title>Crear Tipo de Licencia - Super Admin</title>
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
                        <a class="nav-link" href="usuarios.php">Usuarios</a>
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
                            <a class="nav-link" href="usuarios.php">
                                <i class="bi bi-people"></i> Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="tipos_licencia.php">
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
                    <h1 class="h2">Crear Nuevo Tipo de Licencia</h1>
                </div>
                
                <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-tag-fill me-1"></i> Formulario de Registro
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre del Tipo de Licencia *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>
                                <div class="form-text">Ingrese un nombre descriptivo para el tipo de licencia (ej. Demo, Anual, etc.).</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($descripcion); ?></textarea>
                                <div class="form-text">Describa las características de este tipo de licencia.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="duracion_dias" class="form-label">Duración en Días *</label>
                                <input type="number" class="form-control" id="duracion_dias" name="duracion_dias" value="<?php echo $duracion_dias; ?>" min="1" required>
                                <div class="form-text">Ingrese la duración en días para este tipo de licencia (ej. 30, 365, etc.).</div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="tipos_licencia.php" class="btn btn-secondary me-md-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Tipo de Licencia</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>