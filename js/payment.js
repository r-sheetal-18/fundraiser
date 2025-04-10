function checkStatus(orderId) {
    // In a real application, implement proper status checking
    // This is just a simple example
    fetch('verify_payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `order_id=${orderId}&reference=MANUAL_VERIFY_${Date.now()}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}