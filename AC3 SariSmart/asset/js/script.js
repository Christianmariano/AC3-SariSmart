document.addEventListener("DOMContentLoaded", function () {
  // Navbar menu toggle
  const navbarMenu = document.querySelector(".navbar .links");
  const hamburgerBtn = document.querySelector(".hamburger-btn");
  const hideMenuBtn = navbarMenu.querySelector(".close-btn");

  hamburgerBtn.addEventListener("click", () => {
    navbarMenu.classList.toggle("show-menu");
  });

  hideMenuBtn.addEventListener("click", () => {
    hamburgerBtn.click(); // Reuse the same toggle logic
  });

  // Popup and form handling
  const showPopupBtn = document.querySelector(".login-btn");
  const formPopup = document.querySelector(".form-popup");
  const hidePopupBtn = formPopup.querySelector(".close-btn");
  const blurOverlay = document.querySelector(".blur-bg-overlay");

  const loginForm = document.querySelector(".form-box.login");
  const forgotPasswordForm = document.querySelector(".form-box.forgot-password");
  const signupForm = document.querySelector(".form-box.signup");

  const forgotPassLink = document.querySelector(".forgot-pass-link");
  const backToLoginLink = document.querySelector("#back-to-login-link");
  const signupLink = document.querySelector("#signup-link");
  const loginLink = document.querySelector("#login-link");

  // Show popup
  showPopupBtn.addEventListener("click", () => {
    document.body.classList.add("show-popup");
    loginForm.style.display = "flex";
    forgotPasswordForm.style.display = "none";
    signupForm.style.display = "none";
  });

  // Hide popup
  function hidePopup() {
    document.body.classList.remove("show-popup");
  }

  hidePopupBtn.addEventListener("click", hidePopup);
  blurOverlay.addEventListener("click", hidePopup);

  // Form switches
  forgotPassLink.addEventListener("click", (e) => {
    e.preventDefault();
    loginForm.style.display = "none";
    forgotPasswordForm.style.display = "flex";
    signupForm.style.display = "none";
  });

  backToLoginLink.addEventListener("click", (e) => {
    e.preventDefault();
    loginForm.style.display = "flex";
    forgotPasswordForm.style.display = "none";
    signupForm.style.display = "none";
  });

  signupLink.addEventListener("click", (e) => {
    e.preventDefault();
    loginForm.style.display = "none";
    forgotPasswordForm.style.display = "none";
    signupForm.style.display = "flex";
  });

  loginLink.addEventListener("click", (e) => {
    e.preventDefault();
    loginForm.style.display = "flex";
    forgotPasswordForm.style.display = "none";
    signupForm.style.display = "none";
  });
});
