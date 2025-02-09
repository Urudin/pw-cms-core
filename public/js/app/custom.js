document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.Livewire !== 'undefined' && typeof window.Livewire.dispatch === 'function') {
        console.log("✅ Livewire 3 betöltődött!");
        initializeTiptapMedia();
    } else {
        console.warn("⚠️ Livewire nincs teljesen betöltve, újrapróbálkozás...");
        setTimeout(arguments.callee, 200);
    }
});

function initializeTiptapMedia() {
    console.log("🚀 Livewire események inicializálása...");

    // Livewire 3.x eseményküldés
    document.getElementById('insertImageBtn')?.addEventListener('click', function() {
        console.log("📸 Kép hozzáadása gomb megnyomva!");
        window.Livewire.dispatch('openMediaPicker'); // Livewire 3.x kompatibilis
    });

    // Meghallgatja az eseményt és beilleszti a képet a Tiptap szerkesztőbe
    document.addEventListener('insert-image', function(event) {
        const url = event.detail.url;
        const editorElement = document.querySelector('.ProseMirror');

        if (editorElement) {
            const tiptapInstance = editorElement.__tiptapEditor; // Filament így tárolhatja az instance-t
            console.log(tiptapInstance);
        }

        if (editorElement && editorElement.__tiptapEditor) {
            console.log(`🖼️ Kép beillesztése: ${url}`);
            editorElement.__tiptapEditor.chain().focus().setImage({ src: url }).run();
        }
    });
}
