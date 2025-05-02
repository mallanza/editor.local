// change-format.js
import Quill from 'quill';

const Inline = Quill.import('blots/inline');

/**
 * <ins> wrapper for inserted text
 */
class InsertBlot extends Inline {}
InsertBlot.blotName  = 'ice-ins';
InsertBlot.className = 'lite-insert';
InsertBlot.tagName   = 'ins';

/**
 * <del> wrapper for deleted text
 */
class DeleteBlot extends Inline {}
DeleteBlot.blotName  = 'ice-del';
DeleteBlot.className = 'lite-delete';
DeleteBlot.tagName   = 'del';

Quill.register({
  'formats/ice-ins': InsertBlot,
  'formats/ice-del': DeleteBlot
});