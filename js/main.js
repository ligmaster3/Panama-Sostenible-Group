// Búsqueda de servicios
function buscarServicios() {
    const termino = document.getElementById('searchInput').value;
    const categoria = document.getElementById('categoriaSelect').value;
    
    if(termino || categoria) {
        window.location.href = `pages/servicios/servicios.php?search=${termino}&categoria=${categoria}`;
    }
}

// Filtros avanzados
function aplicarFiltros() {
    const precio = document.getElementById('filterPrecio').value;
    const ubicacion = document.getElementById('filterUbicacion').value;
    const sostenible = document.getElementById('filterSostenible').checked;
    
    // Implementar filtros AJAX
    const params = new URLSearchParams({
        precio: precio,
        ubicacion: ubicacion,
        sostenible: sostenible
    });
    
    window.location.href = `pages/servicios/servicios.php?${params.toString()}`;
}

// Sistema de favoritos
function toggleFavorito(servicioId) {
    fetch('includes/favoritos.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            servicio_id: servicioId,
            action: 'toggle'
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Actualizar interfaz
            const heart = document.querySelector(`[data-servicio="${servicioId}"]`);
            heart.classList.toggle('active');
        }
    });
}