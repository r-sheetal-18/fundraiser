document.getElementById("loginForm").addEventListener("submit", function(event) {
    event.preventDefault(); // Prevent default form submission

    let formData = new FormData(this);

    fetch("login.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log("Server Response:", data); // Debugging: Check response

        if (data.status === "success") {
            // ✅ Ensure session is set before redirection
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 500); // Small delay for session stabilization
        } else {
            alert(data.message); // Show error message
        }
    })
    .catch(error => console.error("Error:", error));
});
