class TrackChanges {
    constructor(quill, options = {}) {
      this.quill = quill;
      this.user = options.user || { id: 'unknown', name: 'Unknown' };
      this.enabled = false;

      const Inline = Quill.import('blots/inline');

      class InsertBlot extends Inline {
        static create(value) {
          const node = super.create();
          node.classList.add('qtrack-insert');
          node.setAttribute('data-userid', value.id);
          node.setAttribute('data-username', value.name);
          return node;
        }

        static formats(node) {
          return {
            id: node.getAttribute('data-userid'),
            name: node.getAttribute('data-username')
          };
        }
      }

      InsertBlot.blotName = 'qtrack-insert';
      InsertBlot.tagName = 'span';

      Quill.register(InsertBlot, true);

      // Apply tracking only to new inserts
      quill.on('text-change', (delta, oldDelta, source) => {
        if (!this.enabled || source !== 'user') return;

        let index = 0;
        delta.ops.forEach(op => {
          if (op.insert) {
            quill.formatText(index, op.insert.length, 'qtrack-insert', this.user, 'user');
            index += op.insert.length;
          } else if (op.retain) {
            index += op.retain;
          }
        });
      });
    }

    setUserInfo(user) {
      this.user = user;
    }

    enableTracking(flag = true) {
      this.enabled = flag;
    }
  }

  window.QuillTrackChanges = TrackChanges;
