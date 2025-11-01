/**
 * ============================================
 * SERVICIO DETALLE - JavaScript
 * Archivo: assets/js/servicio-detalle.js
 * Funcionalidad principal para la página de detalle del servicio
 * ============================================
 */

// Obtener datos del servicio pasados desde PHP
const { precio, tipoPrecio, servicioId, csrfToken, isLoggedIn } = window.servicioData;

// ============================================
// GALERÍA DE IMÁGENES
// ============================================

/**
 * Cambiar la imagen principal de la galería
 * @param {string} src - URL de la imagen
 * @param {HTMLElement} elemento - Elemento thumbnail clickeado
 */
function cambiarImagen(src, elemento) {
    const mainImage = document.getElementById('mainImage');
    if (!mainImage) return;
    
    // Cambiar la imagen con fade effect
    mainImage.style.opacity = '0.5';
    
    setTimeout(() => {
        mainImage.src = src;
        mainImage.style.opacity = '1';
    }, 150);
    
    // Actualizar estado activo de los thumbnails
    document.querySelectorAll('.thumbnail').forEach(thumb => {
        thumb.classList.remove('active');
        thumb.setAttribute('aria-pressed', 'false');
    });
    
    elemento.classList.add('active');
    elemento.setAttribute('aria-pressed', 'true');
}

// ============================================
// SISTEMA DE FAVORITOS
// ============================================

/**
 * Toggle favorito - Agregar o quitar servicio de favoritos
 * @param {number} serviceId - ID del servicio
 */
async function toggleFavorite(serviceId) {
    // Verificar si el usuario está logueado
    if (!isLoggedIn) {
        window.location.href = '../../pages/login.php?redirect=' + encodeURIComponent(window.location.href);
        return;
    }

    const btn = document.querySelector('.btn-favorite');
    if (!btn) return;
    
    const originalContent = btn.innerHTML;
    const wasActive = btn.classList.contains('active');
    
    // Estado de carga
    btn.disabled = true;
    btn.innerHTML = '<span aria-hidden="true">⏳</span> Procesando...';

    try {
        const response = await fetch('../../includes/favoritos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                servicio_id: serviceId,
                action: 'toggle',
                csrf_token: csrfToken
            })
        });

        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }

        const data = await response.json();

        if (data.success) {
            if (data.favorito) {
                btn.innerHTML = '<span aria-hidden="true">♥</span> Favorito';
                btn.classList.add('active');
                btn.setAttribute('aria-label', 'Quitar de favoritos');
                showNotification('Agregado a favoritos', 'success');
            } else {
                btn.innerHTML = '<span aria-hidden="true">♡</span> Favorito';
                btn.classList.remove('active');
                btn.setAttribute('aria-label', 'Agregar a favoritos');
                showNotification('Removido de favoritos', 'info');
            }
        } else {
            throw new Error(data.message || 'Error al procesar la solicitud');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al procesar la solicitud: ' + error.message, 'error');
        
        // Restaurar estado original en caso de error
        btn.innerHTML = originalContent;
        if (wasActive) {
            btn.classList.add('active');
        }
    } finally {
        btn.disabled = false;
    }
}

// ============================================
// CÁLCULO DE PRECIOS
// ============================================

/**
 * Calcular el total de la reserva basado en fechas y personas
 */
function calcularTotal() {
    const fechaInicioInput = document.getElementById('fechaInicio');
    const fechaFinInput = document.getElementById('fechaFin');
    const personasInput = document.getElementById('personas');
    
    if (!fechaInicioInput.value || !fechaFinInput.value) {
        return;
    }
    
    const inicio = new Date(fechaInicioInput.value);
    const fin = new Date(fechaFinInput.value);
    const personas = parseInt(personasInput.value) || 1;

    // Validar que la fecha de fin sea posterior a la de inicio
    if (fin <= inicio) {
        showNotification('La fecha de fin debe ser posterior a la fecha de inicio', 'warning');
        document.getElementById('subtotal').textContent = '$0.00';
        document.getElementById('comision').textContent = '$0.00';
        document.getElementById('totalReserva').textContent = '$0.00';
        return;
    }

    const diffTiempo = fin - inicio;
    let subtotal = 0;

    // Calcular subtotal según el tipo de precio
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

    const comision = subtotal * 0.10; // 10% de comisión
    const total = subtotal + comision;

    // Actualizar UI con animación
    animateNumberChange('subtotal', subtotal);
    animateNumberChange('comision', comision);
    animateNumberChange('totalReserva', total);
}

/**
 * Animar cambio de números en el resumen
 * @param {string} elementId - ID del elemento
 * @param {number} newValue - Nuevo valor
 */
