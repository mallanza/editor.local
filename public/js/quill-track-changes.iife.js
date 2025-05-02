
/*!
 * Quill Track Changes IIFE (v7 patched)
 */
(function() {
  const Quill = window.Quill;
  if (!Quill) {
    console.error('[track-changes] Quill not found');
    return;
  }

  class TrackChanges {
    constructor(quill, options = {}) {
      this.quill = quill;
      this.options = options;
      this.user = { id: options.authorId || 'guest', name: options.username || 'Guest', styleClass: options.styleClass || '' };
      this.enabled = false;

      this.quill.on('text-change', (delta, oldDelta, source) => {
        if (!this.enabled || source !== 'user') return;

        const ops = delta.ops;
        if (!ops) return;

        let index = 0;
        for (const op of ops) {
          if (op.insert) {
            const length = op.insert.length || 1;
            const changeId = this.uuidv4();
            const now = Date.now();

            this.quill.formatText(index, length, {
              'ice-change-id': changeId,
              'ice-author': this.user.id,
              'ice-time': now
            }, 'user');
          }
          if (op.retain) {
            index += op.retain;
          }
          if (op.delete) {
            // not implemented in this patch
          }
        }
      });
    }

    setUserInfo(user) {
      this.user = user || this.user;
    }

    enableTracking(state = true) {
      this.enabled = state;
    }

    uuidv4() {
      return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0,
          v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
      });
    }
  }

  Quill.register('modules/trackChanges', TrackChanges);
})();
