// Sistema de reservas
class ReservationSystem {
    constructor() {
        this.selectedService = null;
        this.reservationData = {
            fecha_inicio: '',
            fecha_fin: '',
            personas: 1,
            notas: ''
        };
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadServiceData();
    }

    bindEvents() {
        // Selector de fecha
        const fechaInicio = document.getElementById('fechaInicio');
        const fechaFin = document.getElementById('fechaFin');
        
        if(fechaInicio) {
            fechaInicio.addEventListener('change', this.updateTotal.bind(this));
        }
        if(fechaFin) {
            fechaFin.addEventListener('change', this.updateTotal.bind(this));
        }

        // Selector de personas
        const personasInput = document.getElementById('personas');
        if(personasInput) {
            personasInput.addEventListener('change', this.updateTotal.bind(this));
        }

        // Botón reservar
        const reservarBtn = document.getElementById('reservarBtn');
        if(reservarBtn) {
            reservarBtn.addEventListener('click', this.createReservation.bind(this));
        }
    }

    loadServiceData() {
        const serviceData = document.getElementById('serviceData');
        if(serviceData) {
            this.selectedService = JSON.parse(serviceData.dataset.service);
            this.updateTotal();
        }
    }

    updateTotal() {
        if(!this.selectedService) return;

        const fechaInicio = new Date(document.getElementById('fechaInicio').value);
        const fechaFin = new Date(document.getElementById('fechaFin').value);
        const personas = parseInt(document.getElementById('personas').value) || 1;

        if(fechaInicio && fechaFin && fechaInicio < fechaFin) {
            const diffTime = Math.abs(fechaFin - fechaInicio);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            let total = 0;
            switch(this.selectedService.tipo_precio) {
                case 'hora':
                    const diffHours = Math.ceil(diffTime / (1000 * 60 * 60));
                    total = this.selectedService.precio * diffHours * personas;
                    break;
                case 'dia':
                    total = this.selectedService.precio * diffDays * personas;
                    break;
                case 'persona':
                    total = this.selectedService.precio * personas;
                    break;
                case 'fijo':
                    total = this.selectedService.precio;
                    break;
            }

            document.getElementById('totalReserva').textContent = `$${total.toFixed(2)}`;
        }
    }

    async createReservation() {
        if(!this.validateReservation()) return;

        const reservationData = {
            servicio_id: this.selectedService.id,
            fecha_inicio: document.getElementById('fechaInicio').value,
            fecha_fin: document.getElementById('fechaFin').value,
            personas: document.getElementById('personas').value,
            notas: document.getElementById('notasReserva').value,
            total: document.getElementById('totalReserva').textContent.replace('$', '')
        };

        try {
            const response = await fetch('includes/create_reservation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(reservationData)
            });

            const result = await response.json();
            
            if(result.success) {
                this.showSuccessMessage(result.reserva_id);
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Error al crear la reservación');
        }
    }

    validateReservation() {
        // Implementar validación completa
        return true;
    }

    showSuccessMessage(reservaId) {
        alert(`¡Reserva creada exitosamente! ID: ${reservaId}`);
        window.location.href = 'mis-reservas.php';
    }

    showError(message) {
        alert(`Error: ${message}`);
    }
}

// Inicializar sistema de reservas
document.addEventListener('DOMContentLoaded', function() {
    new ReservationSystem();
});