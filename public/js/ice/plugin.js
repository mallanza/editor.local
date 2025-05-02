// public/js/ice/plugin.js

tinymce.PluginManager.add('ice', function(editor) {
    let iceEditor;
    let pendingUser = null;
    let showChanges = false;

    // 1) Queue up any set-user calls until ICE is ready
    editor.addCommand('ice_change_user', (ui, user) => {
      if (iceEditor) {
        iceEditor.setCurrentUser(user);
      } else {
        pendingUser = user;
      }
    });

    // 2) Function to inject the Show/Hide CSS
    function updateChangeCss() {
      const css = `
        .del {
          display: ${showChanges ? 'inline' : 'none'} !important;
          background-color: #fdd !important;
          color: #900 !important;
          text-decoration: line-through !important;
        }
        .ins {
          background-color: ${showChanges ? '#d0f0d0' : 'transparent'} !important;
        }
      `;
      const doc = editor.getDoc();
      let style = doc.getElementById('ice-toggle-style');
      if (!style) {
        style = doc.createElement('style');
        style.id = 'ice-toggle-style';
        doc.head.appendChild(style);
      }
      style.innerHTML = css;
    }

    editor.on('init', () => {
      const body = editor.getBody();
      const sel  = document.getElementById('userSelect');

      // Helper: read current user from your <select>
      function getUser() {
        const o = sel.options[sel.selectedIndex];
        return { id: +o.dataset.userid, name: o.dataset.username };
      }

      // Determine initial user
      const initialUser = pendingUser || getUser();

      // 3) Instantiate ICE
      iceEditor = new ice.InlineChangeEditor({
        element:         body,
        handleEvents:    true,  // let ICE catch backspace/delete, even ranges
        contentEditable: true,
        preserveOnPaste: 'p,a[href],i,em,b,span,img[src|alt|width|height]',
        deleteTag:       'delete',
        insertTag:       'insert',
        user:            initialUser
      });
      iceEditor.startTracking();

      // Apply queued user if any
      if (pendingUser) {
        iceEditor.setCurrentUser(pendingUser);
        pendingUser = null;
      }

      // 4) Wire up your <select> → ICE.setCurrentUser
      sel.addEventListener('change', () => {
        editor.execCommand('ice_change_user', false, getUser());
      });

      // 5) Initial CSS injection (hide changes by default)
      updateChangeCss();

      // 6) Hover tooltips
      body.addEventListener('mouseover', (e) => {
        const span = e.target.closest('span.ins, span.del');
        if (!span) return;
        const user = span.dataset.username || 'Unknown';
        const ts   = span.dataset.time;
        const when = ts ? new Date(+ts).toLocaleString() : '';
        const act  = span.classList.contains('ins') ? 'Inserted' : 'Deleted';
        span.title = `${act} by ${user}${when ? ' — ' + when : ''}`;
      });

      // 7) Register the Show/Hide toggle
      editor.ui.registry.addToggleButton('ice_toggle_changes', {
        text: 'Show Changes',
        onSetup: (api) => {
          api.setActive(showChanges);
          api.setText(showChanges ? 'Hide Changes' : 'Show Changes');
          return () => {};
        },
        onAction: (api) => {
          showChanges = !showChanges;
          updateChangeCss();
          api.setActive(showChanges);
          api.setText(showChanges ? 'Hide Changes' : 'Show Changes');
        }
      });

      // 8) Accept / Reject buttons
      editor.ui.registry.addButton('ice_accept', {
        text: 'Accept',
        onAction: () => {
          const n = iceEditor.getCurrentRangeStartNode();
          iceEditor.acceptChange(n);
        }
      });
      editor.ui.registry.addButton('ice_reject', {
        text: 'Reject',
        onAction: () => {
          const n = iceEditor.getCurrentRangeStartNode();
          iceEditor.rejectChange(n);
        }
      });
      editor.ui.registry.addButton('ice_accept_all', {
        text: 'Accept All',
        onAction: () => iceEditor.acceptAll()
      });
      editor.ui.registry.addButton('ice_reject_all', {
        text: 'Reject All',
        onAction: () => iceEditor.rejectAll()
      });
    });
  });
