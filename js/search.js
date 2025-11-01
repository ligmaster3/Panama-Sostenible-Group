// Búsqueda avanzada de servicios - Versión Mejorada
class SearchManager {
    constructor() {
        this.filters = {
            categoria: '',
            precioMin: '',
            precioMax: '',
            ubicacion: '',
            sostenible: false,
            rating: 0
        };
        this.isSearching = false;
        this.lastSearchTerm = '';
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadFiltersFromURL();
        this.setupIntersectionObserver();
    }

    bindEvents() {
        // Búsqueda en tiempo real con mejor manejo
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(this.realTimeSearch.bind(this), 500));
            searchInput.addEventListener('keypress', this.handleEnterKey.bind(this));
        }

        // Filtros con validación
        document.querySelectorAll('.filter-input').forEach(input => {
            input.addEventListener('change', this.applyFilters.bind(this));
            // Validación en tiempo real para campos numéricos
            if (input.type === 'number') {
                input.addEventListener('blur', this.validateNumberInput.bind(this));
            }
        });

        // Botón buscar con estado de carga
        const searchBtn = document.getElementById('searchBtn');
        if (searchBtn) {
            searchBtn.addEventListener('click', this.searchServices.bind(this));
        }

        // Limpiar filtros
        const clearBtn = document.getElementById('clearFilters');
        if (clearBtn) {
            clearBtn.addEventListener('click', this.clearFilters.bind(this));
        }

        // Manejar cambios de URL para búsqueda
        window.addEventListener('popstate', this.loadFiltersFromURL.bind(this));
    }

    debounce(func, wait, immediate = false) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func(...args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func(...args);
        };
    }

    handleEnterKey(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            this.searchServices();
        }
    }

    validateNumberInput(event) {
        const input = event.target;
        const value = parseFloat(input.value);
        
        if (input.id === 'precioMin' && this.filters.precioMax) {
            if (value > parseFloat(this.filters.precioMax)) {
                input.value = this.filters.precioMax;
                this.showNotification('El precio mínimo no puede ser mayor al máximo', 'warning');
            }
        }
        
        if (input.id === 'precioMax' && this.filters.precioMin) {
            if (value < parseFloat(this.filters.precioMin)) {
                input.value = this.filters.precioMin;
                this.showNotification('El precio máximo no puede ser menor al mínimo', 'warning');
            }
        }
    }

    realTimeSearch(event) {
        const term = event.target.value.trim();
        
        // Evitar búsquedas duplicadas
        if (term === this.lastSearchTerm && term.length < 3) return;
        
        this.lastSearchTerm = term;
        
        if (term.length >= 3 || term.length === 0) {
            this.searchServices();
        }
    }

    applyFilters() {
        this.updateFilters();
        this.updateURL();
        this.searchServices();
    }

    updateFilters() {
        this.filters = {
            categoria: document.getElementById('categoriaFilter')?.value || '',
            precioMin: document.getElementById('precioMin')?.value || '',
            precioMax: document.getElementById('precioMax')?.value || '',
            ubicacion: document.getElementById('ubicacionFilter')?.value || '',
            sostenible: document.getElementById('sostenibleFilter')?.checked || false,
            rating: parseInt(document.getElementById('ratingFilter')?.value || 0)
        };
    }

    async searchServices() {
        if (this.isSearching) return;
        
        this.isSearching = true;
        this.setLoadingState(true);

        try {
            const searchTerm = document.getElementById('searchInput')?.value || '';
            const requestBody = {
                search: searchTerm,
                filters: this.filters,
                page: 1, // Para futura paginación
                limit: 20
            };

            const response = await fetch('includes/search_services.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestBody)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success) {
                this.displayResults(data.services);
                this.updateResultsCount(data.total || data.services.length);
            } else {
                throw new Error(data.message || 'Error en la búsqueda');
            }

        } catch (error) {
            console.error('Error en búsqueda:', error);
            this.showNotification('Error al realizar la búsqueda. Intenta nuevamente.', 'error');
            this.displayErrorState();
        } finally {
            this.isSearching = false;
            this.setLoadingState(false);
        }
    }

    displayResults(services) {
        const resultsContainer = document.getElementById('resultsContainer');
        if (!resultsContainer) return;

        if (!services || services.length === 0) {
            resultsContainer.innerHTML = this.getNoResultsHTML();
            return;
        }

        resultsContainer.innerHTML = services.map(service => this.createServiceCard(service)).join('');
        
        // Animar entrada de resultados
        this.animateResults();
    }

    createServiceCard(service) {
        const ratingStars = this.generateRatingStars(service.rating);
        const isSustainable = service.sostenible || service.es_sostenible;
        const priceType = service.tipo_precio || 'servicio';
        const imageUrl = service.imagen_principal || 'assets/images/placeholder-service.jpg';
        const description = service.descripcion ? 
            service.descripcion.substring(0, 100) + (service.descripcion.length > 100 ? '...' : '') : 
            'Descripción no disponible';

        return `
            <div class="service-result-card" data-service-id="${service.id}">
                <div class="service-image">
                    <img src="${imageUrl}" alt="${service.titulo}" loading="lazy" onerror="this.src='assets/images/placeholder-service.jpg'">
                    ${isSustainable ? '<span class="badge-sostenible">♻️ Certificado Sostenible</span>' : ''}
                    ${service.oferta ? '<span class="badge-oferta">🔥 Oferta Especial</span>' : ''}
                </div>
                <div class="service-info">
                    <h3>${this.escapeHtml(service.titulo)}</h3>
                    <p class="provider">🏢 ${this.escapeHtml(service.nombre_empresa)}</p>
                    <p class="location">📍 ${this.escapeHtml(service.ubicacion)}</p>
                    <div class="rating">${ratingStars} <span class="rating-text">${service.rating || 'Nuevo'}</span></div>
                    <p class="description">${this.escapeHtml(description)}</p>
                    <div class="service-meta">
                        <div class="price">$${service.precio} <small>/${priceType}</small></div>
                        <a href="servicio.php?id=${service.id}" class="btn btn-primary">Ver Detalles</a>
                    </div>
                </div>
            </div>
        `;
    }

    generateRatingStars(rating) {
        if (!rating || rating === 0) return '⭐ Nuevo';
        
        const fullStars = '★'.repeat(Math.floor(rating));
        const emptyStars = '☆'.repeat(5 - Math.floor(rating));
        return `<span class="stars">${fullStars}${emptyStars}</span>`;
    }

    getNoResultsHTML() {
        return `
            <div class="no-results">
                <div class="no-results-icon">🔍</div>
                <h3>No se encontraron servicios</h3>
                <p>Intenta ajustar tus filtros o términos de búsqueda</p>
                <button class="btn btn-outline" id="clearSearch">Limpiar búsqueda</button>
            </div>
        `;
    }

    animateResults() {
        const cards = document.querySelectorAll('.service-result-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('fade-in-up');
        });
    }

    setupIntersectionObserver() {
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, { threshold: 0.1 });

            // Observar elementos de resultados cuando se agreguen
            const resultsContainer = document.getElementById('resultsContainer');
            if (resultsContainer) {
                const config = { childList: true, subtree: true };
                const mutationObserver = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        mutation.addedNodes.forEach((node) => {
                            if (node.nodeType === 1 && node.classList.contains('service-result-card')) {
                                observer.observe(node);
                            }
                        });
                    });
                });
                mutationObserver.observe(resultsContainer, config);
            }
        }
    }

    setLoadingState(loading) {
        const searchBtn = document.getElementById('searchBtn');
        const resultsContainer = document.getElementById('resultsContainer');
        
        if (loading) {
            searchBtn?.classList.add('loading');
            searchBtn?.setAttribute('disabled', 'true');
            resultsContainer?.classList.add('loading');
            
            if (resultsContainer && !resultsContainer.querySelector('.loading-spinner')) {
                resultsContainer.innerHTML = `
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <p>Buscando servicios...</p>
                    </div>
                `;
            }
        } else {
            searchBtn?.classList.remove('loading');
            searchBtn?.removeAttribute('disabled');
            resultsContainer?.classList.remove('loading');
        }
    }

    displayErrorState() {
        const resultsContainer = document.getElementById('resultsContainer');
        if (resultsContainer) {
            resultsContainer.innerHTML = `
                <div class="error-state">
                    <div class="error-icon">⚠️</div>
                    <h3>Error de conexión</h3>
                    <p>No pudimos cargar los resultados. Verifica tu conexión e intenta nuevamente.</p>
                    <button class="btn btn-primary" onclick="searchManager.searchServices()">Reintentar</button>
                </div>
            `;
        }
    }

    updateResultsCount(count) {
        const countElement = document.getElementById('resultsCount');
        if (countElement) {
            countElement.textContent = `${count} resultado${count !== 1 ? 's' : ''} encontrado${count !== 1 ? 's' : ''}`;
        }
    }

    clearFilters() {
        // Limpiar inputs de filtros
        document.querySelectorAll('.filter-input').forEach(input => {
            if (input.type === 'checkbox') {
                input.checked = false;
            } else if (input.type === 'number') {
                input.value = '';
            } else {
                input.value = '';
            }
        });

        // Limpiar búsqueda
        const searchInput = document.getElementById('searchInput');
        if (searchInput) searchInput.value = '';

        this.updateFilters();
        this.updateURL();
        this.searchServices();
        
        this.showNotification('Filtros limpiados correctamente', 'success');
    }

    updateURL() {
        const params = new URLSearchParams();
        const searchTerm = document.getElementById('searchInput')?.value;
        
        if (searchTerm) params.set('search', searchTerm);
        if (this.filters.categoria) params.set('categoria', this.filters.categoria);
        if (this.filters.ubicacion) params.set('ubicacion', this.filters.ubicacion);
        if (this.filters.rating) params.set('rating', this.filters.rating);
        
        const newURL = `${window.location.pathname}?${params.toString()}`;
        window.history.replaceState({}, '', newURL);
    }

    loadFiltersFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Cargar búsqueda
        const searchInput = document.getElementById('searchInput');
        if (searchInput && urlParams.get('search')) {
            searchInput.value = urlParams.get('search');
        }
        
        // Cargar filtros
        if (urlParams.get('categoria')) {
            this.filters.categoria = urlParams.get('categoria');
            const categoriaFilter = document.getElementById('categoriaFilter');
            if (categoriaFilter) categoriaFilter.value = this.filters.categoria;
        }
        
        // Aplicar búsqueda si hay parámetros
        if (urlParams.toString()) {
            setTimeout(() => this.searchServices(), 100);
        }
    }

    showNotification(message, type = 'info') {
        // Implementación básica de notificación
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()">×</button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}

// Inicializar búsqueda mejorada
document.addEventListener('DOMContentLoaded', function() {
    window.searchManager = new SearchManager();
});