function closePopup() {
            document.getElementById("signup-popup").style.display = "none";
            history.replaceState(null, "", window.location.pathname);
        }

        function closeResetPopup() {
            document.getElementById("reset-popup").style.display = "none";
            history.replaceState(null, "", window.location.pathname);
        }

        function closeLoginPopup() {
            document.getElementById("login-popup").style.display = "none";
            history.replaceState(null, "", window.location.pathname);
        }

        window.addEventListener("DOMContentLoaded", () => {
            const urlParams = new URLSearchParams(window.location.search);

            if (urlParams.get("signup") === "success") {
                document.getElementById("signup-popup").style.display = "block";
            }

            if (urlParams.get("reset") === "success") {
                document.getElementById("reset-popup").style.display = "block";
            }

            if (urlParams.get("login") === "success") {
                document.getElementById("login-popup").style.display = "block";
            }
        });