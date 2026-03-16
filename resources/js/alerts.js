window.Alert = {

    success(msg) {
        Swal.fire({
            icon: 'success',
            text: msg
        });
    },

    error(msg) {
        Swal.fire({
            icon: 'error',
            text: msg
        });
    },

    confirm(msg) {
        return Swal.fire({
            title: msg,
            icon: 'warning',
            showCancelButton: true
        });
    }

};