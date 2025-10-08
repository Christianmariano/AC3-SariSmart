// ✅ Recursive function to highlight text in nodes, skip charts or elements we don't want to change
function highlightText(node, term) {
    if (!term) return;

    const regex = new RegExp(`(${term})`, 'gi');

    // Skip <canvas> elements (charts) or already highlighted spans
    if (node.nodeType === 1 && (node.tagName === 'CANVAS' || node.classList.contains('highlight'))) {
        return;
    }

    if (node.nodeType === 3) { // Text node
        const parent = node.parentNode;
        if (regex.test(node.nodeValue)) {
            const span = document.createElement('span');
            span.className = 'highlight';
            span.innerHTML = node.nodeValue.replace(regex, '<span class="highlight">$1</span>');
            parent.replaceChild(span, node);
        }
    } else if (node.nodeType === 1) {
        Array.from(node.childNodes).forEach(child => highlightText(child, term));
    }
}

// ✅ Remove previous highlights safely
function removeHighlights() {
    document.querySelectorAll('span.highlight').forEach(span => {
        const parent = span.parentNode;
        parent.replaceChild(document.createTextNode(span.textContent), span);
        parent.normalize(); // merge adjacent text nodes
    });
}

// ✅ Main highlight function
function highlightSearch(term) {
    removeHighlights();
    if (!term) return;

    document.querySelectorAll('.content, .content-2').forEach(container => {
        highlightText(container, term);
    });

    // Scroll to first highlight
    const firstMatch = document.querySelector('.highlight');
    if (firstMatch) firstMatch.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Trigger highlight on Enter
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === "Enter") {
        e.preventDefault();
        highlightSearch(this.value.trim());
    }
});

// Mic search support
function startDictation() {
    if (window.hasOwnProperty('webkitSpeechRecognition')) {
        const recognition = new webkitSpeechRecognition();
        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.lang = "en-US";

        recognition.start();

        recognition.onresult = function(e) {
            const speechResult = e.results[0][0].transcript;
            document.getElementById('searchInput').value = speechResult;
            highlightSearch(speechResult);
            recognition.stop();
        };

        recognition.onerror = function() {
            recognition.stop();
        };
    } else {
        alert("Speech recognition not supported in this browser.");
    }
}