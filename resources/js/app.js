import './bootstrap';

import Alpine from 'alpinejs';
import Quill from 'quill';
import QuillTableBetter from 'quill-table-better';
import 'quill-table-better/dist/quill-table-better.css';

window.Alpine = Alpine;
if (typeof window !== 'undefined') {
	window.Quill = window.Quill || Quill;
}

const broadcastQuillTableBetterReady = () => {
	if (!window.__quillTableBetterReady || !window.QuillBetterTable) {
		return;
	}
	const detail = {
		module: window.QuillBetterTable,
		bindings: window.QuillBetterTableBindings
			|| window.QuillBetterTable?.keyboardBindings
			|| {},
	};
	window.dispatchEvent(new CustomEvent('quill-table-better:ready', { detail }));
};

const registerQuillTableBetter = () => {
	if (typeof window === 'undefined') {
		return;
	}
	const maxAttempts = 120;
	const attemptRegister = (attempt = 0) => {
		if (window.__quillTableBetterReady) {
			broadcastQuillTableBetterReady();
			return;
		}
		if (!window.Quill) {
			if (attempt < maxAttempts) {
				window.setTimeout(() => attemptRegister(attempt + 1), 100);
			} else {
				console.error('Quill was never found while registering quill-table-better.');
			}
			return;
		}
		try {
			window.Quill.register({
				'modules/table-better': QuillTableBetter,
			}, true);
			window.__quillTableBetterReady = true;
			window.QuillBetterTable = QuillTableBetter;
			window.QuillBetterTableBindings = QuillTableBetter?.keyboardBindings ?? null;
			broadcastQuillTableBetterReady();
		} catch (error) {
			console.error('Failed to register quill-table-better', error);
		}
	};
	attemptRegister();
};

registerQuillTableBetter();

Alpine.start();
