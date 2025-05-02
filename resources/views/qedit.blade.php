{-- resources/views/editor.blade.php --}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Quill Track Changes (Multi-user)</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css">
  <script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
  <script src="/js/quill-change-format.iife.js"></script>
  <script src="/js/quill-track-changes.iife.js"></script>
  <script src="/js/quill-change-tooltip.iife.js"></script>

  <style>
    #editor-container { height: 400px; }
    .lite-insert { background: #e8f5e9; }
    .lite-delete { background: #ffebee; text-decoration: line-through; }
    .lite-delete[ice-hidden] { display: none; }
    .ql-editor.changes-hidden .lite-insert,
    .ql-editor.changes-hidden .lite-delete {
      background: none;
      text-decoration: none;
    }
    .ql-editor.changes-hidden .lite-delete,
    .ql-editor.changes-hidden .lite-delete[ice-hidden] { display: none; }
    .ql-showHideChanges { white-space: nowrap; }
  </style>
</head>
<body>

<h2>Quill Editor ({ Auth::user()->name ?? 'guest' })</h2>

<form method="POST" action="{ route('documents.store') }">
  @csrf
  <input type="text" name="title" placeholder="Title" required>
  <input type="hidden" name="content" id="doc-content">
  <button type="submit">Save</button>
</form>

<form method="POST" action="{ route('documents.update') }">
  @csrf
  <input type="hidden" name="doc_id" value="{ request('doc_id') }">
  <input type="text" name="title" value="{ old('title', $selectedDocument->title ?? '') }" required>
  <input type="hidden" name="content" id="doc-content-update">
  <button type="submit">Update</button>
</form>

<div id="editor-container"></div>

<script>
  const toolbarOptions = {
    container: [
      [{ header: [1, 2, 3, false] }],
      ['bold', 'italic', 'underline'],
      ['link', 'blockquote', 'code-block'],
      [{ list: 'ordered' }, { list: 'bullet' }],
      ['clean'],
      ['showHideChanges']
    ],
    handlers: {
      showHideChanges: function () {
        const root = this.quill.root;
        const btn = this.container.querySelector('.ql-showHideChanges');
        const hidden = root.classList.toggle('changes-hidden');
        btn.classList.toggle('ql-active', hidden);
        btn.textContent = hidden ? 'Show changes' : 'Hide changes';
      }
    }
  };

  const quill = new Quill('#editor-container', {
    theme: 'snow',
    modules: {
      toolbar: toolbarOptions,
      trackChanges: {
        authorId: <?= json_encode(Auth::id() ?? "guest") ?>,
        username: <?= json_encode(Auth::user()->name ?? "guest") ?>,
        styleClass: 'user-' + <?= json_encode(Auth::id() ?? "guest") ?>
      },
      changeTooltip: true
    }
  });

  const tracker = quill.getModule('trackChanges');

  if (typeof tracker.setUserInfo === 'function') {
    tracker.setUserInfo({ id: <?= json_encode(Auth::id() ?? "guest") ?>, name: <?= json_encode(Auth::user()->name ?? "guest") ?>, styleClass: 'user-' + <?= json_encode(Auth::id() ?? "guest") ?> });
  } else if (typeof tracker.setAuthor === 'function') {
    tracker.setAuthor({ id: <?= json_encode(Auth::id() ?? "guest") ?>, name: <?= json_encode(Auth::user()->name ?? "guest") ?>, styleClass: 'user-' + <?= json_encode(Auth::id() ?? "guest") ?> });
  }

  quill.root.classList.add('user-' + <?= json_encode(Auth::id() ?? "guest") ?>);

  document.querySelector('form[action="{{ route('documents.store') }}"]').addEventListener('submit', function () {
    document.getElementById('doc-content').value = JSON.stringify({ html: quill.root.innerHTML });
  });

  const updateForm = document.querySelector('form[action="{{ route('documents.update') }}"]');
if (updateForm) {
  updateForm.addEventListener('submit', function () {
    document.getElementById('doc-content-update').value = JSON.stringify({ html: quill.root.innerHTML });
  });
}

  @if (!empty($selectedContent))
  document.addEventListener('DOMContentLoaded', function () {
  try {
    const saved = JSON.parse(String(@json($selectedContent)));
    if (saved.html) {
      quill.clipboard.dangerouslyPasteHTML(saved.html, 'silent');
    }
  } catch (e) {
    console.warn('Failed to load saved content:', e);
  }
});
  @endif
</script>
</body>
</html>