function animateNumberChange(elementId, newValue) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    element.style.transform = 'scale(1.1)';
    element.style.color = '#3498db';
    
    setTimeout(() => {
        element.textContent = `$${newValue.toFixed(2)}`;
        element.style.transform = 'scale(1)';
        element.style.color = '';
    }, 150);
}

// ============================================
// VALIDACIÓN DEL FORMULARIO
// ============================================

/**
 * Validar el formulario de reserva antes de enviarlo
 * @returns {boolean} - true si es válido, false si no
 */
function validarFormulario() {
    const fechaInicio = new Date(document.getElementById('fechaInicio').value);
    const fechaFin = new Date(document.getElementById('fechaFin').value);
    const ahora = new Date();

    // Validar fecha de inicio no sea en el pasado
    if (fechaInicio < ahora) {
        showNotification('La fecha de inicio no puede ser en el pasado', 'error');
        document.getElementById('fechaInicio').focus();
        return false;
    }

    // Validar fecha de fin sea posterior a fecha de inicio
    if (fechaFin <= fechaInicio) {
        showNotification('La fecha de fin debe ser posterior a la fecha de inicio', 'error');
        document.getElementById('fechaFin').focus();
        return false;
    }

    // Validar número de personas
    const personas = parseInt(document.getElementById('personas').value);
    if (personas < 1 || personas > 100) {
        showNotification('El número de personas debe estar entre 1 y 100', 'error');
        document.getElementById('personas').focus();
        return false;
    }

    // Validar que el total sea mayor a 0
    const totalText = document.getElementById('totalReserva').textContent;
    const total = parseFloat(totalText.replace('$', ''));
    if (total <= 0 || isNaN(total)) {
        showNotification('Por favor, complete las fechas para calcular el total', 'error');
        return false;
    }

    return true;
}

// ============================================
// NAVEGACIÓN Y UX
// ============================================

/**
 * Scroll suave hacia el formulario de reserva
 */
function scrollToReservation() {
    const reservationSidebar = document.querySelector('.reservation-sidebar');
    if (reservationSidebar) {
        reservationSidebar.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
        
        // Focus en el primer input después del scroll
        setTimeout(() => {
            const firstInput = document.getElementById('fechaInicio');
            if (firstInput) {
                firstInput.focus();
            }
        }, 500);
    }
}

// ============================================
// SISTEMA DE NOTIFICACIONES
// ============================================

/**
 * Mostrar notificación al usuario
 * @param {string} message - Mensaje a mostrar
 * @param {string} type - Tipo de notificación (success, error, warning, info)
 */
function showNotification(message, type = 'info') {
    // Remover notificaciones existentes
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.setAttribute('role', 'alert');
    notification.setAttribute('aria-live', 'polite');
    
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    notification.innerHTML = `
        <span class="notification-icon" aria-hidden="true">${icons[type] || icons.info}</span>
        <span class="notification-message">${escapeHtml(message)}</span>
        <button class="notification-close" onclick="this.parentElement.remove()" aria-label="Cerrar notificación">×</button>
    `;

    document.body.appendChild(notification);

    // Animación de entrada
    setTimeout(() => notification.classList.add('show'), 10);

    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 300);
    }, 5000);
}

/**
 * Escapar HTML para prevenir XSS
 * @param {string} text - Texto a escapar
 * @returns {string} - Texto escapado
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

// ============================================
// CONTADOR DE CARACTERES
// ============================================

/**
 * Inicializar contador de caracteres para textarea
 */
function initCharCounter() {
    const notasTextarea = document.getElementById('notas');
    if (!notasTextarea) return;
    
    const maxLength = notasTextarea.getAttribute('maxlength') || 500;
    const counterElement = document.createElement('small');
    counterElement.className = 'char-counter';
    counterElement.setAttribute('aria-live', 'polite');
    
    // Insertar después del textarea
    const formText = notasTextarea.parentElement.querySelector('.form-text');
    if (formText) {
        formText.insertAdjacentElement('afterend', counterElement);
    } else {
        notasTextarea.parentElement.appendChild(counterElement);
    }
    
    // Actualizar contador
    const updateCounter = () => {
        const remaining = maxLength - notasTextarea.value.length;
        counterElement.textContent = `${remaining} caracteres restantes`;
        
        if (remaining < 50) {
            counterElement.style.color = '#e74c3c';
        } else if (remaining < 100) {
            counterElement.style.color = '#f39c12';
        } else {
            counterElement.style.color = '#95a5a6';
        }
    };
    
    notasTextarea.addEventListener('input', updateCounter);
    updateCounter(); // Inicializar
}

