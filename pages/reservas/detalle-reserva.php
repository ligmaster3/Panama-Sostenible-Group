<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$pageTitle = "Detalle de Reserva";
include '../../includes/header.php';
include '../../includes/database.php';

$database = new Database();
$conn = $database->getConnection();

$reserva_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Obtener detalles de la reserva
$stmt = $conn->prepare("
    SELECT r.*, s.titulo, s.descripcion, s.imagenes, s.precio, s.tipo_precio,
           p.nombre_empresa, p.telefono, p.email, p.ubicacion, p.descripcion as descripcion_proveedor,
           c.nombre as categoria, u.nombre as usuario_nombre, u.email as usuario_email
    FROM reservas r
    JOIN servicios s ON r.servicio_id = s.id
    JOIN proveedores p ON s.proveedor_id = p.id
    JOIN categorias c ON p.categoria_id = c.id
    JOIN usuarios u ON r.usuario_id = u.id
    WHERE r.id = :reserva_id AND r.usuario_id = :user_id
");
$stmt->bindParam(':reserva_id', $reserva_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$reserva) {
    echo "<div class='container'><p>Reserva no encontrada</p></div>";
    include '../../includes/footer.php';
    exit;
}
?>

<div class="page-header">
    <div class="container">
        <h1>Detalle de Reserva</h1>
        <p>Información completa de tu reserva</p>
    </div>
</div>

<div class="container">
    <div class="reserva-detalle">
        <!-- Encabezado -->
        <div class="reserva-header-detalle">
            <div class="reserva-titulo">
                <h2><?= $reserva['titulo'] ?></h2>
                <span class="reserva-id">Reserva #<?= str_pad($reserva['id'], 6, '0', STR_PAD_LEFT) ?></span>
            </div>
            <div class="reserva-estado">
                <span class="estado-badge estado-<?= $reserva['estado'] ?>">
                    <?= ucfirst($reserva['estado']) ?>
                </span>
            </div>
        </div>

        <div class="reserva-content">
            <!-- Información del servicio -->
            <div class="reserva-section">
                <h3>📋 Información del Servicio</h3>
                <div class="service-info-detalle">
                    <div class="service-image">
                        <img src="<?= json_decode($reserva['imagenes'])[0] ?>" alt="<?= $reserva['titulo'] ?>">
                    </div>
                    <div class="service-details">
                        <h4><?= $reserva['titulo'] ?></h4>
                        <p class="descripcion"><?= $reserva['descripcion'] ?></p>
                        <div class="service-meta">
                            <span class="precio">Precio: $<?= $reserva['precio'] ?> /
                                <?= $reserva['tipo_precio'] ?></span>
                            <span class="categoria">Categoría: <?= $reserva['categoria'] ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del proveedor -->
            <div class="reserva-section">
                <h3>👥 Información del Proveedor</h3>
                <div class="proveedor-info">
                    <h4><?= $reserva['nombre_empresa'] ?></h4>
                    <p><?= $reserva['descripcion_proveedor'] ?></p>
                    <div class="contacto-proveedor">
                        <p><strong>📧 Email:</strong> <?= $reserva['email'] ?></p>
                        <p><strong>📞 Teléfono:</strong> <?= $reserva['telefono'] ?></p>
                        <p><strong>📍 Ubicación:</strong> <?= $reserva['ubicacion'] ?></p>
                    </div>
                </div>
            </div>

            <!-- Detalles de la reserva -->
            <div class="reserva-section">
                <h3>📅 Detalles de la Reserva</h3>
                <div class="detalles-grid">
                    <div class="detalle-item">
                        <strong>Fecha de inicio:</strong>
                        <span><?= date('d/m/Y H:i', strtotime($reserva['fecha_inicio'])) ?></span>
                    </div>
                    <div class="detalle-item">
                        <strong>Fecha de fin:</strong>
                        <span><?= date('d/m/Y H:i', strtotime($reserva['fecha_fin'])) ?></span>
                    </div>
                    <div class="detalle-item">
                        <strong>Monto total:</strong>
                        <span class="precio-total">$<?= number_format($reserva['total'], 2) ?></span>
                    </div>
                    <div class="detalle-item">
                        <strong>Fecha de reserva:</strong>
                        <span><?= date('d/m/Y H:i', strtotime($reserva['created_at'])) ?></span>
                    </div>
                    <div class="detalle-item">
                        <strong>Estado:</strong>
                        <span class="estado-texto estado-<?= $reserva['estado'] ?>">
                            <?= ucfirst($reserva['estado']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Información del cliente -->
            <div class="reserva-section">
                <h3>👤 Información del Cliente</h3>
                <div class="cliente-info">
                    <p><strong>Nombre:</strong> <?= $reserva['usuario_nombre'] ?></p>
                    <p><strong>Email:</strong> <?= $reserva['usuario_email'] ?></p>
                </div>
            </div>

            <?php if(!empty($reserva['notas'])): ?>
            <div class="reserva-section">
                <h3>📝 Notas Adicionales</h3>
                <div class="notas-reserva">
                    <p><?= $reserva['notas'] ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Acciones -->
        <div class="reserva-actions-detalle">
            <?php if($reserva['estado'] == 'pendiente'): ?>
            <button class="btn btn-success" onclick="confirmarReserva(<?= $reserva['id'] ?>)">
                ✅ Confirmar Reserva
            </button>
            <button class="btn btn-danger" onclick="cancelarReserva(<?= $reserva['id'] ?>)">
                ❌ Cancelar Reserva
            </button>
            <?php endif; ?>

            <button class="btn btn-primary"
                onclick="contactarProveedor('<?= $reserva['telefono'] ?>', '<?= $reserva['email'] ?>')">
                📞 Contactar Proveedor
            </button>

            <a href="mis-reservas.php" class="btn btn-outline">← Volver a Mis Reservas</a>

            <button class="btn btn-secondary" onclick="imprimirReserva()">
                🖨️ Imprimir Comprobante
            </button>
        </div>
    </div>
</div>

<script>
function imprimirReserva() {
    window.print();
}

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
</script>

<style>
@media print {

    .navbar,
    .footer,
    .reserva-actions-detalle {
        display: none !important;
    }

    .reserva-detalle {
        box-shadow: none !important;
        border: 1px solid #ccc !important;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>