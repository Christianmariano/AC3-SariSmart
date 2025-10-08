function closeResetPopup() {
            document.getElementById("reset-popup").style.display = "none";
            history.replaceState(null, "", window.location.pathname);
        }

        window.addEventListener("DOMContentLoaded", () => {
            const urlParams = new URLSearchParams(window.location.search);

            if (urlParams.get("reset") === "success") {
                document.getElementById("reset-popup").style.display = "block";
            }
        });