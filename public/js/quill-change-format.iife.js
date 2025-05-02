/* quill-change-format.iife.js  (with attribute attributors) */
(function (w) {
  if (!w.Quill) { console.error('Quill missing'); return; }
  var Inline  = w.Quill.import('blots/inline');
  var Block   = w.Quill.import('blots/block');
  var Attr    = w.Quill.import('parchment').Attributor.Attribute;

  function registerBlot(name, className, tag) {
    class B extends Inline {}
    B.blotName  = name;
    B.className = className;
    B.tagName   = tag;
    var obj = {};  obj['formats/' + name] = B;
    w.Quill.register(obj);
  }

  // Inline INS / DEL
  registerBlot('ice-ins', 'lite-insert', 'INS');
  registerBlot('ice-del', 'lite-delete', 'DEL');

  // Also allow the *block* versions (so Quill can mark the <p>)
  registerBlot('ice-ins-block', 'lite-insert', Block.tagName);
  registerBlot('ice-del-block', 'lite-delete', Block.tagName);

  // ----- keep metadata attributes -----
  [
    'ice-author',
    'ice-time',
    'ice-change-id',
    'ice-hidden'
  ].forEach(function (name) {
    w.Quill.register(new Attr(name, name, { scope: w.Quill.import('parchment').Scope.INLINE | w.Quill.import('parchment').Scope.BLOCK }));
  });
})(window);
