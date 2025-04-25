<?php
session_start();

// Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 2) {
    header('Location: ../index.php');
    exit;
}

// Incluir archivo de conexión
require_once '../conexion.php';

// Inicializar variables
$mensaje = '';
$tipo_mensaje = '';
$curso_id = isset($_GET['curso']) ? (int)$_GET['curso'] : 0;

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Obtener lista de cursos para el selector
    $stmt = $conn->prepare("SELECT id, nombre FROM cursos WHERE id_creador = ? ORDER BY nombre");
    $stmt->execute([$_SESSION['user_id']]);
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filtros para asistencias
    $where = [];
    $params = [];
    
    // Filtro por curso
    if ($curso_id > 0) {
        // Verificar que el curso pertenezca al usuario actual
        $stmt = $conn->prepare("SELECT COUNT(*) FROM cursos WHERE id = ? AND id_creador = ?");
        $stmt->execute([$curso_id, $_SESSION['user_id']]);
        
        if ($stmt->fetchColumn() > 0) {
            $where[] = "a.curso_id = :curso_id";
            $params[':curso_id'] = $curso_id;
            
            // Obtener información del curso
            $stmt = $conn->prepare("SELECT * FROM cursos WHERE id = ?");
            $stmt->execute([$curso_id]);
            $curso_actual = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $curso_id = 0;
        }
    }
    
    // Filtro por fecha
    if (isset($_GET['fecha_desde']) && !empty($_GET['fecha_desde'])) {
        $fecha_desde = $_GET['fecha_desde'];
        $where[] = "DATE(a.fecha_hora) >= :fecha_desde";
        $params[':fecha_desde'] = $fecha_desde;
    } else {
        $fecha_desde = '';
    }
    
    if (isset($_GET['fecha_hasta']) && !empty($_GET['fecha_hasta'])) {
        $fecha_hasta = $_GET['fecha_hasta'];
        $where[] = "DATE(a.fecha_hora) <= :fecha_hasta";
        $params[':fecha_hasta'] = $fecha_hasta;
    } else {
        $fecha_hasta = '';
    }
    
    // Búsqueda por nombre o documento
    if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
        $busqueda = $_GET['buscar'];
        $where[] = "(a.nombre_usu LIKE :busqueda OR a.documento LIKE :busqueda)";
        $params[':busqueda'] = "%$busqueda%";
    }
    
    // Construir la consulta
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    // Paginación
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $por_pagina = 20;
    $inicio = ($pagina - 1) * $por_pagina;
    
    // Contar total de registros
    $query_count = "SELECT COUNT(*) FROM asistencia a 
                    LEFT JOIN cursos c ON a.curso_id = c.id 
                    $whereClause";
    $stmt = $conn->prepare($query_count);
    $stmt->execute($params);
    $total_registros = $stmt->fetchColumn();
    $total_paginas = ceil($total_registros / $por_pagina);
    
    // Obtener asistencias
    $query = "SELECT a.*, c.nombre as curso_nombre 
              FROM asistencia a 
              LEFT JOIN cursos c ON a.curso_id = c.id 
              $whereClause 
              ORDER BY a.fecha_hora DESC 
              LIMIT $inicio, $por_pagina";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $mensaje = "Error: " . $e->getMessage();
    $tipo_mensaje = "danger";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Asistencias - Administrador</title>
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
                        <a class="nav-link" href="cursos.php">
                            <i class="bi bi-book"></i> Cursos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="asistencias.php">
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
                            <a class="nav-link" href="cursos.php">
                                <i class="bi bi-book"></i> Cursos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="asistencias.php">
                                <i class="bi bi-calendar-check"></i> Asistencias
                            </a>
                        </li>
                        
                    </ul>
                </div>
            </div>
            
            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Asistencias</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="asistencias_registrar.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-lg"></i> Registrar Asistencia
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-funnel me-1"></i> Filtros y Búsqueda
                    </div>
                    <div class="card-body">
                        <form action="" method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="curso" class="form-label">Curso</label>
                                <select class="form-select" id="curso" name="curso">
                                    <option value="">Todos los cursos</option>
                                    <?php foreach ($cursos as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo ($curso_id == $c['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="fecha_desde" class="form-label">Fecha Desde</label>
                                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="<?php echo $fecha_desde; ?>">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>">
                            </div>
                            
                            <div class="col-md-12">
                                <label for="buscar" class="form-label">Buscar</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="buscar" name="buscar" placeholder="Buscar por nombre o documento..." value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                            
                            <div class="col-12 d-flex justify-content-end">
                                <?php if (isset($_GET['curso']) || isset($_GET['fecha_desde']) || isset($_GET['fecha_hasta']) || isset($_GET['buscar'])): ?>
                                <a href="asistencias.php" class="btn btn-secondary">Limpiar filtros</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Información del curso seleccionado -->
                <?php if ($curso_id > 0 && isset($curso_actual)): ?>
                <div class="alert alert-info mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Curso: <?php echo htmlspecialchars($curso_actual['nombre']); ?></h5>
                            <p class="mb-0">
                                <strong>Fechas:</strong> <?php echo date('d/m/Y', strtotime($curso_actual['f_inicio'])); ?> - <?php echo date('d/m/Y', strtotime($curso_actual['f_fin'])); ?>
                                <br>
                                <strong>Lugar:</strong> <?php echo htmlspecialchars($curso_actual['lugar']); ?>
                            </p>
                        </div>
                        <div>
                            <a href="asistencias_registrar.php?curso=<?php echo $curso_id; ?>" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Registrar Asistencia
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Tabla de asistencias -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-table me-1"></i> Listado de Asistencias
                        <span class="badge bg-primary ms-2"><?php echo $total_registros; ?> registros</span>
                    </div>
                    <div class="card-body">
                        <?php if (count($asistencias) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha y Hora</th>
                                        <th>Nombre</th>
                                        <th>Documento</th>
                                        <th>Curso</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($asistencias as $asistencia): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($asistencia['fecha_hora'])); ?></td>
                                        <td><?php echo htmlspecialchars($asistencia['nombre_usu']); ?></td>
                                        <td><?php echo htmlspecialchars($asistencia['documento']); ?></td>
                                        <td>
                                            <a href="cursos_ver.php?id=<?php echo $asistencia['curso_id']; ?>">
                                                <?php echo htmlspecialchars($asistencia['curso_nombre']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($asistencia['observaciones']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginación -->
                        <?php if ($total_paginas > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($pagina <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?><?php echo isset($_GET['curso']) ? '&curso=' . $_GET['curso'] : ''; ?><?php echo isset($_GET['fecha_desde']) ? '&fecha_desde=' . $_GET['fecha_desde'] : ''; ?><?php echo isset($_GET['fecha_hasta']) ? '&fecha_hasta=' . $_GET['fecha_hasta'] : ''; ?><?php echo isset($_GET['buscar']) ? '&buscar=' . urlencode($_GET['buscar']) : ''; ?>" aria-label="Anterior">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?php echo ($pagina == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo isset($_GET['curso']) ? '&curso=' . $_GET['curso'] : ''; ?><?php echo isset($_GET['fecha_desde']) ? '&fecha_desde=' . $_GET['fecha_desde'] : ''; ?><?php echo isset($_GET['fecha_hasta']) ? '&fecha_hasta=' . $_GET['fecha_hasta'] : ''; ?><?php echo isset($_GET['buscar']) ? '&buscar=' . urlencode($_GET['buscar']) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo ($pagina >= $total_paginas) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?><?php echo isset($_GET['curso']) ? '&curso=' . $_GET['curso'] : ''; ?><?php echo isset($_GET['fecha_desde']) ? '&fecha_desde=' . $_GET['fecha_desde'] : ''; ?><?php echo isset($_GET['fecha_hasta']) ? '&fecha_hasta=' . $_GET['fecha_hasta'] : ''; ?><?php echo isset($_GET['buscar']) ? '&buscar=' . urlencode($_GET['buscar']) : ''; ?>" aria-label="Siguiente">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                        
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i> No se encontraron registros de asistencia.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
