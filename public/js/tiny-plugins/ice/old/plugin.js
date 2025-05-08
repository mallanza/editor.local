
tinymce.PluginManager.add('ice', function (editor) {
    let currentUser = { id: 1, name: 'Default User' };
    let showChanges = true;
    let changeCounter = 1;

    function nextChangeId() {
        return changeCounter++;
    }

    function now() {
        return Date.now();
    }

    function userAttrs() {
        return `data-cid="${nextChangeId()}" data-userid="${currentUser.id}" data-username="${currentUser.name}" data-time="${now()}"`;
    }

    function wrapInsertion(html) {
        return `<span class="ice-insert" ${userAttrs()}>${html}</span>`;
    }

    function wrapDeletion(html) {
        return `<span class="ice-delete" ${userAttrs()}>${html}</span>`;
    }

    function applyStyleToEditor(css) {
        const head = editor.getDoc().head;
        let styleTag = head.querySelector('#ice-style');
        if (!styleTag) {
            styleTag = editor.getDoc().createElement('style');
            styleTag.id = 'ice-style';
            head.appendChild(styleTag);
        }
        styleTag.innerHTML = css;
    }

    function acceptRejectChange(node, accept) {
        const editorDom = editor.dom;
        const changeId = editorDom.getAttrib(node, 'data-cid');
        if (!changeId) return;

        const selector = `[data-cid="${changeId}"]`;
        const all = editorDom.select(selector);
        all.forEach(el => {
            const isInsert = editorDom.hasClass(el, 'ice-insert');
            const isDelete = editorDom.hasClass(el, 'ice-delete');
            if ((accept && isInsert) || (!accept && isDelete)) {
                editorDom.remove(el, true); // unwrap
            } else {
                editorDom.remove(el); // delete
            }
        });
    }

    editor.addCommand('ice_toggle_changes', () => {
        showChanges = !showChanges;
        const css = `
            .ice-delete { display: ${showChanges ? 'inline' : 'none'} !important; text-decoration: line-through !important; color: red !important; }
            .ice-insert { background-color: ${showChanges ? '#d0f0d0' : 'transparent'} !important; }
        `;
        applyStyleToEditor(css);
    });

    editor.addCommand('ice_change_user', (ui, value) => {
        currentUser = value;
    });

    editor.addCommand('ice_accept_change', () => {
        const node = editor.selection.getNode();
        acceptRejectChange(node, true);
    });

    editor.addCommand('ice_reject_change', () => {
        const node = editor.selection.getNode();
        acceptRejectChange(node, false);
    });

    editor.ui.registry.addButton('ice_toggle_changes', {
        text: 'Show/Hide Changes',
        onAction: () => editor.execCommand('ice_toggle_changes')
    });

    editor.ui.registry.addButton('ice_accept', {
        text: 'Accept',
        onAction: () => editor.execCommand('ice_accept_change')
    });

    editor.ui.registry.addButton('ice_reject', {
        text: 'Reject',
        onAction: () => editor.execCommand('ice_reject_change')
    });

    editor.on('BeforeInput', (e) => {
        if (e.inputType === 'insertText' && e.data) {
            e.preventDefault();
            const wrapped = wrapInsertion(e.data);
            editor.insertContent(wrapped);
        }
    });

    editor.on('KeyDown', (e) => {
        const key = e.key;
        const isBackspace = key === 'Backspace';
        const isDelete = key === 'Delete';

        if ((isBackspace || isDelete) && editor.selection.isCollapsed()) {
            const rng = editor.selection.getRng();
            const container = rng.startContainer;
            const offset = rng.startOffset;

            let textNode = container.nodeType === 3 ? container : null;

            if (textNode) {
                let deleteChar = '';
                let newText = '';
                let delOffset = offset;

                if (isBackspace && offset > 0) {
                    deleteChar = textNode.data.charAt(offset - 1);
                    newText = textNode.data.slice(0, offset - 1) + textNode.data.slice(offset);
                    delOffset--;
                } else if (isDelete && offset < textNode.data.length) {
                    deleteChar = textNode.data.charAt(offset);
                    newText = textNode.data.slice(0, offset) + textNode.data.slice(offset + 1);
                }

                if (deleteChar) {
                    e.preventDefault();
                    const delSpan = wrapDeletion(deleteChar);
                    const parent = textNode.parentNode;

                    const before = document.createTextNode(newText.slice(0, delOffset));
                    const after = document.createTextNode(newText.slice(delOffset));
                    const spanEl = document.createElement('span');
                    spanEl.innerHTML = delSpan;

                    parent.replaceChild(after, textNode);
                    parent.insertBefore(spanEl.firstChild, after);
                    if (before.data) {
                        parent.insertBefore(before, spanEl.firstChild);
                    }
                    const sel = editor.selection.getSel();
                    sel.collapse(after, 0);
                }
            }
        }
    });
});