// ============================================
// MANEJO DEL FORMULARIO DE RESERVA
// ============================================

/**
 * Enviar formulario de reserva
 * @param {Event} e - Evento del formulario
 */
async function handleReservationSubmit(e) {
    e.preventDefault();
    
    if (!validarFormulario()) {
        return;
    }

    const submitBtn = this.querySelector('button[type="submit"]');
    if (!submitBtn) return;
    
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span aria-hidden="true">⏳</span> Procesando reserva...';

    const formData = {
        servicio_id: servicioId,
        fecha_inicio: document.getElementById('fechaInicio').value,
        fecha_fin: document.getElementById('fechaFin').value,
        personas: document.getElementById('personas').value,
        notas: document.getElementById('notas').value.trim(),
        total: parseFloat(document.getElementById('totalReserva').textContent.replace('$', '')),
        csrf_token: csrfToken
    };

    try {
        const response = await fetch('../../includes/create_reservation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }

        const data = await response.json();

        if (data.success) {
            showNotification('¡Reserva creada exitosamente! Redirigiendo...', 'success');
            
            // Deshabilitar el formulario para evitar envíos múltiples
            const form = document.getElementById('reservationForm');
            if (form) {
                const inputs = form.querySelectorAll('input, textarea, button');
                inputs.forEach(input => input.disabled = true);
            }
            
            setTimeout(() => {
                window.location.href = '../reservas/mis-reservas.php';
            }, 1500);
        } else {
            throw new Error(data.message || 'Error al crear la reserva');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al crear la reserva: ' + error.message, 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// ============================================
// INICIALIZACIÓN
// ============================================

/**
 * Inicializar todos los componentes cuando el DOM esté listo
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando servicio detalle...');
    
    // Configurar fechas mínimas
    const ahora = new Date();
    const manana = new Date(ahora);
    manana.setDate(ahora.getDate() + 1);

    const fechaInicioInput = document.getElementById('fechaInicio');
    const fechaFinInput = document.getElementById('fechaFin');
    
    if (fechaInicioInput) {
        fechaInicioInput.min = ahora.toISOString().slice(0, 16);
        
        // Actualizar fecha mínima de fin cuando cambia la de inicio
        fechaInicioInput.addEventListener('change', function() {
            calcularTotal();
            if (fechaFinInput && this.value) {
                const nuevaFechaMin = new Date(this.value);
                nuevaFechaMin.setHours(nuevaFechaMin.getHours() + 1);
                fechaFinInput.min = nuevaFechaMin.toISOString().slice(0, 16);
                
                // Si la fecha de fin es menor, limpiarla
                if (fechaFinInput.value && new Date(fechaFinInput.value) <= new Date(this.value)) {
                    fechaFinInput.value = '';
                    showNotification('Por favor, seleccione una nueva fecha de fin', 'info');
                }
            }
        });
    }
    
    if (fechaFinInput) {
        fechaFinInput.min = manana.toISOString().slice(0, 16);
        fechaFinInput.addEventListener('change', calcularTotal);
    }
    
    // Event listener para el número de personas
    const personasInput = document.getElementById('personas');
    if (personasInput) {
        personasInput.addEventListener('input', calcularTotal);
        
        // Validar mientras escribe
        personasInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            if (value < 1) {
                this.value = 1;
            } else if (value > 100) {
                this.value = 100;
                showNotification('El máximo permitido es 100 personas', 'warning');
            }
        });
    }
    
    // Inicializar contador de caracteres
    initCharCounter();
    
    // Manejar envío del formulario
    const reservationForm = document.getElementById('reservationForm');
    if (reservationForm) {
        reservationForm.addEventListener('submit', handleReservationSubmit);
    }
    
    // Mejorar accesibilidad de keyboard en thumbnails
    const thumbnails = document.querySelectorAll('.thumbnail');
    thumbnails.forEach((thumb, index) => {
        thumb.setAttribute('tabindex', '0');
        
        // Navegación con teclado (flechas)
        thumb.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowRight') {
                e.preventDefault();
                const nextThumb = thumbnails[index + 1];
                if (nextThumb) nextThumb.focus();
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                const prevThumb = thumbnails[index - 1];
                if (prevThumb) prevThumb.focus();
            }
        });
    });
    
    console.log('Servicio detalle inicializado correctamente');
});

// ============================================
// MANEJO DE ERRORES GLOBALES
// ============================================

// Capturar errores no manejados
window.addEventListener('error', function(e) {
    console.error('Error global capturado:', e.error);
});

// Capturar promesas rechazadas no manejadas
window.addEventListener('unhandledrejection', function(e) {
    console.error('Promise rechazada no manejada:', e.reason);
});