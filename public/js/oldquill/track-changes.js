// track-changes.js
// Minimal "track‑changes" Quill module.
// Adds change‑ids + author metadata into Delta attributes
// and exposes accept / reject helpers.

import Quill from 'quill';
import './change-format.js';

const Module = Quill.import('core/module');

/** RFC‑4122 v4 – tiny local helper so we avoid extra deps */
function uuid () {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
    const r = Math.random() * 16 | 0,
          v = c === 'x' ? r : (r & 0x3 | 0x8);
    return v.toString(16);
  });
}

export default class TrackChanges extends Module {
  constructor (quill, opts = {}) {
    super(quill, opts);
    this.authorId  = opts.authorId  ?? 'unknown';
    this.username  = opts.username  ?? 'Unknown';
    this.sessionId = opts.sessionId ?? uuid();
    this.changes   = new Map();                 // changeId → meta

    quill.on('text-change', (delta, _old, src) => {
      if (src !== 'user') return;
      this._record(delta);
    });
  }

  /* ---------- public API ---------- */
  accept (id) { this._apply(id, 'accept'); }
  reject (id) { this._apply(id, 'reject'); }

  getChanges (filter = () => true) {
    return [...this.changes.values()].filter(filter);
  }

  /* ---------- internals ---------- */
  _record (delta) {
    const cid  = uuid();
    const time = Date.now();

    // decorate ops
    const authored = delta.map(op => {
      if (op.insert) {
        op.attributes = { ...(op.attributes || {}), 
          'ice-change-id': cid,
          'ice-action'   : 'insert',
          'ice-author'   : this.authorId,
          'ice-session'  : this.sessionId,
          'ice-time'     : time
        };
      }
      if (op.delete) {
        op = {
          retain: op.delete,
          attributes: {
            'ice-change-id': cid,
            'ice-action'   : 'delete',
            'ice-author'   : this.authorId,
            'ice-session'  : this.sessionId,
            'ice-time'     : time,
            'ice-hidden'   : true     // hide via CSS until accepted/rejected
          }
        };
      }
      return op;
    });

    this.changes.set(cid, {
      id: cid,
      author : this.username,
      time,
      delta  : authored,
      accepted: null
    });

    this.quill.updateContents({ ops: authored }, 'silent');
    this.quill.emit('track-change:add', this.changes.get(cid));
  }

  _apply (cid, action) {
    const entry = this.changes.get(cid);
    if (!entry) return;

    const transformed = entry.delta.map(op => {
      const a = op.attributes || {};
      const ins = a['ice-action'] === 'insert';
      const del = a['ice-action'] === 'delete';

      if (ins) {
        if (action === 'accept')   return { insert: op.insert };
        if (action === 'reject')   return { delete: (op.insert || '').length };
      }
      if (del) {
        if (action === 'accept')   return { delete: op.retain };
        if (action === 'reject') {
          return { retain: op.retain, attributes: {
            'ice-hidden': null,
            'ice-change-id': null,
            'ice-action': null,
            'ice-author': null,
            'ice-time': null
          }};
        }
      }
      return op;
    });

    this.quill.updateContents({ ops: transformed }, 'silent');
    entry.accepted = action === 'accept';
    this.quill.emit(`track-change:${action}`, entry);
  }
}

Quill.register('modules/trackChanges', TrackChanges);