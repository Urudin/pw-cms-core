document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.Livewire !== 'undefined' && typeof window.Livewire.dispatch === 'function') {
        console.log("✅ Livewire 3 betöltődött!");
        initializeMonacoMedia();
    } else {
        console.warn("⚠️ Livewire nincs teljesen betöltve, újrapróbálkozás...");
        setTimeout(arguments.callee, 200);
    }
});

function getMonacoEditorInstance(retries = 10) {
    const editors = monaco.editor.getEditors();

    if (editors.length > 0) {
        return editors[0]; // Ha már létezik egy editor, visszaadjuk
    } else if (retries > 0) {
        console.warn(`⌛ Monaco Editor még nem elérhető, újrapróbálás (${retries})`);
        return new Promise((resolve) => {
            setTimeout(() => resolve(getMonacoEditorInstance(retries - 1)), 500);
        });
    } else {
        console.error("❌ Monaco Editor nem található!");
        return null;
    }
}

function initializeMonacoMedia() {
    console.log("🚀 Livewire események inicializálása Monaco Editorhoz...");

    // Ellenőrizzük, hogy már hozzáadtuk-e az eseményfigyelőt
    if (window.__monacoMediaInitialized) {
        console.warn("⚠️ Monaco Media már inicializálva!");
        return;
    }
    window.__monacoMediaInitialized = true; // Megjelöljük, hogy inicializáltuk

    // Livewire eseményküldés (Médiatár megnyitása)
    document.getElementById('insertImageBtn')?.addEventListener('click', function() {
        console.log("📸 Kép hozzáadása gomb megnyomva!");
        window.Livewire.dispatch('openMediaPicker');
    });

    // Meghallgatja az eseményt és beilleszti az <img> taget a Monaco Editorba
    document.addEventListener('insert-image', async function(event) {
        const url = event.detail.url;
        console.log(`🖼️ Kép beillesztése: ${url}`);

        const editor = await getMonacoEditorInstance();

        if (editor) {
            const imgTag = `<img src="${url}" alt="Image">`;

            editor.focus();
            const position = editor.getPosition();
            editor.executeEdits("", [{
                range: new monaco.Range(position.lineNumber, position.column, position.lineNumber, position.column),
                text: imgTag,
                forceMoveMarkers: true
            }]);

            console.log("✅ Kép beillesztve a Monaco szerkesztőbe!");
        } else {
            console.error("❌ Monaco Editor továbbra sem található!");
        }
    });
}
