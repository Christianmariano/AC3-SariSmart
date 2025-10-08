  const burger = document.getElementById('burger');
  const sideMenu = document.getElementById('side-menu');

  burger.addEventListener('click', () => {
    sideMenu.classList.toggle('active');
  });

const profileImg = document.getElementById('profile-img');
const dropdownMenu = document.getElementById('dropdown-menu');
const dropdownArrow = document.getElementById('dropdown-arrow');

profileImg.addEventListener('click', (e) => {
  // Only open dropdown if it's closed
  if (!dropdownMenu.classList.contains('show')) {
    dropdownMenu.classList.add('show');
    dropdownArrow.textContent = '▲'; // up arrow when open
  }
});

dropdownArrow.addEventListener('click', (e) => {
  e.stopPropagation(); // prevent bubbling
  if (dropdownMenu.classList.contains('show')) {
    dropdownMenu.classList.remove('show');
    dropdownArrow.textContent = '▼'; // down arrow when closed
  } else {
    dropdownMenu.classList.add('show');
    dropdownArrow.textContent = '▲'; // up arrow when open
  }
});