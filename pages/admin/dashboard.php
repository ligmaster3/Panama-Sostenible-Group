<?php
// Verificar autenticación y permisos de admin
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = "Panel de Administración";
include '../includes/header.php';
include '../includes/database.php';

$database = new Database();
$conn = $database->getConnection();

// Estadísticas
$stats = [
    'total_servicios' => $conn->query("SELECT COUNT(*) FROM servicios")->fetchColumn(),
    'total_proveedores' => $conn->query("SELECT COUNT(*) FROM proveedores")->fetchColumn(),
    'total_reservas' => $conn->query("SELECT COUNT(*) FROM reservas")->fetchColumn(),
    'ingresos_totales' => $conn->query("SELECT COALESCE(SUM(total), 0) FROM reservas WHERE estado = 'completada'")->fetchColumn()
];
?>

<div class="admin-dashboard">
    <div class="admin-sidebar">
        <h2>Panel Admin</h2>
        <nav class="admin-nav">
            <a href="dashboard.php" class="active">📊 Dashboard</a>
            <a href="gestion-servicios.php">🛍️ Servicios</a>
            <a href="gestion-proveedores.php">👥 Proveedores</a>
            <a href="gestion-reservas.php">📅 Reservas</a>
            <a href="gestion-usuarios.php">👤 Usuarios</a>
            <a href="configuracion.php">⚙️ Configuración</a>
        </nav>
    </div>

    <div class="admin-content">
        <div class="admin-header">
            <h1>Dashboard</h1>
            <p>Bienvenido al panel de administración</p>
        </div>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🛍️</div>
                <div class="stat-info">
                    <h3><?= $stats['total_servicios'] ?></h3>
                    <p>Total Servicios</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <h3><?= $stats['total_proveedores'] ?></h3>
                    <p>Proveedores</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-info">
                    <h3><?= $stats['total_reservas'] ?></h3>
                    <p>Reservas</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <h3>$<?= number_format($stats['ingresos_totales'], 2) ?></h3>
                    <p>Ingresos Totales</p>
                </div>
            </div>
        </div>

        <!-- Gráficos y tablas recientes -->
        <div class="dashboard-sections">
            <div class="recent-activity">
                <h3>Actividad Reciente</h3>
                <!-- Tabla de actividad reciente -->
            </div>

            <div class="quick-actions">
                <h3>Acciones Rápidas</h3>
                <div class="action-buttons">
                    <a href="gestion-servicios.php?action=add" class="btn btn-primary">➕ Agregar Servicio</a>
                    <a href="gestion-proveedores.php?action=verify" class="btn btn-secondary">✅ Verificar
                        Proveedores</a>
                    <a href="reportes.php" class="btn btn-outline">📊 Generar Reportes</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>