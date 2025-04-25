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
$nombre_usu = '';
$documento = '';
$observaciones = '';

// Obtener información del curso
try {
    $db = new Database();
    $conn = $db->connect();
    
    if ($curso_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM cursos WHERE id = ? AND id_creador = ?");
        $stmt->execute([$curso_id, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() == 0) {
            $mensaje = "No tiene acceso a este curso o no existe.";
            $tipo_mensaje = "danger";
            $curso_id = 0;
        } else {
            $curso = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    
    // Obtener lista de cursos para el selector
    $stmt = $conn->prepare("SELECT id, nombre FROM cursos WHERE id_creador = ? ORDER BY nombre");
    $stmt->execute([$_SESSION['user_id']]);
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener asistencias del curso seleccionado
    if ($curso_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM asistencia WHERE curso_id = ? ORDER BY fecha_hora DESC");
        $stmt->execute([$curso_id]);
        $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    $mensaje = "Error al obtener datos: " . $e->getMessage();
    $tipo_mensaje = "danger";
}

// Procesar la consulta de usuario por código de barras (AJAX)
if (isset($_POST['action']) && $_POST['action'] == 'buscar_usuario') {
    $documento = trim($_POST['documento']);
    
    try {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("SELECT id, nombres, apellidos FROM usuarios WHERE id = ?");
        $stmt->execute([$documento]);
        
        if ($stmt->rowCount() > 0) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'success' => true,
                'usuario' => $usuario
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'mensaje' => 'No se encontró ningún usuario con ese documento'
            ]);
        }
        exit;
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error al buscar usuario: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['action'])) {
    $curso_id = (int)$_POST['curso_id'];
    $nombre_usu = trim($_POST['nombre_usu']);
    $documento = trim($_POST['documento']);
    $observaciones = trim($_POST['observaciones']);
    
    // Validaciones
    $errores = [];
    
    if (empty($curso_id)) {
        $errores[] = "Debe seleccionar un curso.";
    }
    
    if (empty($nombre_usu)) {
        $errores[] = "El nombre del asistente es obligatorio.";
    }
    
    if (empty($documento)) {
        $errores[] = "El documento de identidad es obligatorio.";
    }
    
    if (empty($errores)) {
        try {
            $db = new Database();
            $conn = $db->connect();
            
            // Verificar que el curso pertenezca al usuario actual
            $stmt = $conn->prepare("SELECT COUNT(*) FROM cursos WHERE id = ? AND id_creador = ?");
            $stmt->execute([$curso_id, $_SESSION['user_id']]);
            
            if ($stmt->fetchColumn() > 0) {
                // Insertar asistencia
                $stmt = $conn->prepare("INSERT INTO asistencia (curso_id, fecha_hora, nombre_usu, documento, observaciones) VALUES (?, NOW(), ?, ?, ?)");
                $stmt->execute([$curso_id, $nombre_usu, $documento, $observaciones]);
                
                $mensaje = "Asistencia registrada correctamente.";
                $tipo_mensaje = "success";
                
                // Limpiar campos
                $nombre_usu = '';
                $documento = '';
                $observaciones = '';
                
                // Actualizar lista de asistencias
                $stmt = $conn->prepare("SELECT * FROM asistencia WHERE curso_id = ? ORDER BY fecha_hora DESC");
                $stmt->execute([$curso_id]);
                $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $mensaje = "No tiene permisos para registrar asistencia en este curso.";
                $tipo_mensaje = "danger";
            }
        } catch (PDOException $e) {
            $mensaje = "Error al registrar la asistencia: " . $e->getMessage();
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
    <title>Registro de Asistencias - Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
        }
        .scanner-container {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .scanner-container .form-label {
            font-weight: bold;
        }
        .scanner-icon {
            font-size: 1.5rem;
            margin-right: 0.5rem;
            color: #0d6efd;
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
                    <h1 class="h2">Registro de Asistencias</h1>
                </div>
                
                <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Selector de curso -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-book me-1"></i> Seleccionar Curso
                    </div>
                    <div class="card-body">
                        <form action="" method="GET" class="row g-3">
                            <div class="col-md-8">
                                <select class="form-select" id="curso" name="curso" required>
                                    <option value="">Seleccione un curso</option>
                                    <?php foreach ($cursos as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo ($curso_id == $c['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> Seleccionar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if ($curso_id > 0): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-upc-scan me-1"></i> Escanear Código de Barras
                    </div>
                    <div class="card-body">
                        <div class="scanner-container">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <label for="codigo_escaneado" class="form-label">
                                        <i class="bi bi-upc-scan scanner-icon"></i>Escanee el código de barras
                                    </label>
                                    <input type="text" class="form-control form-control-lg" id="codigo_escaneado" 
                                           placeholder="Escanee o ingrese el documento de identidad" autofocus>
                                    <div class="form-text">Escanee el código de barras o ingrese manualmente el documento de identidad</div>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <button type="button" id="btn_consultar" class="btn btn-primary btn-lg mt-3">
                                        <i class="bi bi-search me-1"></i> Consultar
                                    </button>
                                    <div id="spinner" class="spinner-border text-primary ms-3 d-none" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                            </div>
                            <div id="resultado_consulta" class="mt-3"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Formulario de registro de asistencia -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-person-plus-fill me-1"></i> Registrar Asistencia para: <?php echo htmlspecialchars($curso['nombre']); ?>
                    </div>
                    <div class="card-body">
                        <form method="post" action="" name="form1" id="form_asistencia">
                            <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nombre_usu" class="form-label">Nombre Completo *</label>
                                    <input type="text" class="form-control" id="nombre_usu" name="nombre_usu" value="<?php echo htmlspecialchars($nombre_usu); ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="documento" class="form-label">Documento de Identidad *</label>
                                    <input type="text" class="form-control" id="documento" name="documento" value="<?php echo htmlspecialchars($documento); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="observaciones" class="form-label">Observaciones</label>
                                    <textarea class="form-control" id="observaciones" name="observaciones" rows="2"><?php echo htmlspecialchars($observaciones); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-person-check"></i> Registrar Asistencia
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Listado de asistencias -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-list-check me-1"></i> Asistencias Registradas
                    </div>
                    <div class="card-body">
                        <?php if (isset($asistencias) && count($asistencias) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha y Hora</th>
                                        <th>Nombre</th>
                                        <th>Documento</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($asistencias as $asistencia): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($asistencia['fecha_hora'])); ?></td>
                                        <td><?php echo htmlspecialchars($asistencia['nombre_usu']); ?></td>
                                        <td><?php echo htmlspecialchars($asistencia['documento']); ?></td>
                                        <td><?php echo htmlspecialchars($asistencia['observaciones']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i> No hay asistencias registradas para este curso.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enfocar el campo de escaneo al cargar la página
            const codigoEscaneadoInput = document.getElementById('codigo_escaneado');
            if (codigoEscaneadoInput) {
                codigoEscaneadoInput.focus();
            }
            
            // Manejar el evento de escaneo (cuando se presiona Enter después de escanear)
            codigoEscaneadoInput?.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    consultarUsuario();
                }
            });
            
            // Manejar el clic en el botón de consultar
            const btnConsultar = document.getElementById('btn_consultar');
            btnConsultar?.addEventListener('click', function() {
                consultarUsuario();
            });
            
            // Función para consultar usuario por documento
            function consultarUsuario() {
                const codigo = codigoEscaneadoInput.value.trim();
                if (!codigo) {
                    mostrarResultado('error', 'Por favor ingrese o escanee un documento de identidad');
                    return;
                }
                
                // Mostrar spinner
                document.getElementById('spinner').classList.remove('d-none');
                document.getElementById('resultado_consulta').innerHTML = '';
                
                // Realizar la consulta AJAX
                const formData = new FormData();
                formData.append('action', 'buscar_usuario');
                formData.append('documento', codigo);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Ocultar spinner
                    document.getElementById('spinner').classList.add('d-none');
                    
                    if (data.success) {
                        // Autocompletar el formulario
                        document.getElementById('documento').value = data.usuario.id;
                        document.getElementById('nombre_usu').value = data.usuario.nombres + ' ' + data.usuario.apellidos;
                        
                        // Mostrar mensaje de éxito
                        mostrarResultado('success', 'Usuario encontrado. Se han completado los datos automáticamente.');
                        
                        // Enfocar el campo de observaciones
                        document.getElementById('observaciones').focus();
                    } else {
                        // Mostrar mensaje de error
                        mostrarResultado('error', data.mensaje);
                        
                        // Completar solo el campo de documento
                        document.getElementById('documento').value = codigo;
                        document.getElementById('nombre_usu').value = '';
                        document.getElementById('nombre_usu').focus();
                    }
                })
                .catch(error => {
                    // Ocultar spinner
                    document.getElementById('spinner').classList.add('d-none');
                    mostrarResultado('error', 'Error al procesar la solicitud: ' + error);
                });
            }
            
            // Función para mostrar resultados
            function mostrarResultado(tipo, mensaje) {
                const resultadoDiv = document.getElementById('resultado_consulta');
                const alertClass = tipo === 'success' ? 'alert-success' : 'alert-danger';
                const icon = tipo === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle';
                
                resultadoDiv.innerHTML = `
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                        <i class="bi ${icon} me-2"></i> ${mensaje}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
            }
            
            // Limpiar el campo de escaneo después de enviar el formulario
            const formAsistencia = document.getElementById('form_asistencia');
            formAsistencia?.addEventListener('submit', function() {
                if (codigoEscaneadoInput) {
                    codigoEscaneadoInput.value = '';
                }
            });
        });
    </script>
</body>
</html>