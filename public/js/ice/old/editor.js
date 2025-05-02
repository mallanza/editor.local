// public/js/ice/editor.js

tinymce.PluginManager.add('ice', function (editor) {
  var ice = new ICE({
    editor: editor,
    userId: 'user_' + Math.floor(Math.random() * 1000), // or pass Auth::id()
    userColor: '#c0392b' // customize per user
  });

  // Attach for debugging or manual access
  editor.ice = ice;
});
