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
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$empresa = null;
$licencias = [];
$usuarios = [];

// Obtener datos de la empresa
try {
    $db = new Database();
    $conn = $db->connect();
    
    // Obtener información de la empresa
    $stmt = $conn->prepare("SELECT * FROM empresa WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() == 0) {
        header('Location: empresas.php');
        exit;
    }
    
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener licencias de la empresa
    $stmt = $conn->prepare("SELECT l.*, tl.nombre as tipo_licencia, tl.descripcion as tipo_descripcion 
                           FROM licencias l 
                           JOIN tipo_licencia tl ON l.id_tipo_licencia = tl.id 
                           WHERE l.id_empresa = ? 
                           ORDER BY l.fecha_compra DESC");
    $stmt->execute([$id]);
    $licencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener usuarios de la empresa
    $stmt = $conn->prepare("SELECT u.*, r.nombre_rol 
                           FROM usuarios u 
                           JOIN rol r ON u.rol = r.id 
                           WHERE u.id_empresa = ? 
                           ORDER BY u.nombres, u.apellidos");
    $stmt->execute([$id]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Empresa - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
        }
        .badge-lg {
            font-size: 0.9rem;
            padding: 0.5rem 0.7rem;
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
                        <a class="nav-link active" href="empresas.php">Empresas</a>
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
                            <a class="nav-link active" href="empresas.php">
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
                    <h1 class="h2">Detalles de Empresa</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="empresas_editar.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-pencil"></i> Editar Empresa
                            </a>
                            <a href="licencias_crear.php?empresa=<?php echo $id; ?>" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-key-fill"></i> Asignar Licencia
                            </a>
                            <a href="usuarios_crear.php?empresa=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-person-plus"></i> Agregar Usuario
                            </a>
                        </div>
                        <a href="empresas.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                
                <!-- Información de la empresa -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-building me-1"></i> Información de la Empresa
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>ID:</strong> <?php echo $empresa['id']; ?></p>
                                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($empresa['nombre']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Total de Licencias:</strong> <span class="badge bg-primary"><?php echo count($licencias); ?></span></p>
                                <p><strong>Total de Usuarios:</strong> <span class="badge bg-info"><?php echo count($usuarios); ?></span></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Licencias de la empresa -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-key me-1"></i> Licencias Asignadas
                            </h5>
                            <a href="licencias_crear.php?empresa=<?php echo $id; ?>" class="btn btn-sm btn-light">
                                <i class="bi bi-plus-lg"></i> Asignar Nueva Licencia
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($licencias) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tipo</th>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Estado</th>
                                        <th>Fecha Compra</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($licencias as $licencia): ?>
                                    <tr>
                                        <td><?php echo $licencia['id']; ?></td>
                                        <td>
                                            <span data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($licencia['tipo_descripcion']); ?>">
                                                <?php echo htmlspecialchars($licencia['tipo_licencia']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($licencia['fecha_inicio'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($licencia['fecha_fin'])); ?></td>
                                        <td>
                                            <?php if ($licencia['estado'] == 'Activa'): ?>
                                                <span class="badge bg-success">Activa</span>
                                            <?php elseif ($licencia['estado'] == 'Expirada'): ?>
                                                <span class="badge bg-danger">Expirada</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactiva</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($licencia['fecha_compra'])); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="licencias_ver.php?id=<?php echo $licencia['id']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="licencias_editar.php?id=<?php echo $licencia['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i> Esta empresa no tiene licencias asignadas.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Usuarios de la empresa -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-people me-1"></i> Usuarios Registrados
                            </h5>
                            <a href="usuarios_crear.php?empresa=<?php echo $id; ?>" class="btn btn-sm btn-light">
                                <i class="bi bi-plus-lg"></i> Agregar Nuevo Usuario
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($usuarios) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td><?php echo $usuario['id']; ?></td>
                                        <td><?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                        <td>
                                            <?php if ($usuario['rol'] == 1): ?>
                                                <span class="badge bg-danger">Super Admin</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">Admin</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="usuarios_ver.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="usuarios_editar.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i> Esta empresa no tiene usuarios registrados.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>
</html>