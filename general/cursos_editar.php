<?php
session_start();

// Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 2) {
    header('Location: ../index.php');
    exit;
}

// Incluir archivo de conexión
require_once '../conexion.php';

// Verificar si se proporcionó un ID de curso
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: cursos.php');
    exit;
}

$curso_id = (int)$_GET['id'];
$mensaje = '';
$tipo_mensaje = '';

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Obtener información del curso
    $stmt = $conn->prepare("SELECT * FROM cursos WHERE id = ? AND id_creador = ?");
    $stmt->execute([$curso_id, $_SESSION['user_id']]);
    
    if ($stmt->rowCount() == 0) {
        // El curso no existe o no pertenece al usuario actual
        header('Location: cursos.php');
        exit;
    }
    
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Inicializar variables con los datos del curso
    $nombre = $curso['nombre'];
    $descripcion = $curso['descripcion'];
    $f_inicio = $curso['f_inicio'];
    $f_fin = $curso['f_fin'];
    $lugar = $curso['lugar'];
    
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
    exit;
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $f_inicio = $_POST['f_inicio'];
    $f_fin = $_POST['f_fin'];
    $lugar = trim($_POST['lugar']);
    
    // Validaciones
    $errores = [];
    
    if (empty($nombre)) {
        $errores[] = "El nombre del curso es obligatorio.";
    }
    
    if (empty($f_inicio)) {
        $errores[] = "La fecha de inicio es obligatoria.";
    }
    
    if (empty($f_fin)) {
        $errores[] = "La fecha de fin es obligatoria.";
    }
    
    if (strtotime($f_fin) < strtotime($f_inicio)) {
        $errores[] = "La fecha de fin no puede ser anterior a la fecha de inicio.";
    }
    
    if (empty($lugar)) {
        $errores[] = "El lugar es obligatorio.";
    }
    
    if (empty($errores)) {
        try {
            // Actualizar curso
            $stmt = $conn->prepare("UPDATE cursos SET nombre = ?, descripcion = ?, f_inicio = ?, f_fin = ?, lugar = ? WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $f_inicio, $f_fin, $lugar, $curso_id]);
            
            $mensaje = "Curso actualizado correctamente.";
            $tipo_mensaje = "success";
            
            // Redirigir a la página de ver curso
            header("Location: cursos_ver.php?id=$curso_id&mensaje=actualizado");
            exit;
        } catch (PDOException $e) {
            $mensaje = "Error al actualizar el curso: " . $e->getMessage();
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
    <title>Editar Curso - Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building"></i> Panel Administrador
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="cursos.php">
                            <i class="bi bi-book"></i> Cursos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="asistencias.php">
                            <i class="bi bi-calendar-check"></i> Asistencias
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="perfil.php">Mi Perfil</a></li>
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
                            <a class="nav-link active" href="cursos.php">
                                <i class="bi bi-book"></i> Cursos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="asistencias.php">
                                <i class="bi bi-calendar-check"></i> Asistencias
                            </a>
                        </li>
                        
                    </ul>
                </div>
            </div>
            
            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Editar Curso</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="cursos_ver.php?id=<?php echo $curso_id; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-pencil-fill me-1"></i> Formulario de Edición
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="nombre" class="form-label">Nombre del Curso *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($descripcion); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="f_inicio" class="form-label">Fecha de Inicio *</label>
                                    <input type="date" class="form-control" id="f_inicio" name="f_inicio" value="<?php echo $f_inicio; ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="f_fin" class="form-label">Fecha de Fin *</label>
                                    <input type="date" class="form-control" id="f_fin" name="f_fin" value="<?php echo $f_fin; ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="lugar" class="form-label">Lugar *</label>
                                    <input type="text" class="form-control" id="lugar" name="lugar" value="<?php echo htmlspecialchars($lugar); ?>" required>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="cursos_ver.php?id=<?php echo $curso_id; ?>" class="btn btn-secondary me-md-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
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