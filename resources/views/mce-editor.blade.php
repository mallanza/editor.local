<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        /* Highlight annotated text with a yellow background */
        .my-comment-marker {
            background-color: rgba(255, 235, 59, 0.5);
            border-bottom: 1px dashed #fdd835;
            position: relative;
        }

        /* On hover, show the comment in a tooltip-like box */
        .my-comment-marker:hover::after {
            content: attr(data-annotation);
            /* the raw JSON blob */
            white-space: pre-wrap;
            position: absolute;
            left: 0;
            top: 1.5em;
            background: #fff;
            border: 1px solid #ccc;
            padding: 4px 8px;
            z-index: 10;
        }
    </style>


    <title>ICE + TinyMCE 7 with Images</title>

    {{-- 1) jQuery core (must come first) --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    {{-- 2) (Optional) jQuery Migrate if you need other old APIs --}}
    <script src="https://code.jquery.com/jquery-migrate-3.4.0.min.js"></script>

    {{-- 3) Polyfill jQuery.browser for ice.js if you still need it --}}
    <script>
        (function($) {
            if (!$.browser) {
                $.browser = {};
                const m = navigator.userAgent.match(/(Chrome|Firefox|MSIE|Edge|Safari|Opera)[\/ ]([\d\.]+)/i) || [];
                $.browser[m[1]?.toLowerCase()] = true;
                $.browser.version = m[2] || '0';
            }
        })(window.jQuery);
    </script>

    {{-- 4) Now load ICE (which relies on jQuery) --}}
    <script src="{{ asset('js/ice/ice.js') }}"></script>


    <script src="/js/tinymce/tinymce.min.js"></script>

    {{-- 6) Your ICE ↔ TinyMCE glue plugin --}}
    <script src="{{ asset('js/ice/plugin.js') }}"></script>
</head>

<body>
    <select id="userSelect">
        <option data-userid="11" data-username="Geoffrey Jellineck">Geoffrey</option>
        <option data-userid="22" data-username="Chuck Noblet">Chuck</option>
        <option data-userid="33" data-username="Jerri Blank">Jerri</option>
    </select>

    <textarea id="editor" style="height:500px;">{!! $document->html ?? '' !!}</textarea>

    <script>
        tinymce.init({
            selector: '#editor',
            license_key: 'gpl',
            branding: false,
            promotion: false,
            plugins: [
                'ice', 'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount',
            ],

            // Add both buttons to toolbar1
            toolbar1: 'styles | align | bullist numlist outdent indent | undo redo | ' +
                'bold italic underline | image | table | annotateComment showComments | ',
            toolbar2: 'ice_toggle_changes ice_accept ice_reject ice_accept_all ice_reject_all',






  content_style: `
    /* 1) Reset the default list styling and counter */
    ol {
      list-style: none;
      counter-reset: item;
      margin: 0;
      padding: 0;
    }

    /* 2) Make each LI a two-column grid: marker + content */
    ol li {
      counter-increment: item;
      display: grid;
      grid-template-columns: max-content 1fr;
      grid-gap: 0.5em;          /* space between marker and text */
      margin-bottom: 0.5em;     /* optional spacing between items */
    }

    /* 3) Draw the hierarchical counter in the first column */
    ol li::before {
      content: counters(item, ".") ".";
      white-space: nowrap;      /* never wrap the marker */
      justify-self: end;        /* right-align in its column */
    }

    /* 4) Force all children of LI (headings, paragraphs, nested lists) into the 2nd column */
    ol li > * {
      grid-column: 2;
    }
  `,



            setup(editor) {
                // ─── your existing ICE + annotateComment setup ─────────────────────────────────
                editor.on('init', () => {
                    const sel = document.getElementById('userSelect');
                    const applyUser = () => {
                        const o = sel.options[sel.selectedIndex];
                        editor.execCommand('ice_change_user', false, {
                            id: +o.dataset.userid,
                            name: o.dataset.username
                        });
                    };
                    sel.addEventListener('change', applyUser);
                    applyUser();

                    editor.annotator.register('comment', {
                        persistent: true,
                        decorate: (uid, data) => ({
                            attributes: {
                                'data-comment': data.text
                            }
                        })
                    });

                    editor.ui.registry.addButton('annotateComment', {
                        icon: 'comment',
                        tooltip: 'Add comment',
                        onAction: () => {
                            const text = prompt('Enter comment:');
                            if (text) {
                                editor.annotator.annotate('comment', {
                                    text
                                });
                                editor.focus();
                            }
                        }
                    });
                });

                // ─── Comments sidebar registration ──────────────────────────────────────────
                let commentsApi;
                editor.ui.registry.addSidebar('comments', {
                    tooltip: 'Comments',
                    icon: 'comment',
                    onSetup: (api) => {
                        commentsApi = api;
                        return () => {}; // teardown if needed
                    },
                    onShow: (api) => {
                        renderComments(api);
                    },
                    onHide: (api) => {
                        // optional cleanup
                    }
                }); // :contentReference[oaicite:0]{index=0}

                // Button to toggle that sidebar
                editor.ui.registry.addButton('showComments', {
                    icon: 'comment',
                    tooltip: 'Toggle comments sidebar',
                    onAction: () => {
                        editor.execCommand('togglesidebar', false, 'comments');
                    }
                }); // :contentReference[oaicite:1]{index=1}

                // ─── helper to fill the sidebar with current comments ────────────────────────
                function renderComments(api) {
                    const container = api.element();
                    container.innerHTML = '';

                    // getAll returns { uid1: [el1, el2…], uid2: [el3…], … }
                    const all = editor.annotator.getAll('comment'); // :contentReference[oaicite:0]{index=0}

                    Object.entries(all).forEach(([uid, elements], idx) => {
                        // take the first span for each annotation
                        const marker = elements[0]; // :contentReference[oaicite:1]{index=1}
                        const selectedText = marker.textContent;
                        const commentText = marker.getAttribute('data-comment');

                        const item = document.createElement('div');
                        item.className = 'sidebar-comment';
                        item.style.padding = '4px';
                        item.style.cursor = 'pointer';
                        item.innerHTML = `
      <p><strong>Comment ${idx + 1}:</strong>
      <div>${commentText}</div>
      <em>on “${selectedText}”</em></p>
    `;

                        item.addEventListener('click', () => {
                            editor.selection.select(marker);
                            editor.focus();
                        });

                        container.appendChild(item);
                    });
                }


            }
        });
    </script>
</body>

</html>
