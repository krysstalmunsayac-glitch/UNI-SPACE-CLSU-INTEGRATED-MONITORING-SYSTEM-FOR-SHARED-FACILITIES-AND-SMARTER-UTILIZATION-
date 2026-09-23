export function bookingRequestForm(config) {
    return {
        ...config,
        submitting: false,
        availability: {},
        availabilityLoading: false,
        availabilityError: '',
        amenityAvailability: config.amenityAvailability ?? {},
        scheduleValidationError: '',
        activePhoto: null,

        openPhoto(index) {
            this.activePhoto = index;
            document.body.classList.add('overflow-hidden');
        },
        closePhoto() {
            this.activePhoto = null;
            document.body.classList.remove('overflow-hidden');
        },
        previousPhoto() {
            this.activePhoto = (this.activePhoto - 1 + this.photos.length) % this.photos.length;
        },
        nextPhoto() {
            this.activePhoto = (this.activePhoto + 1) % this.photos.length;
        },
        syncDailySchedules() {
            const startValue = this.$refs.startDate?.value;
            let endValue = this.$refs.endDate?.value;
            if (!startValue || !endValue) return;
            if (endValue < startValue) {
                endValue = startValue;
                this.$refs.endDate.value = startValue;
            }

            const previous = new Map(this.dailySchedules.map(schedule => [schedule.date, schedule]));
            const current = new Date(`${startValue}T12:00:00`);
            const last = new Date(`${endValue}T12:00:00`);
            const schedules = [];
            const formatDate = date => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

            while (current <= last && schedules.length < 31) {
                const date = formatDate(current);
                schedules.push(previous.get(date) ?? { date, start: this.sharedStartTime, end: this.sharedEndTime });
                current.setDate(current.getDate() + 1);
            }

            this.dailySchedules = schedules;
            if (!this.customizeDailyTimes) this.applySharedTime();
            this.loadAvailability();
        },
        applySharedTime() {
            this.dailySchedules = this.dailySchedules.map(schedule => ({ ...schedule, start: this.sharedStartTime, end: this.sharedEndTime }));
            if (this.dailySchedules.length && this.dailySchedules.every(schedule => schedule.start && schedule.end)) this.scheduleValidationError = '';
            this.loadAvailability();
        },
        minimumEndTime(startTime) {
            if (!startTime) return null;
            const [hours, minutes] = startTime.split(':').map(Number);
            const minimumMinutes = (hours * 60) + minutes + 60;
            if (minimumMinutes >= 1440) return '24:00';
            return `${String(Math.floor(minimumMinutes / 60)).padStart(2, '0')}:${String(minimumMinutes % 60).padStart(2, '0')}`;
        },
        addMinutes(time, minutes) {
            const [hours, mins] = time.split(':').map(Number);
            const total = Math.min((hours * 60) + mins + minutes, 24 * 60);
            return `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
        },
        chooseSharedStart(time) {
            this.sharedStartTime = time;
            this.sharedEndTime = this.addMinutes(time, 60);
            this.applySharedTime();
        },
        chooseDayStart(schedule, time) {
            schedule.start = time;
            schedule.end = this.addMinutes(time, 60);
            if (this.dailySchedules.every(item => item.start && item.end)) this.scheduleValidationError = '';
            this.loadAvailability();
        },
        async loadAvailability() {
            const from = this.$refs.startDate?.value;
            const to = this.$refs.endDate?.value;
            if (!from || !to) return;
            this.availabilityLoading = true;
            this.availabilityError = '';
            try {
                const url = new URL(this.availabilityUrl, window.location.origin);
                url.searchParams.set('from', from);
                url.searchParams.set('to', to);
                if (this.dailySchedules.every(schedule => schedule.date && schedule.start && schedule.end)) {
                    this.dailySchedules.forEach((schedule, index) => {
                        url.searchParams.set(`schedules[${index}][date]`, schedule.date);
                        url.searchParams.set(`schedules[${index}][start]`, schedule.start);
                        url.searchParams.set(`schedules[${index}][end]`, schedule.end);
                    });
                }
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Availability could not be loaded.');
                const data = await response.json();
                this.availability = data.days;
                this.amenityAvailability = data.amenities ?? this.amenityAvailability;
            } catch (error) {
                this.availability = {};
                this.availabilityError = error.message;
            } finally {
                this.availabilityLoading = false;
            }
        },
        amenityRemaining(id) {
            return Number(this.amenityAvailability[String(id)] ?? 0);
        },
        slotStatus(date, start, end) {
            if (date < this.bookingToday || (date === this.bookingToday && start <= this.bookingCurrentTime)) return 'past';
            const day = this.availability[date];
            if (!day || day.closed) return 'unavailable';
            let status = 'available';
            for (const range of day.ranges || []) {
                if (start < range.blocked_end && end > range.blocked_start) {
                    if (range.status === 'approved') return 'approved';
                    status = 'pending';
                }
            }
            return status;
        },
        scheduleStatus(schedule) {
            return schedule?.start && schedule?.end ? this.slotStatus(schedule.date, schedule.start, schedule.end) : 'incomplete';
        },
        hasApprovedConflict() { return this.dailySchedules.some(schedule => this.scheduleStatus(schedule) === 'approved'); },
        hasPendingWarning() { return !this.hasApprovedConflict() && this.dailySchedules.some(schedule => this.scheduleStatus(schedule) === 'pending'); },
        hasClosure() { return this.dailySchedules.some(schedule => this.scheduleStatus(schedule) === 'unavailable'); },
        hasPastTime() { return this.dailySchedules.some(schedule => this.scheduleStatus(schedule) === 'past'); },
        hasBlockingConflict() { return this.hasApprovedConflict() || this.hasClosure() || this.hasPastTime(); },
        sharedStartDisabled(slot) { return this.dailySchedules.some(schedule => ['approved', 'unavailable', 'past'].includes(this.slotStatus(schedule.date, slot, this.addMinutes(slot, 60)))); },
        sharedEndDisabled(slot) { return this.dailySchedules.some(schedule => ['approved', 'unavailable', 'past'].includes(this.slotStatus(schedule.date, this.sharedStartTime, slot))); },
        slotLabel(status) {
            return status === 'approved' ? ' — Already Booked' : status === 'past' ? ' — Time Elapsed' : status === 'unavailable' ? ' — Unavailable' : '';
        },
        duration(schedule) {
            if (!schedule?.start || !schedule?.end) return 0;
            const [sh, sm] = schedule.start.split(':').map(Number);
            const [eh, em] = schedule.end.split(':').map(Number);
            return ((eh * 60) + em) - ((sh * 60) + sm);
        },
        formatTime(time) {
            if (time === '24:00') return '12:00 AM';
            return new Date(`2000-01-01T${time}:00`).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        },
        useOneTimeForAllDays() {
            this.customizeDailyTimes = false;
            this.applySharedTime();
        },
    };
}
