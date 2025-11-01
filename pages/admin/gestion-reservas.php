<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = "Gestión de Reservas";
include '../../includes/header.php';
include '../../includes/database.php';

$database = new Database();
$conn = $database->getConnection();

// Acciones
$action = $_GET['action'] ?? '';
$reserva_id = $_GET['id'] ?? 0;

// Cambiar estado de reserva
if($action && $reserva_id) {
    $estados_validos = ['pendiente', 'confirmada', 'completada', 'cancelada'];
    if(in_array($action, $estados_validos)) {
        $stmt = $conn->prepare("UPDATE reservas SET estado = :estado WHERE id = :id");
        $stmt->bindParam(':estado', $action);
        $stmt->bindParam(':id', $reserva_id);
        $stmt->execute();
        header('Location: gestion-reservas.php?message=updated');
        exit;
    }
}

// Obtener reservas
$stmt = $conn->prepare("
    SELECT r.*, s.titulo as servicio_titulo, p.nombre_empresa, 
           u.nombre as usuario_nombre, u.email as usuario_email,
           c.nombre as categoria_nombre
    FROM reservas r
    JOIN servicios s ON r.servicio_id = s.id
    JOIN proveedores p ON s.proveedor_id = p.id
    JOIN usuarios u ON r.usuario_id = u.id
    JOIN categorias c ON p.categoria_id = c.id
    ORDER BY r.created_at DESC
");
$stmt->execute();
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-dashboard">
    <div class="admin-sidebar">
        <h2>Panel Admin</h2>
        <nav class="admin-nav">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="gestion-servicios.php">🛍️ Servicios</a>
            <a href="gestion-proveedores.php">👥 Proveedores</a>
            <a href="gestion-reservas.php" class="active">📅 Reservas</a>
            <a href="gestion-usuarios.php">👤 Usuarios</a>
            <a href="configuracion.php">⚙️ Configuración</a>
        </nav>
    </div>

    <div class="admin-content">
        <div class="admin-header">
            <h1>Gestión de Reservas</h1>
            <p>Administra todas las reservas de la plataforma</p>

            <div class="admin-actions">
                <a href="exportar-reservas.php" class="btn btn-secondary">📊 Exportar CSV</a>
                <a href="reporte-reservas.php" class="btn btn-primary">📈 Generar Reporte</a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="admin-filters">
            <select id="filterEstado">
                <option value="">Todos los estados</option>
                <option value="pendiente">Pendientes</option>
                <option value="confirmada">Confirmadas</option>
                <option value="completada">Completadas</option>
                <option value="cancelada">Canceladas</option>
            </select>
            <input type="date" id="filterFechaInicio" placeholder="Fecha inicio">
            <input type="date" id="filterFechaFin" placeholder="Fecha fin">
            <button class="btn btn-outline" onclick="aplicarFiltros()">Aplicar Filtros</button>
        </div>

        <!-- Estadísticas -->
        <div class="admin-stats">
            <div class="stat-card">
                <h3>Total Reservas</h3>
                <span class="stat-number"><?= count($reservas) ?></span>
            </div>
            <div class="stat-card">
                <h3>Ingresos Totales</h3>
                <span class="stat-number">
                    $<?= number_format(array_sum(array_column($reservas, 'total')), 2) ?>
                </span>
            </div>
            <div class="stat-card">
                <h3>Reservas Hoy</h3>
                <span class="stat-number">
                    <?= count(array_filter($reservas, function($r) { 
                        return date('Y-m-d', strtotime($r['created_at'])) == date('Y-m-d'); 
                    })) ?>
                </span>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if(isset($_GET['message'])): ?>
        <div class="alert alert-success">
            Reserva actualizada correctamente
        </div>
        <?php endif; ?>

        <!-- Tabla de reservas -->
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Servicio</th>
                        <th>Cliente</th>
                        <th>Proveedor</th>
                        <th>Fechas</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th>Fecha Reserva</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($reservas as $reserva): ?>
                    <tr>
                        <td>#<?= str_pad($reserva['id'], 6, '0', STR_PAD_LEFT) ?></td>
                        <td>
                            <div class="reserva-cell">
                                <strong><?= $reserva['servicio_titulo'] ?></strong>
                                <small><?= $reserva['categoria_nombre'] ?></small>
                            </div>
                        </td>
                        <td>
                            <div class="usuario-cell">
                                <strong><?= $reserva['usuario_nombre'] ?></strong>
                                <small><?= $reserva['usuario_email'] ?></small>
                            </div>
                        </td>
                        <td><?= $reserva['nombre_empresa'] ?></td>
                        <td>
                            <div class="fechas-cell">
                                <div>Inicio: <?= date('d/m/Y H:i', strtotime($reserva['fecha_inicio'])) ?></div>
                                <div>Fin: <?= date('d/m/Y H:i', strtotime($reserva['fecha_fin'])) ?></div>
                            </div>
                        </td>
                        <td>$<?= number_format($reserva['total'], 2) ?></td>
                        <td>
                            <span class="status-badge estado-<?= $reserva['estado'] ?>">
                                <?= ucfirst($reserva['estado']) ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($reserva['created_at'])) ?></td>
                        <td>
                            <div class="action-buttons">
                                <!-- Cambiar estados -->
                                <?php if($reserva['estado'] == 'pendiente'): ?>
                                <a href="?action=confirmada&id=<?= $reserva['id'] ?>" class="btn-action btn-confirm"
                                    title="Confirmar">
                                    ✅
                                </a>
                                <a href="?action=cancelada&id=<?= $reserva['id'] ?>" class="btn-action btn-cancel"
                                    title="Cancelar" onclick="return confirm('¿Cancelar esta reserva?')">
                                    ❌
                                </a>
                                <?php elseif($reserva['estado'] == 'confirmada'): ?>
                                <a href="?action=completada&id=<?= $reserva['id'] ?>" class="btn-action btn-complete"
                                    title="Marcar como completada">
                                    🏁
                                </a>
                                <?php endif; ?>

                                <a href="../reservas/detalle-reserva.php?id=<?= $reserva['id'] ?>"
                                    class="btn-action btn-view" title="Ver detalles">
                                    👁️
                                </a>

                                <a href="editar-reserva.php?id=<?= $reserva['id'] ?>" class="btn-action btn-edit"
                                    title="Editar">
                                    ✏️
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function aplicarFiltros() {
    const estado = document.getElementById('filterEstado').value;
    const fechaInicio = document.getElementById('filterFechaInicio').value;
    const fechaFin = document.getElementById('filterFechaFin').value;

    document.querySelectorAll('.admin-table tbody tr').forEach(row => {
        let show = true;

        if (estado) {
            const rowEstado = row.querySelector('.status-badge').textContent.toLowerCase();
            if (rowEstado !== estado) show = false;
        }

        // Implementar filtros por fecha
        // ...

        row.style.display = show ? '' : 'none';
    });
}

// Inicializar filtros
document.getElementById('filterEstado').addEventListener('change', aplicarFiltros);
</script>

<?php include '../../includes/footer.php'; ?>