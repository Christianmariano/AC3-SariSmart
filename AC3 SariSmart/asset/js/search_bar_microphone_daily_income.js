const searchInput = document.getElementById("searchInput");
const rows = document.querySelectorAll("#recordsTable tbody tr");

// Live filter while typing
searchInput.addEventListener("keyup", searchTable);

function searchTable() {
  const input = searchInput.value.toLowerCase();
  rows.forEach(row => {
    const cells = row.querySelectorAll("td");
    let matchFound = false;

    // Reset old highlights
    cells.forEach(cell => {
      cell.innerHTML = cell.textContent;
    });

    // Check each cell
    cells.forEach(cell => {
      const text = cell.textContent.toLowerCase();
      if (text.includes(input) && input !== "") {
        matchFound = true;
        // Highlight matching part
        const regex = new RegExp(`(${input})`, "gi");
        cell.innerHTML = cell.textContent.replace(regex, `<span class="highlight">$1</span>`);
      }
    });

    /* Show/hide row
    row.style.display = (input === "" || matchFound) ? "" : "none";*/
  });
}

// 🎤 Voice Search
function startDictation() {
  if ('webkitSpeechRecognition' in window) {
    const recognition = new webkitSpeechRecognition();
    recognition.continuous = false;
    recognition.interimResults = false;
    recognition.lang = "en-US";

    recognition.start();

    recognition.onresult = function(event) {
      const transcript = event.results[0][0].transcript;
      searchInput.value = transcript; // show in search box
      recognition.stop();
      searchTable(); // filter & highlight instantly
    };

    recognition.onerror = function(event) {
      recognition.stop();
      alert("Speech recognition error: " + event.error);
    };
  } else {
    alert("Speech recognition not supported in this browser. Try Chrome.");
  }
}