<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$pageTitle = "Mis Reservas";
include '../../includes/header.php';
include '../../includes/database.php';

$database = new Database();
$conn = $database->getConnection();

$user_id = $_SESSION['user_id'];

// Obtener reservas del usuario
$stmt = $conn->prepare("
    SELECT r.*, s.titulo, s.imagenes, p.nombre_empresa, p.telefono, p.email,
           c.nombre as categoria, r.total as monto_total
    FROM reservas r
    JOIN servicios s ON r.servicio_id = s.id
    JOIN proveedores p ON s.proveedor_id = p.id
    JOIN categorias c ON p.categoria_id = c.id
    WHERE r.usuario_id = :user_id
    ORDER BY r.created_at DESC
");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <div class="container">
        <h1>Mis Reservas</h1>
        <p>Gestiona y revisa todas tus reservas</p>
    </div>
</div>

<div class="container">
    <div class="reservas-container">
        <!-- Filtros de estado -->
        <div class="reservas-filters">
            <button class="filter-btn active" data-filter="todas">Todas</button>
            <button class="filter-btn" data-filter="pendiente">Pendientes</button>
            <button class="filter-btn" data-filter="confirmada">Confirmadas</button>
            <button class="filter-btn" data-filter="completada">Completadas</button>
            <button class="filter-btn" data-filter="cancelada">Canceladas</button>
        </div>

        <!-- Lista de reservas -->
        <div class="reservas-list">
            <?php if(empty($reservas)): ?>
            <div class="no-reservas">
                <h3>No tienes reservas aún</h3>
                <p>Explora nuestros servicios y haz tu primera reserva</p>
                <a href="../servicios.php" class="btn btn-primary">Explorar Servicios</a>
            </div>
            <?php else: ?>
            <?php foreach($reservas as $reserva): ?>
            <div class="reserva-card" data-estado="<?= $reserva['estado'] ?>">
                <div class="reserva-header">
                    <div class="reserva-info">
                        <h3><?= $reserva['titulo'] ?></h3>
                        <span class="proveedor">Proveedor: <?= $reserva['nombre_empresa'] ?></span>
                        <span class="categoria"><?= $reserva['categoria'] ?></span>
                    </div>
                    <div class="reserva-status">
                        <span class="estado-badge estado-<?= $reserva['estado'] ?>">
                            <?= ucfirst($reserva['estado']) ?>
                        </span>
                        <span class="reserva-id">#<?= str_pad($reserva['id'], 6, '0', STR_PAD_LEFT) ?></span>
                    </div>
                </div>

                <div class="reserva-details">
                    <div class="reserva-imagen">
                        <img src="<?= json_decode($reserva['imagenes'])[0] ?>" alt="<?= $reserva['titulo'] ?>">
                    </div>
                    <div class="reserva-info-detallada">
                        <div class="detail-row">
                            <span class="label">Fecha de inicio:</span>
                            <span class="value"><?= date('d/m/Y H:i', strtotime($reserva['fecha_inicio'])) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Fecha de fin:</span>
                            <span class="value"><?= date('d/m/Y H:i', strtotime($reserva['fecha_fin'])) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Monto total:</span>
                            <span class="value precio">$<?= number_format($reserva['monto_total'], 2) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Fecha de reserva:</span>
                            <span class="value"><?= date('d/m/Y H:i', strtotime($reserva['created_at'])) ?></span>
                        </div>
                    </div>
                </div>

                <div class="reserva-actions">
                    <?php if($reserva['estado'] == 'pendiente'): ?>
                    <button class="btn btn-success" onclick="confirmarReserva(<?= $reserva['id'] ?>)">
                        ✅ Confirmar
                    </button>
                    <button class="btn btn-danger" onclick="cancelarReserva(<?= $reserva['id'] ?>)">
                        ❌ Cancelar
                    </button>
                    <?php endif; ?>

                    <?php if($reserva['estado'] == 'confirmada'): ?>
                    <button class="btn btn-primary"
                        onclick="contactarProveedor('<?= $reserva['telefono'] ?>', '<?= $reserva['email'] ?>')">
                        📞 Contactar Proveedor
                    </button>
                    <?php endif; ?>

                    <button class="btn btn-outline" onclick="verDetallesReserva(<?= $reserva['id'] ?>)">
                        👁️ Ver Detalles
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmarReserva(reservaId) {
    if (confirm('¿Estás seguro de que quieres confirmar esta reserva?')) {
        fetch('../../includes/update_reservation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    reserva_id: reservaId,
                    action: 'confirmar'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
    }
}

function cancelarReserva(reservaId) {
    const motivo = prompt('Por favor, indica el motivo de la cancelación:');
    if (motivo) {
        fetch('../../includes/update_reservation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    reserva_id: reservaId,
                    action: 'cancelar',
                    motivo: motivo
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
    }
}

function contactarProveedor(telefono, email) {
    const opcion = confirm(
        `¿Cómo quieres contactar al proveedor?\n\nOK para llamar al ${telefono}\nCancelar para enviar email`);

    if (opcion) {
        window.location.href = `tel:${telefono}`;
    } else {
        window.location.href = `mailto:${email}`;
    }
}

function verDetallesReserva(reservaId) {
    window.location.href = `detalle-reserva.php?id=${reservaId}`;
}

// Filtros
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const filter = this.dataset.filter;

        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        document.querySelectorAll('.reserva-card').forEach(card => {
            if (filter === 'todas' || card.dataset.estado === filter) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>