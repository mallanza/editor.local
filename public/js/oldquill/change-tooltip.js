// change-tooltip.js
import Quill from 'quill';

const Tooltip = Quill.import('ui/tooltip');

export default class ChangeTooltip extends Tooltip {
  constructor (quill, opts = {}) {
    super(quill, opts);
    this.root.classList.add('change-tooltip');
    quill.on('selection-change', range => {
      if (!range) return this.hide();
      const [blot] = quill.getLine(range.index);
      const meta = blot.formats();
      if (!meta['ice-change-id']) return this.hide();
      this.show(meta);
    });
  }

  show (meta) {
    this.root.innerHTML = `
      <b>${meta['ice-author'] ?? 'Someone'}</b><br/>
      ${meta['ice-action'] === 'delete' ? 'Deleted' : 'Inserted'}
      <br/><small>${new Date(+meta['ice-time']).toLocaleString()}</small>
    `;
    super.show();
  }
}

Quill.register('modules/changeTooltip', ChangeTooltip);