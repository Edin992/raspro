// assets/js/dashboard.js
document.addEventListener('DOMContentLoaded', function() {
    // Osvježi broj poruka svakih 60 sekundi
    setInterval(function() {
        fetch('?page=ajax&action=getUnreadCount')
            .then(response => response.json())
            .then(data => {
                if (data.unreadMessages > 0) {
                    const badge = document.querySelector('.badge.bg-danger');
                    if (badge) badge.textContent = data.unreadMessages;
                }
            });
    }, 60000);
    
    // Chart.js za statistiku (ako koristite)
    // ...
});