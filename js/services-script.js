// services-script.js
function cambiarVista(vista) {
    const servicesGrid = document.getElementById('servicesGrid');
    const servicesMap = document.getElementById('servicesMap');
    const viewBtns = document.querySelectorAll('.view-btn');
    
    // Actualizar botones activos
    viewBtns.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.view === vista);
    });
    
    // Mostrar vista correspondiente
    if (vista === 'grid') {
        servicesGrid.style.display = 'grid';
        servicesMap.style.display = 'none';
    } else if (vista === 'list') {
        servicesGrid.style.display = 'block';
        servicesMap.style.display = 'none';
        // Aquí puedes añadir lógica para cambiar a vista de lista
        servicesGrid.classList.add('list-view');
    } else if (vista === 'map') {
        servicesGrid.style.display = 'none';
        servicesMap.style.display = 'block';
        inicializarMapa();
    }
}

function cambiarOrden(orden) {
    const url = new URL(window.location);
    url.searchParams.set('orden', orden);
    window.location.href = url.toString();
}

function limpiarFiltros() {
    window.location.href = window.location.pathname;
}

function removerFiltro(filtro) {
    const url = new URL(window.location);
    url.searchParams.delete(filtro);
    window.location.href = url.toString();
}

function toggleFavorite(serviceId, button) {
    if (!isLoggedIn()) {
        alert('Por favor inicia sesión para agregar a favoritos');
        return;
    }
    
    // Simular toggle de favorito
    const isFavorite = button.textContent === '♥';
    button.textContent = isFavorite ? '♡' : '♥';
    button.style.color = isFavorite ? 'inherit' : '#E74C3C';
    
    // Aquí iría la llamada AJAX para guardar en la base de datos
    console.log(`Servicio ${serviceId} ${isFavorite ? 'removido de' : 'agregado a'} favoritos`);
}

function quickReserve(serviceId) {
    const modal = document.getElementById('quickReserveModal');
    document.getElementById('quickReserveServiceId').value = serviceId;
    modal.style.display = 'flex';
    modal.classList.add('active');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'none';
    modal.classList.remove('active');
}

function inicializarMapa() {
    // Aquí iría la inicialización del mapa con Leaflet o Google Maps
    console.log('Inicializando mapa...');
}

// Cerrar modal al hacer clic fuera
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            closeModal(modal.id);
        }
    });
});

// Manejar envío del formulario de reserva rápida
document.getElementById('quickReserveForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    // Aquí iría la lógica para procesar la reserva
    alert('Reserva enviada correctamente');
    closeModal('quickReserveModal');
});

// Inicializar vista por defecto
document.addEventListener('DOMContentLoaded', function() {
    cambiarVista('grid');
});