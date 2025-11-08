<?php
$pageTitle = "Detalle del Servicio";

// Incluir archivos con rutas correctas
include '../../includes/header.php';
include '../../config/database.php';
include '../../includes/functions.php';
include '../../includes/auth.php';

$database = new Database();
$conn = $database->getConnection();

$servicio_id = $_GET['id'] ?? 0;

if (!$servicio_id) {
    header('Location: pages/servicios/servicios.php');
    exit;
}

$servicio = getServicioById($conn, $servicio_id);

if (!$servicio) {
    echo "<div class='container'><p class='error-message'>Servicio no encontrado</p></div>";
    include '../../includes/footer.php';
    exit;
}

// Obtener reseñas con paginación
$pagina_resenas = $_GET['pagina_resenas'] ?? 1;
$limite_resenas = 5;
$offset_resenas = ($pagina_resenas - 1) * $limite_resenas;

$stmt = $conn->prepare("
    SELECT r.*, u.nombre as usuario_nombre 
    FROM resenas r 
    JOIN usuarios u ON r.usuario_id = u.id 
    WHERE r.servicio_id = :servicio_id 
    ORDER BY r.created_at DESC 
    LIMIT :limit OFFSET :offset
");
$stmt->bindParam(':servicio_id', $servicio_id, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limite_resenas, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset_resenas, PDO::PARAM_INT);
$stmt->execute();
$resenas = $stmt->fetchAll();

// Contar total de reseñas para paginación
$stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM resenas WHERE servicio_id = :servicio_id");
$stmt_count->bindParam(':servicio_id', $servicio_id, PDO::PARAM_INT);
$stmt_count->execute();
$total_resenas_count = $stmt_count->fetch()['total'];
$total_paginas_resenas = ceil($total_resenas_count / $limite_resenas);

// Verificar favorito
$enFavoritos = false;
if (isLoggedIn()) {
    $stmt = $conn->prepare("
        SELECT id FROM favoritos 
        WHERE usuario_id = :usuario_id AND servicio_id = :servicio_id
    ");
    $stmt->bindParam(':usuario_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':servicio_id', $servicio_id, PDO::PARAM_INT);
    $stmt->execute();
    $enFavoritos = $stmt->fetch() !== false;
}

// Obtener servicios relacionados
$stmt_relacionados = $conn->prepare("
    SELECT s.id, s.titulo, s.precio, s.imagenes, s.rating_promedio, s.total_resenas
    FROM servicios s 
    JOIN proveedores p ON s.proveedor_id = p.id
    WHERE p.categoria_id = :categoria_id AND s.id != :servicio_id AND s.disponibilidad = 1
    ORDER BY s.rating_promedio DESC 
    LIMIT 4
");

$stmt_relacionados->bindParam(':categoria_id', $servicio['categoria_id'], PDO::PARAM_INT);
$stmt_relacionados->bindParam(':servicio_id', $servicio_id, PDO::PARAM_INT);
$stmt_relacionados->execute();
$servicios_relacionados = $stmt_relacionados->fetchAll();
?>

<div class="service-detail">
    <!-- Breadcrumb -->
    <div class="container">
        <nav class="breadcrumb" aria-label="Migas de pan">
            <a href="../../index.php">Inicio</a>
            <span aria-hidden="true">></span>
            <a href="../../servicios.php">Servicios</a>
            <?php if(isset($servicio['categoria_id'])): ?>
            <span aria-hidden="true">></span>
            <a href="../../servicios.php?categoria=<?= $servicio['categoria_id'] ?>">
                <?= htmlspecialchars($servicio['categoria']) ?>
            </a>
            <?php endif; ?>
            <span aria-hidden="true">></span>
            <span class="current" aria-current="page"><?= htmlspecialchars($servicio['titulo']) ?></span>
        </nav>
    </div>

    <div class="container">
        <div class="service-detail-layout">
            <!-- Contenido Principal -->
            <main class="service-main">
                <!-- Galería -->
                <section class="service-gallery" aria-label="Galería de imágenes del servicio">
                    <?php
                    $imagenes = json_decode($servicio['imagenes'] ?? '[]');
                    $imagenPrincipal = $imagenes[0] ?? '../../assets/images/placeholder-service.jpg';
                    ?>
                    <div class="main-image">
                        <img src="<?= $imagenPrincipal ?>" alt="<?= htmlspecialchars($servicio['titulo']) ?>"
                            id="mainImage" loading="lazy">
                    </div>
                    <?php if(count($imagenes) > 1): ?>
                    <div class="image-thumbnails">
                        <?php foreach($imagenes as $index => $imagen): ?>
                        <button class="thumbnail <?= $index === 0 ? 'active' : '' ?>"
                            onclick="cambiarImagen('<?= $imagen ?>', this)" aria-label="Ver imagen <?= $index + 1 ?>"
                            aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>">
                            <img src="<?= $imagen ?>" alt="Miniatura <?= $index + 1 ?>" loading="lazy">
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>

                <!-- Información del Servicio -->
                <section class="service-info-detailed">
                    <div class="service-header">
                        <h1><?= htmlspecialchars($servicio['titulo']) ?></h1>
                        <div class="service-actions">
                            <button class="btn-favorite <?= $enFavoritos ? 'active' : '' ?>"
                                onclick="toggleFavorite(<?= $servicio_id ?>)"
                                aria-label="<?= $enFavoritos ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>">
                                <span class="favorite-icon"><?= $enFavoritos ? '♥' : '♡' ?></span>
                                <span
                                    class="favorite-text"><?= $enFavoritos ? 'En Favoritos' : 'Agregar a Favoritos' ?></span>
                            </button>
                            <button class="btn btn-primary" onclick="scrollToReservation()">
                                <span class="icon">📅</span>
                                <span>Reservar</span>
                            </button>
                        </div>
                    </div>

                    <div class="service-meta-detailed">
                        <div class="provider-info">
                            <strong>Proveedor:</strong>
                            <span><?= htmlspecialchars($servicio['nombre_empresa']) ?></span>
                            <?php if($servicio['sostenible']): ?>
                            <span class="badge sustainable" title="Servicio sostenible">♻️ Sostenible</span>
                            <?php endif; ?>
                        </div>
                        <div class="location-info">
                            <span class="icon">📍</span>
                            <span><?= htmlspecialchars($servicio['ubicacion']) ?></span>
                        </div>
                        <div class="rating-info">
                            <span class="icon">⭐</span>
                            <span class="rating-value">
                                <?= $servicio['rating_promedio'] ? number_format($servicio['rating_promedio'], 1) : 'Nuevo' ?>
                            </span>
                            <?php if($servicio['total_resenas'] > 0): ?>
                            <span class="review-count">(<?= $servicio['total_resenas'] ?> reseñas)</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="service-description-detailed">
                        <h2>Descripción</h2>
                        <p><?= nl2br(htmlspecialchars($servicio['descripcion'])) ?></p>
                    </div>

                    <!-- Características -->
                    <?php
                    $caracteristicas = json_decode($servicio['caracteristicas'] ?? '[]', true);
                    if ($caracteristicas && count($caracteristicas) > 0):
                    ?>
                    <div class="service-features">
                        <h2>Características</h2>
                        <div class="features-list">
                            <?php foreach($caracteristicas as $caracteristica): 
                                // Convertir array/objeto a string si es necesario
                                if (is_array($caracteristica)) {
                                    $caracteristica = implode(', ', array_filter($caracteristica, function($item) {
                                        return is_string($item) || is_numeric($item);
                                    }));
                                } elseif (!is_string($caracteristica) && !is_numeric($caracteristica)) {
                                    $caracteristica = (string)$caracteristica;
                                }
                            ?>
                            <div class="feature-item">
                                <span class="feature-icon">✅</span>
                                <span class="feature-text"><?= htmlspecialchars($caracteristica) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Reseñas -->
                    <div class="service-reviews">
                        <div class="reviews-header">
                            <h2>Reseñas</h2>
                            <?php if($total_resenas_count > 0): ?>
                            <div class="reviews-stats">
                                <span class="average-rating">
                                    ⭐ <?= number_format($servicio['rating_promedio'], 1) ?>
                                </span>
                                <span class="total-reviews">Basado en <?= $total_resenas_count ?> reseñas</span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if(empty($resenas)): ?>
                        <div class="no-reviews">
                            <p>Este servicio aún no tiene reseñas.</p>
                            <?php if(isLoggedIn()): ?>
                            <button class="btn btn-outline" onclick="scrollToReviewForm()">Escribir primera
                                reseña</button>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="reviews-list">
                            <?php foreach($resenas as $resena): ?>
                            <article class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <strong
                                            class="reviewer-name"><?= htmlspecialchars($resena['usuario_nombre']) ?></strong>
                                        <div class="review-rating"
                                            aria-label="Calificación: <?= $resena['rating'] ?> de 5 estrellas">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?= $i <= $resena['rating'] ? 'filled' : 'empty' ?>">
                                                <?= $i <= $resena['rating'] ? '⭐' : '☆' ?>
                                            </span>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <time class="review-date" datetime="<?= $resena['created_at'] ?>">
                                        <?= date('d/m/Y', strtotime($resena['created_at'])) ?>
                                    </time>
                                </div>
                                <p class="review-comment"><?= nl2br(htmlspecialchars($resena['comentario'])) ?></p>
                            </article>
                            <?php endforeach; ?>
                        </div>

                        <!-- Paginación de reseñas -->
                        <?php if($total_paginas_resenas > 1): ?>
                        <div class="reviews-pagination">
                            <?php if($pagina_resenas > 1): ?>
                            <a href="?id=<?= $servicio_id ?>&pagina_resenas=<?= $pagina_resenas - 1 ?>#reviews"
                                class="pagination-btn">← Anterior</a>
                            <?php endif; ?>

                            <span class="pagination-info">
                                Página <?= $pagina_resenas ?> de <?= $total_paginas_resenas ?>
                            </span>

                            <?php if($pagina_resenas < $total_paginas_resenas): ?>
                            <a href="?id=<?= $servicio_id ?>&pagina_resenas=<?= $pagina_resenas + 1 ?>#reviews"
                                class="pagination-btn">Siguiente →</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Servicios Relacionados -->
                    <?php if(!empty($servicios_relacionados)): ?>
                    <section class="related-services">
                        <h2>Servicios Relacionados</h2>
                        <div class="related-services-grid">
                            <?php foreach($servicios_relacionados as $relacionado): 
                                $imagenes_rel = json_decode($relacionado['imagenes'] ?? '[]');
                                $imagen_rel = $imagenes_rel[0] ?? '../../assets/images/placeholder-service.jpg';
                            ?>
                            <a href="servicio-detalle.php?id=<?= $relacionado['id'] ?>" class="related-service-card">
                                <img src="<?= $imagen_rel ?>" alt="<?= htmlspecialchars($relacionado['titulo']) ?>"
                                    loading="lazy">
                                <div class="related-service-info">
                                    <h4><?= htmlspecialchars($relacionado['titulo']) ?></h4>
                                    <div class="related-service-meta">
                                        <span class="price">$<?= number_format($relacionado['precio'], 2) ?></span>
                                        <?php if($relacionado['rating_promedio']): ?>
                                        <span class="rating">
                                            ⭐ <?= number_format($relacionado['rating_promedio'], 1) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                </section>
            </main>

            <!-- Sidebar de Reserva -->
            <aside class="reservation-sidebar">
                <div class="reservation-widget">
                    <div class="price-widget">
                        <span class="price">$<?= number_format($servicio['precio'], 2) ?></span>
                        <span class="unit">/<?= $servicio['tipo_precio'] ?></span>
                    </div>

                    <form id="reservationForm" class="reservation-form" novalidate>
                        <input type="hidden" name="servicio_id" value="<?= $servicio_id ?>">

                        <div class="form-group">
                            <label for="fechaInicio">Fecha de inicio</label>
                            <input type="datetime-local" name="fecha_inicio" id="fechaInicio" required
                                class="form-control" aria-describedby="fechaInicioHelp">
                            <small id="fechaInicioHelp" class="form-help">Selecciona la fecha y hora de inicio</small>
                        </div>

                        <div class="form-group">
                            <label for="fechaFin">Fecha de fin</label>
                            <input type="datetime-local" name="fecha_fin" id="fechaFin" required class="form-control"
                                aria-describedby="fechaFinHelp">
                            <small id="fechaFinHelp" class="form-help">Selecciona la fecha y hora de
                                finalización</small>
                        </div>

                        <div class="form-group">
                            <label for="personas">Personas</label>
                            <input type="number" name="personas" id="personas" min="1" max="50" value="1"
                                class="form-control" aria-describedby="personasHelp">
                            <small id="personasHelp" class="form-help">Número de personas para el servicio</small>
                        </div>

                        <div class="form-group">
                            <label for="notas">Notas (opcional)</label>
                            <textarea name="notas" id="notas" class="form-control"
                                placeholder="Comentarios adicionales..." rows="3"
                                aria-describedby="notasHelp"></textarea>
                            <small id="notasHelp" class="form-help">Información adicional para el proveedor</small>
                        </div>

                        <div class="reservation-summary">
                            <div class="summary-item">
                                <span>Subtotal:</span>
                                <span id="subtotal">$0.00</span>
                            </div>
                            <div class="summary-item">
                                <span>Comisión (10%):</span>
                                <span id="comision">$0.00</span>
                            </div>
                            <div class="summary-total">
                                <span>Total:</span>
                                <span id="totalReserva">$0.00</span>
                            </div>
                        </div>

                        <?php if(isLoggedIn()): ?>
                        <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                            <span class="btn-text">Confirmar Reserva</span>
                            <span class="btn-loading" style="display: none;">Procesando...</span>
                        </button>
                        <?php else: ?>
                        <a href="../../pages/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                            class="btn btn-primary btn-block">
                            Iniciar Sesión para Reservar
                        </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Información del Proveedor -->
                <div class="provider-widget">
                    <h3>Proveedor</h3>
                    <div class="provider-card">
                        <h4><?= htmlspecialchars($servicio['nombre_empresa']) ?></h4>
                        <div class="provider-contact">
                            <p><strong>Email:</strong>
                                <a href="mailto:<?= htmlspecialchars($servicio['email']) ?>">
                                    <?= htmlspecialchars($servicio['email']) ?>
                                </a>
                            </p>
                            <p><strong>Teléfono:</strong>
                                <a href="tel:<?= htmlspecialchars($servicio['telefono']) ?>">
                                    <?= htmlspecialchars($servicio['telefono']) ?>
                                </a>
                            </p>
                            <p><strong>Ubicación:</strong> <?= htmlspecialchars($servicio['ubicacion']) ?></p>
                        </div>
                        <?php if(!empty($servicio['descripcion_proveedor'])): ?>
                        <div class="provider-description">
                            <p><?= nl2br(htmlspecialchars($servicio['descripcion_proveedor'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
// Variables globales
const precio = <?= $servicio['precio'] ?>;
const tipoPrecio = '<?= $servicio['tipo_precio'] ?>';
let isSubmitting = false;

// Cambiar imagen principal
function cambiarImagen(src, elemento) {
    const mainImage = document.getElementById('mainImage');
    mainImage.style.opacity = '0';

    setTimeout(() => {
        mainImage.src = src;
        mainImage.style.opacity = '1';

        // Actualizar estado activo de thumbnails
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.classList.remove('active');
            thumb.setAttribute('aria-pressed', 'false');
        });
        elemento.classList.add('active');
        elemento.setAttribute('aria-pressed', 'true');
    }, 150);
}

// Toggle favorito
async function toggleFavorite(serviceId) {
    <?php if(!isLoggedIn()): ?>
    window.location.href = '../../pages/login.php?redirect=' + encodeURIComponent(window.location.href);
    return;
    <?php endif; ?>

    const btn = document.querySelector('.btn-favorite');
    const icon = btn.querySelector('.favorite-icon');
    const text = btn.querySelector('.favorite-text');

    // Feedback visual inmediato
    btn.disabled = true;

    try {
        const response = await fetch('../../includes/favoritos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                servicio_id: serviceId,
                action: 'toggle',
                csrf_token: '<?= $_SESSION['csrf_token'] ?>'
            })
        });

        const data = await response.json();

        if (data.success) {
            if (data.favorito) {
                icon.textContent = '♥';
                text.textContent = 'En Favoritos';
                btn.classList.add('active');
                btn.setAttribute('aria-label', 'Quitar de favoritos');
                showNotification('Agregado a favoritos', 'success');
            } else {
                icon.textContent = '♡';
                text.textContent = 'Agregar a Favoritos';
                btn.classList.remove('active');
                btn.setAttribute('aria-label', 'Agregar a favoritos');
                showNotification('Removido de favoritos', 'info');
            }
        } else {
            showNotification('Error: ' + (data.message || 'No se pudo completar la acción'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
    }
}

// Calcular total de reserva
function calcularTotal() {
    const inicio = new Date(document.getElementById('fechaInicio').value);
    const fin = new Date(document.getElementById('fechaFin').value);
    const personas = parseInt(document.getElementById('personas').value) || 1;

    // Validar fechas
    if (!inicio || !fin || inicio >= fin) {
        resetReservationSummary();
        return;
    }

    let subtotal = 0;
    const diffTiempo = fin - inicio;

    switch (tipoPrecio) {
        case 'hora':
            const horas = Math.ceil(diffTiempo / (1000 * 60 * 60));
            subtotal = precio * horas * personas;
            break;
        case 'dia':
            const dias = Math.ceil(diffTiempo / (1000 * 60 * 60 * 24));
            subtotal = precio * dias * personas;
            break;
        case 'persona':
            subtotal = precio * personas;
            break;
        case 'fijo':
            subtotal = precio;
            break;
        default:
            subtotal = precio;
    }

    const comision = subtotal * 0.10;
    const total = subtotal + comision;

    updateReservationSummary(subtotal, comision, total);
}

function resetReservationSummary() {
    document.getElementById('subtotal').textContent = '$0.00';
    document.getElementById('comision').textContent = '$0.00';
    document.getElementById('totalReserva').textContent = '$0.00';
}

function updateReservationSummary(subtotal, comision, total) {
    document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('comision').textContent = `$${comision.toFixed(2)}`;
    document.getElementById('totalReserva').textContent = `$${total.toFixed(2)}`;
}

// Scroll a sección de reserva
function scrollToReservation() {
    document.querySelector('.reservation-sidebar').scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });
}

// Scroll a reseñas
function scrollToReviewForm() {
    document.getElementById('reviews').scrollIntoView({
        behavior: 'smooth'
    });
}

// Validar formulario
function validarFormulario() {
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    const personas = document.getElementById('personas').value;

    if (!fechaInicio || !fechaFin) {
        showNotification('Por favor completa las fechas de reserva', 'error');
        return false;
    }

    if (new Date(fechaInicio) >= new Date(fechaFin)) {
        showNotification('La fecha de fin debe ser posterior a la fecha de inicio', 'error');
        return false;
    }

    if (personas < 1 || personas > 50) {
        showNotification('El número de personas debe estar entre 1 y 50', 'error');
        return false;
    }

    return true;
}

// Envío del formulario
document.getElementById('reservationForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (isSubmitting) return;

    if (!validarFormulario()) return;

    isSubmitting = true;
    const submitBtn = document.getElementById('submitBtn');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');

    // Mostrar estado de carga
    btnText.style.display = 'none';
    btnLoading.style.display = 'inline';
    submitBtn.disabled = true;

    const formData = {
        servicio_id: <?= $servicio_id ?>,
        fecha_inicio: document.getElementById('fechaInicio').value,
        fecha_fin: document.getElementById('fechaFin').value,
        personas: document.getElementById('personas').value,
        notas: document.getElementById('notas').value,
        total: document.getElementById('totalReserva').textContent.replace('$', ''),
        csrf_token: '<?= $_SESSION['csrf_token'] ?>'
    };

    try {
        const response = await fetch('../../includes/create_reservation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            showNotification('¡Reserva creada exitosamente! Redirigiendo...', 'success');
            setTimeout(() => {
                window.location.href = '../reservas/mis-reservas.php';
            }, 2000);
        } else {
            showNotification('Error: ' + (data.message || 'No se pudo crear la reserva'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión al crear la reserva', 'error');
    } finally {
        // Restaurar estado del botón
        btnText.style.display = 'inline';
        btnLoading.style.display = 'none';
        submitBtn.disabled = false;
        isSubmitting = false;
    }
});

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    // Configurar fechas mínimas
    const ahora = new Date();
    const manana = new Date(ahora);
    manana.setDate(ahora.getDate() + 1);

    // Redondear a los próximos 30 minutos
    const minutos = ahora.getMinutes();
    const minutosRedondeados = Math.ceil(minutos / 30) * 30;
    ahora.setMinutes(minutosRedondeados, 0, 0);

    document.getElementById('fechaInicio').min = ahora.toISOString().slice(0, 16);
    document.getElementById('fechaFin').min = manana.toISOString().slice(0, 16);

    // Establecer valores por defecto
    document.getElementById('fechaInicio').value = ahora.toISOString().slice(0, 16);
    document.getElementById('fechaFin').value = manana.toISOString().slice(0, 16);

    // Calcular total inicial
    setTimeout(calcularTotal, 100);
});

// Event Listeners para cálculo en tiempo real
document.getElementById('fechaInicio').addEventListener('change', calcularTotal);
document.getElementById('fechaFin').addEventListener('change', calcularTotal);
document.getElementById('personas').addEventListener('input', calcularTotal);

// Sistema de notificaciones
function showNotification(message, type = 'info') {
    // Remover notificaciones existentes
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.setAttribute('role', 'alert');
    notification.innerHTML = `
        <span class="notification-message">${message}</span>
        <button class="notification-close" onclick="this.parentElement.remove()" aria-label="Cerrar notificación">×</button>
    `;

    // Estilos para la notificación
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        color: white;
        z-index: 10000;
        max-width: 400px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
        display: flex;
        align-items: center;
        gap: 1rem;
    `;

    // Colores según tipo
    const colors = {
        success: '#27ae60',
        error: '#e74c3c',
        info: '#3498db',
        warning: '#f39c12'
    };

    notification.style.background = colors[type] || colors.info;

    document.body.appendChild(notification);

    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Agregar estilos CSS para animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    .notification-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
`;
document.head.appendChild(style);
</script>

<style>
/* Estilos mejorados para servicio-detalle.php */
.service-detail-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 3rem;
    margin: 2rem 0;
    align-items: start;
}

.service-gallery {
    margin-bottom: 2rem;
}

.main-image {
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1rem;
    background: #f8f9fa;
    transition: opacity 0.3s ease;
}

.main-image img {
    width: 100%;
    height: 400px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.main-image:hover img {
    transform: scale(1.02);
}

.image-thumbnails {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    padding: 0.5rem 0;
}

.thumbnail {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    opacity: 0.7;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    background: none;
    padding: 0;
}

.thumbnail.active,
.thumbnail:hover {
    opacity: 1;
    border-color: #3498db;
    transform: translateY(-2px);
}

.thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.service-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    gap: 1rem;
}

.service-header h1 {
    margin: 0;
    color: #2c3e50;
    flex: 1;
    font-size: 2rem;
    line-height: 1.2;
}

.service-actions {
    display: flex;
    gap: 1rem;
    flex-shrink: 0;
}

.btn-favorite {
    background: #ecf0f1;
    border: 2px solid #bdc3c7;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.btn-favorite:hover {
    background: #e0e0e0;
    transform: translateY(-1px);
}

.btn-favorite.active {
    background: #ff6b6b;
    border-color: #ff6b6b;
    color: white;
}

.btn-favorite:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.service-meta-detailed {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border-left: 4px solid #3498db;
}

.service-meta-detailed>div {
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.service-description-detailed,
.service-features,
.service-reviews {
    margin-bottom: 3rem;
}

.service-description-detailed h2,
.service-features h2,
.service-reviews h2 {
    color: #2c3e50;
    margin-bottom: 1rem;
    font-size: 1.5rem;
}

.features-list {
    display: grid;
    gap: 0.75rem;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
}

.feature-icon {
    flex-shrink: 0;
}

.reviews-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.reviews-stats {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.average-rating {
    font-size: 1.2rem;
    font-weight: bold;
    color: #f39c12;
}

.total-reviews {
    color: #7f8c8d;
}

.review-item {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border-left: 3px solid #3498db;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
    gap: 1rem;
}

.reviewer-info {
    flex: 1;
}

.reviewer-name {
    display: block;
    margin-bottom: 0.25rem;
}

.review-rating {
    display: flex;
    gap: 0.1rem;
}

.star {
    font-size: 0.9rem;
}

.review-date {
    color: #7f8c8d;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.reviews-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
    padding: 1rem;
}

.pagination-btn {
    padding: 0.5rem 1rem;
    border: 1px solid #3498db;
    border-radius: 5px;
    color: #3498db;
    text-decoration: none;
    transition: all 0.3s ease;
}

.pagination-btn:hover {
    background: #3498db;
    color: white;
}

.related-services {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid #ecf0f1;
}

.related-services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.related-service-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
}

.related-service-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
}

.related-service-card img {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.related-service-info {
    padding: 1rem;
}

.related-service-info h4 {
    margin: 0 0 0.5rem 0;
    color: #2c3e50;
    font-size: 1rem;
}

.related-service-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.related-service-meta .price {
    font-weight: bold;
    color: #27ae60;
}

.related-service-meta .rating {
    color: #f39c12;
    font-size: 0.9rem;
}

.reservation-sidebar {
    position: sticky;
    top: 100px;
}

.reservation-widget {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.price-widget {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #ecf0f1;
}

.price-widget .price {
    font-size: 2.5rem;
    font-weight: bold;
    color: #27ae60;
    display: block;
}

.price-widget .unit {
    color: #7f8c8d;
    font-size: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #2c3e50;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #ecf0f1;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.form-help {
    display: block;
    margin-top: 0.25rem;
    color: #7f8c8d;
    font-size: 0.85rem;
}

.reservation-summary {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin: 1.5rem 0;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    color: #2c3e50;
}

.summary-total {
    display: flex;
    justify-content: space-between;
    font-weight: bold;
    font-size: 1.2rem;
    border-top: 2px solid #ddd;
    padding-top: 0.75rem;
    margin-top: 0.75rem;
    color: #2c3e50;
}

.provider-widget {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.provider-widget h3 {
    margin-bottom: 1rem;
    color: #2c3e50;
}

.provider-card h4 {
    margin-bottom: 1rem;
    color: #2c3e50;
}

.provider-contact p {
    margin-bottom: 0.5rem;
}

.provider-contact a {
    color: #3498db;
    text-decoration: none;
}

.provider-contact a:hover {
    text-decoration: underline;
}

.provider-description {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #ecf0f1;
}

.badge.sustainable {
    background: #27ae60;
    color: white;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.no-reviews {
    text-align: center;
    padding: 3rem 2rem;
    color: #7f8c8d;
}

.btn-loading {
    display: none;
}

@media (max-width: 968px) {
    .service-detail-layout {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .service-header {
        flex-direction: column;
        align-items: stretch;
    }

    .service-actions {
        justify-content: center;
    }

    .reviews-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .related-services-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
}

@media (max-width: 768px) {
    .service-header h1 {
        font-size: 1.5rem;
    }

    .service-actions {
        flex-direction: column;
    }

    .reservation-widget {
        padding: 1.5rem;
    }
}
</style>