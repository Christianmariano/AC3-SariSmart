function togglePassword(fieldId, toggleIcon) {
    const passwordField = document.getElementById(fieldId);
    const eyeIcon = toggleIcon.querySelector("img");

    if (passwordField.type === "password") {
        passwordField.type = "text";
        eyeIcon.src = "images/eye-open.png"; // Add this image to your images folder
    } else {
        passwordField.type = "password";
        eyeIcon.src = "images/eye-close.png";
    }
}
