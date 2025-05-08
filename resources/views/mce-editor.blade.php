<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ICE + TinyMCE 7 with TinyComments & Image Upload</title>

    <!-- TinyComments CSS -->
    <link rel="stylesheet" href="js/tiny-plugins/tinycomments/css/tinycomments.css">

    <!-- Optional ICE change highlight style -->
    <style>
        .ice-inserted {
            background-color: #e0f7fa;
            border-bottom: 1px dotted #00796b;
        }
    </style>


    <!-- jQuery & ICE core JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/jquery-migrate-3.4.0.min.js"></script>
    <script>
        // Polyfill for $.browser used by ICE
        (function($) {
            if (!$.browser) {
                $.browser = {};
                const m = navigator.userAgent.match(/(Chrome|Firefox|MSIE|Edge|Safari|Opera)[\/ ]([\d\.]+)/i) || [];
                if (m[1]) $.browser[m[1].toLowerCase()] = true;
                $.browser.version = m[2] || '0';
            }
        })(window.jQuery);
    </script>
    <script src="{{ asset('js/tiny-plugins/ice/ice.js') }}"></script>


</head>

<body>
    <!-- User selector for ICE change-tracking -->
    <select id="userSelect">
        <option data-userid="11" data-username="Geoffrey Jellineck">Geoffrey</option>
        <option data-userid="22" data-username="Chuck Noblet">Chuck</option>
        <option data-userid="33" data-username="Jerri Blank">Jerri</option>
    </select>

    <!-- Editor container -->
    <textarea id="editor" style="width:100%; height:500px;">{!! $document->html ?? '' !!}</textarea>

    <!-- TinyMCE & ICE plugin -->
    <script src="/js/tinymce/tinymce.min.js"></script>



    <script>
        function myAiRequestFunction(request, respond) {
            // e.g. do an AJAX/fetch to your AI endpoint…
            fetch('/api/ai', {
                    method: 'POST',
                    body: JSON.stringify({
                        prompt: request.prompt
                    }),
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(data => respond(data));
        }


        tinymce.init({

            external_plugins: {
                'tinycomments': '{{ asset('js/tiny-plugins/tinymcecomments/plugin.min.js') }}',
                'glossary': '{{ asset('js/tiny-plugins/glossary/plugin.min.js') }}',
                'tableofcontents': '{{ asset('js/tiny-plugins/tinymcetableofcontents/plugin.min.js') }}',
                'ice': '{{ asset('js/tiny-plugins/ice/plugin.js') }}',
                'tinyai': '{{ asset('js/tiny-plugins/tinytinymceai/plugin.min.js') }}',
                'template': '{{ asset('js/tiny-plugins/tinymceadvtemplate/plugin.min.js') }}',
            },

            selector: '#editor',


            license_key: 'gpl',
            branding: false,
            promotion: false,
            // AI plugin settings remain the same
            ai_request: myAiRequestFunction,
            ai_shortcuts: true,
            contextmenu: 'template',
            plugins: [
                'ice', 'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
                'preview', 'anchor', 'searchreplace', 'visualblocks', 'code',
                'fullscreen', 'insertdatetime', 'media', 'table',
                'wordcount', 'tinycomments', 'myai', 'tableofcontents', 'template', 'glossary', 'autosave',
                'save'
            ],
            toolbar1: "save undo redo |fontfamily blocks  fontsize bold italic underline strikethrough | forecolor backcolor | align outdent indent bullist numlist | formatpainter removeformat charmap | link image table | addcomment showcomments | addtemplate inserttemplate template glossary fullscreen",
            toolbar2: "ice_enable_track_changes ice_toggle_changes ice_accept ice_reject ice_accept_all ice_reject_all | aidialog aishortcuts",

            automatic_uploads: true,
            images_upload_url: '/image/upload',
            file_picker_types: 'image',
            tinycomments_can_resolve: (req, done, fail) => {
                const allowed = req.comments.length > 0 &&
                    req.comments[0].author === currentAuthor;
                done({
                    canResolve: allowed || currentAuthor === '<administration>'
                });
            },
            file_picker_callback: function(callback, value, meta) {
                if (meta.filetype === 'image') {
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.accept = 'image/*';
                    input.onchange = () => {
                        const file = input.files[0];
                        const data = new FormData();
                        data.append('file', file);
                        fetch('/image/upload', {
                                method: 'POST',
                                body: data,
                                credentials: 'include',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                        .content
                                }
                            })
                            .then(res => res.json())
                            .then(json => callback(json.location))
                            .catch(err => alert('Image upload failed: ' + err.message));
                    };
                    input.click();
                }
            },
            tinycomments_mode: 'embedded',
            tinycomments_author: 'Your Name',
            tinycomments_css: 'js/tiny-plugins/tinycomments/css/tinycomments.css',
            content_style: [
                'ol { list-style: none; counter-reset: item; margin:0; padding:0; }',
                'ol li { counter-increment: item; display:grid; grid-template-columns: max-content 1fr; grid-gap:0.5em; margin-bottom:0.5em; }',
                'ol li::before { content: counters(item, "\\.") "\\."; white-space:nowrap; justify-self:end; }',
                'ol li > * { grid-column:2; }'
            ].join(' '),
            setup(editor) {
                // ICE: track-change user selection
                editor.on('init', () => {
                    const sel = document.getElementById('userSelect');
                    const applyUser = () => {
                        const opt = sel.options[sel.selectedIndex];
                        editor.execCommand('ice_change_user', false, {
                            id: Number(opt.dataset.userid),
                            name: opt.dataset.username
                        });
                    };
                    sel.addEventListener('change', applyUser);
                    applyUser();
                });
                // Log ICE change events
                editor.on('IceChange', e => console.log('ICE Change:', e));

                // Override showcomments to list all comment threads
                /*         editor.ui.registry.addButton('showcomments', {
                          icon: 'comment',
                          tooltip: 'Show All Comments',
                          onAction: () => editor.execCommand('ToggleSidebar', false, 'comments')
                        }); */
            }
        });
    </script>
</body>

</html>
