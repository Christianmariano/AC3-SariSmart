const searchInput = document.getElementById("searchInput");
const profileContainer = document.querySelector(".profile-form-container");

// Save original HTML to restore highlights
const originalHTML = profileContainer.innerHTML;

// Escape special regex characters
function escapeRegex(string) {
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Function to recursively highlight text nodes
function highlightText(term) {
  // Restore original HTML first
  profileContainer.innerHTML = originalHTML;

  if (!term) return;

  const regex = new RegExp(`(${escapeRegex(term)})`, "gi");

  function traverse(node) {
    // Only process element or text nodes
    if (node.nodeType === Node.TEXT_NODE) {
      // Skip if parent is already a highlight
      if (node.parentNode.tagName === 'SPAN' && node.parentNode.style.backgroundColor === 'yellow') {
        return;
      }
      const text = node.nodeValue;
      if (regex.test(text)) {
        const temp = document.createElement('span');
        temp.innerHTML = text.replace(regex, `<span style="background-color: yellow;">$1</span>`);
        node.parentNode.replaceChild(temp, node);
      }
    } else if (node.nodeType === Node.ELEMENT_NODE) {
      // Avoid replacing highlight spans recursively
      if (node.tagName === 'SPAN' && node.style.backgroundColor === 'yellow') return;
      node.childNodes.forEach(child => traverse(child));
    }
  }

  traverse(profileContainer);
}

// Typing search
searchInput.addEventListener("input", () => {
  highlightText(searchInput.value.trim());
});

// Voice recognition
function startDictation() {
  if (!('webkitSpeechRecognition' in window)) {
    alert("Your browser does not support speech recognition.");
    return;
  }

  const recognition = new webkitSpeechRecognition();
  recognition.continuous = false;
  recognition.interimResults = false;
  recognition.lang = "en-US";

  recognition.start();

  recognition.onresult = function(event) {
    const transcript = event.results[0][0].transcript.trim();
    searchInput.value = transcript;
    highlightText(transcript);
  };

  recognition.onerror = function(event) {
    console.error("Speech recognition error:", event.error);
  };

  recognition.onend = function() {
    recognition.stop();
  };
}