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