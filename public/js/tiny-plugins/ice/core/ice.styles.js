// ice.styles.js - Phase A
ICE.styles = (function() {
    const authorColors = {};
    const colorPalette = ['#d0f0d0', '#d0e0f0', '#f0d0d0', '#f0f0d0'];
    let nextColorIndex = 0;

    function ensureStyle(editor, userId, className) {
        const doc = editor.getDoc();
        if (doc.getElementById('ice-style-' + className)) return;
        const style = doc.createElement('style');
        style.id = 'ice-style-' + className;
        const color = authorColors[userId] || (authorColors[userId] = colorPalette[nextColorIndex++ % colorPalette.length]);
        style.innerHTML = `.${className} { background-color: ${color}; }`;
        doc.head.appendChild(style);
    }

    return {
        ensureStyle: ensureStyle
    };
})();