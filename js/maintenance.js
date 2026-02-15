// Maintenance Page JavaScript
// Auto check if maintenance is over every 15 seconds
setInterval(function () {
    fetch(window.location.href)
        .then(response => {
            if (response.redirected) {
                window.location.reload();
            }
        })
        .catch(err => console.log('Check failed'));
}, 15000);
