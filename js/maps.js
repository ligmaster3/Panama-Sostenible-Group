// Sistema de mapas y geolocalización
class MapManager {
    constructor() {
        this.map = null;
        this.markers = [];
        this.userLocation = null;
        this.init();
    }

    async init() {
        await this.loadGoogleMaps();
        this.initMap();
        this.bindEvents();
    }

    loadGoogleMaps() {
        return new Promise((resolve) => {
            if(window.google && window.google.maps) {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=TU_API_KEY&libraries=places`;
            script.onload = resolve;
            document.head.appendChild(script);
        });
    }

    initMap() {
        const mapElement = document.getElementById('map');
        if(!mapElement) return;

        this.map = new google.maps.Map(mapElement, {
            center: { lat: 8.537981, lng: -80.782127 }, // Centro de Panamá
            zoom: 8,
            styles: [
                {
                    featureType: "poi",
                    elementType: "labels",
                    stylers: [{ visibility: "on" }]
                }
            ]
        });

        this.loadServicesOnMap();
    }

    bindEvents() {
        // Geolocalización
        const locateBtn = document.getElementById('locateBtn');
        if(locateBtn) {
            locateBtn.addEventListener('click', this.locateUser.bind(this));
        }

        // Búsqueda de lugares
        this.initSearchBox();
    }

    initSearchBox() {
        const input = document.getElementById('mapSearch');
        if(input && this.map) {
            const searchBox = new google.maps.places.SearchBox(input);
            
            searchBox.addListener('places_changed', () => {
                const places = searchBox.getPlaces();
                if(places.length === 0) return;

                const place = places[0];
                this.map.setCenter(place.geometry.location);
                this.map.setZoom(14);
            });
        }
    }

    locateUser() {
        if(navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    this.map.setCenter(this.userLocation);
                    this.map.setZoom(14);
                    
                    // Agregar marcador de ubicación actual
                    new google.maps.Marker({
                        position: this.userLocation,
                        map: this.map,
                        title: 'Tu ubicación',
                        icon: {
                            url: 'assets/icons/user-location.png',
                            scaledSize: new google.maps.Size(30, 30)
                        }
                    });
                },
                (error) => {
                    console.error('Error getting location:', error);
                    alert('No se pudo obtener tu ubicación');
                }
            );
        }
    }

    async loadServicesOnMap() {
        try {
            const response = await fetch('includes/get_services_map.php');
            const services = await response.json();
            
            this.addServicesToMap(services);
        } catch (error) {
            console.error('Error loading services:', error);
        }
    }

    addServicesToMap(services) {
        this.markers.forEach(marker => marker.setMap(null));
        this.markers = [];

        services.forEach(service => {
            if(service.lat && service.lng) {
                const marker = new google.maps.Marker({
                    position: { lat: parseFloat(service.lat), lng: parseFloat(service.lng) },
                    map: this.map,
                    title: service.titulo,
                    icon: this.getMarkerIcon(service.categoria)
                });

                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div class="map-info-window">
                            <h4>${service.titulo}</h4>
                            <p>${service.nombre_empresa}</p>
                            <p>📍 ${service.ubicacion}</p>
                            <p>$${service.precio} / ${service.tipo_precio}</p>
                            <a href="servicio.php?id=${service.id}">Ver detalles</a>
                        </div>
                    `
                });

                marker.addListener('click', () => {
                    infoWindow.open(this.map, marker);
                });

                this.markers.push(marker);
            }
        });
    }

    getMarkerIcon(categoria) {
        const icons = {
            'guia_turistico': 'assets/icons/guide.png',
            'hoteleria': 'assets/icons/hotel.png',
            'restaurante': 'assets/icons/restaurant.png',
            'artesania': 'assets/icons/craft.png',
            'transporte': 'assets/icons/transport.png'
        };
        
        return icons[categoria] || 'assets/icons/default.png';
    }
}

// Inicializar mapa
document.addEventListener('DOMContentLoaded', function() {
    new MapManager();
});