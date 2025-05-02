<!DOCTYPE html>
<html>
    <head>
        <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
        @vite(['resources/js/app.js'])   {{-- Vite injects a <script type="module"> --}}
      </head>

      <body data-user-id="{{ Auth::id() ?? 'guest' }}"
            data-user-name="{{ Auth::user()->name ?? 'Guest' }}">
        <div id="editor-container" style="height:400px"></div>
      </body>

    <script>
        CKEDITOR.replace('editor1', {
            extraPlugins: 'lite',
            removePlugins: 'a11ychecker,About',
            height: 400,
            disableNativeSpellChecker: false,
            updateNotification: false,
            versionCheck: false,
            on: {
                instanceReady: function(evt) {
                    const editor = evt.editor;

                    console.log('✅ CKEditor instance ready');

                    editor.on('LITE.Events.INIT', function(event) {
                        console.log('✅ LITE plugin initialized');

                        const lite = event.data.lite;

                        console.log('Lite instance:', lite);
                        console.log('setUserInfo:', typeof lite.setUserInfo);

                        lite.setUserInfo({
                            id: 'user1', // Replace with Laravel variable if desired
                            name: 'John Doe'
                        });

                        lite.toggleTracking(true);
                    });
                }
            }
        });

        </script>

</body>
</html>
