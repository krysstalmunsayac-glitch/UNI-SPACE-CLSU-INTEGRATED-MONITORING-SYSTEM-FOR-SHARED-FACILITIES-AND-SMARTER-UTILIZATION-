window.addEventListener('swal', event => {
    const {
        title = 'Success',
        text = '',
        icon = 'success',
        timer = 2500,
        showConfirmButton = false,
    } = event?.detail ?? {};

    if (typeof Swal === 'undefined') {
        return;
    }

    Swal.fire({
        title,
        text,
        icon,
        timer,
        showConfirmButton,
        timerProgressBar: true,
        position: 'top-end',
        toast: false,
        customClass: {
            popup: 'rounded-2xl',
        },
    });
});

window.facilityLocationPicker = function (livewire) {
    return {
        map: null,
        marker: null,
        latitude: null,
        longitude: null,
        searching: false,

        openPicker() {
            window.setTimeout(() => {
                if (!window.L || !this.$refs.map) return;

                const savedLatitude = Number(livewire.get('Latitude'));
                const savedLongitude = Number(livewire.get('Longitude'));
                const hasSavedPin = Number.isFinite(savedLatitude)
                    && Number.isFinite(savedLongitude)
                    && savedLatitude !== 0
                    && savedLongitude !== 0;
                const center = hasSavedPin
                    ? [savedLatitude, savedLongitude]
                    : [15.7354, 120.9335];

                if (!this.map) {
                    this.map = L.map(this.$refs.map, { scrollWheelZoom: false }).setView(center, hasSavedPin ? 18 : 16);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 20,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(this.map);
                    this.map.on('click', event => this.setPin(event.latlng.lat, event.latlng.lng));
                }

                if (hasSavedPin) this.setPin(savedLatitude, savedLongitude, false);
                this.map.invalidateSize();
                this.map.setView(center, hasSavedPin ? 18 : 16);
            }, 250);
        },

        setPin(latitude, longitude, updateLivewire = true) {
            this.latitude = Number(latitude).toFixed(7);
            this.longitude = Number(longitude).toFixed(7);

            if (!this.marker) {
                this.marker = L.marker([latitude, longitude], { draggable: true }).addTo(this.map);
                this.marker.on('dragend', event => {
                    const position = event.target.getLatLng();
                    this.setPin(position.lat, position.lng);
                });
            } else {
                this.marker.setLatLng([latitude, longitude]);
            }

            if (updateLivewire) {
                livewire.set('Latitude', Number(this.latitude));
                livewire.set('Longitude', Number(this.longitude));
            }
        },

        async findLocation() {
            const location = livewire.get('Location');
            const facilityName = livewire.get('Facility_Name');
            if (!location && !facilityName) return;

            this.searching = true;
            const query = [facilityName, location, 'Central Luzon State University', 'Science City of Muñoz', 'Nueva Ecija']
                .filter(Boolean)
                .join(', ');

            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=${encodeURIComponent(query)}`);
                const result = (await response.json())[0];
                if (result) {
                    this.setPin(Number(result.lat), Number(result.lon));
                    this.map.setView([Number(result.lat), Number(result.lon)], 18);
                }
            } finally {
                this.searching = false;
            }
        },

        clearPin() {
            if (this.marker) {
                this.marker.remove();
                this.marker = null;
            }
            this.latitude = null;
            this.longitude = null;
            livewire.set('Latitude', null);
            livewire.set('Longitude', null);
        },
    };
};
