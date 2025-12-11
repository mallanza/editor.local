
        const MAX_QUILL_INIT_ATTEMPTS = 80;
        const TABLE_BETTER_READY_EVENT = 'quill-table-better:ready';
        const TABLE_BETTER_WAIT_MS = 5000;
        const initQuillLite = (attempt = 0) => {
            if (!window.Quill) {
                console.error('Quill is not available. Did you load the assets?');
                return;
            }
            const nextAttempt = attempt + 1;
            if (!window.QuillLiteChangeTracker) {
                if (nextAttempt < MAX_QUILL_INIT_ATTEMPTS) {
                    window.setTimeout(() => initQuillLite(nextAttempt), 100);
                } else {
                    console.error('QuillLiteChangeTracker is not available. Did Vite compile?');
                }
                return;
            }

            const betterTableReady = Boolean(window.__quillTableBetterReady && window.QuillBetterTable);
            const skipBetterTableWait = Boolean(window.__quillTableBetterSkipWait);
            if (!betterTableReady && !skipBetterTableWait) {
                if (!window.__quillTableBetterAwaitingInit) {
                    window.__quillTableBetterAwaitingInit = true;
                    const resumeInit = () => {
                        window.removeEventListener(TABLE_BETTER_READY_EVENT, resumeInit);
                        if (window.__quillTableBetterWaitTimer) {
                            clearTimeout(window.__quillTableBetterWaitTimer);
                            window.__quillTableBetterWaitTimer = null;
                        }
                        window.__quillTableBetterAwaitingInit = false;
                        initQuillLite();
                    };
                    window.addEventListener(TABLE_BETTER_READY_EVENT, resumeInit, { once: true });
                    window.__quillTableBetterWaitTimer = window.setTimeout(() => {
                        window.removeEventListener(TABLE_BETTER_READY_EVENT, resumeInit);
                        window.__quillTableBetterAwaitingInit = false;
                        window.__quillTableBetterSkipWait = true;
                        window.__quillTableBetterWaitTimer = null;
                        console.warn('quill-table-better took too long to load. Continuing without table tools.');
                        initQuillLite();
                    }, TABLE_BETTER_WAIT_MS);
                }
                return;
            }
            if (!betterTableReady) {
                console.warn('quill-table-better is not ready. Continuing without table tools.');
            } else if (window.__quillTableBetterWaitTimer) {
                clearTimeout(window.__quillTableBetterWaitTimer);
                window.__quillTableBetterWaitTimer = null;
                window.__quillTableBetterAwaitingInit = false;
            }

            const initialContent = null;
            const initialDelta = null;
            const initialChanges = null;
            const initialComments = null;
            const initialHtml = null;
            const saveEndpoint = null;
            const deleteEndpoint = null;
            const currentUser = null;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const quillModules = {
                toolbar: '#quill2-toolbar',
                history: {
                    delay: 400,
                    maxStack: 100,
                    userOnly: false,
                },
            };
            if (betterTableReady) {
                quillModules.table = false;
                quillModules['table-better'] = {
                    language: 'en_US',
                    toolbarTable: true,
                    menus: ['column', 'row', 'merge', 'table', 'cell', 'wrap', 'copy', 'delete'],
                };
                quillModules.keyboard = {
                    bindings: window.QuillBetterTableBindings
                        || window.QuillBetterTable?.keyboardBindings
                        || {},
                };
            }
            const quill = new window.Quill('#quill2-editor', {
                theme: 'snow',
                modules: quillModules,
            });
            const userSource = window.Quill.sources.USER;
            const silentSource = window.Quill.sources.SILENT;
            const editorScrollContainer = quill.root?.parentElement ?? quill.container ?? null;

            const activityFeedEl = document.getElementById('quill2-activity-feed');
            const activitySummaryEl = document.getElementById('quill2-activity-summary');
            const historyFeedEl = document.getElementById('quill2-history-feed');
            const historySection = document.getElementById('quill2-history-section');
            const historyCountEl = document.getElementById('quill2-history-count');
            const hiddenContainer = document.getElementById('quill2-hidden-tracking');
            const downloadButton = document.getElementById('quill2-download');
            const redlineBtn = document.getElementById('quill2-view-redline');
            const cleanBtn = document.getElementById('quill2-view-clean');
            const quillContainer = document.getElementById('quill2-editor');
            const saveButton = document.getElementById('quill2-save');
            const deleteButton = document.getElementById('quill2-delete');
            const statusEl = document.getElementById('quill2-status');
            const addCommentButton = document.getElementById('quill2-add-comment');
            const commentModal = document.getElementById('quill2-comment-modal');
            const commentForm = document.getElementById('quill2-comment-form');
            const commentBodyInput = document.getElementById('quill2-comment-body');
            const commentSnippetEl = document.getElementById('quill2-comment-snippet');
            const commentCancelButton = document.getElementById('quill2-comment-cancel');
            const commentCloseButton = document.getElementById('quill2-comment-close');
            const togglePanelButton = document.getElementById('quill2-toggle-panel');
            const sidePanel = document.getElementById('quill2-side-panel');
            const panelResizer = document.getElementById('quill2-panel-resizer');
            const acceptChangeButton = document.getElementById('quill2-accept-current');
            const rejectChangeButton = document.getElementById('quill2-reject-current');
            const acceptAllButton = document.getElementById('quill2-accept-all');
            const rejectAllButton = document.getElementById('quill2-reject-all');

            const encodeContent = (value = '') => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

            const buildCommentId = () => {
                if (window.crypto?.randomUUID) {
                    return `cmt-${window.crypto.randomUUID()}`;
                }
                return `cmt-${Date.now()}-${Math.floor(Math.random() * 1e4)}`;
            };

            const normalizeComment = (comment, index = 0) => {
                if (!comment || typeof comment !== 'object') {
                    return null;
                }
                const rangeIndex = Number(comment.range?.index ?? 0);
                const rangeLength = Number(comment.range?.length ?? 0);
                const normalizedAuthor = typeof comment.author === 'string'
                    ? {
                        id: comment.authorId ?? 'unknown-author',
                        name: comment.author,
                    }
                    : {
                        id: comment.author?.id ?? comment.authorId ?? 'unknown-author',
                        name: comment.author?.name ?? comment.authorName ?? 'Unknown',
                    };
                return {
                    id: comment.id ?? buildCommentId() ?? `cmt-import-${index + 1}`,
                    text: comment.text ?? '',
                    snippet: comment.snippet ?? '',
                    range: {
                        index: Number.isFinite(rangeIndex) ? rangeIndex : 0,
                        length: Number.isFinite(rangeLength) ? Math.max(0, rangeLength) : 0,
                    },
                    createdAt: comment.createdAt ?? new Date().toISOString(),
                    author: normalizedAuthor,
                };
            };

            const commentLog = Array.isArray(initialComments)
                ? initialComments.map((comment, index) => normalizeComment(comment, index)).filter(Boolean)
                : [];

            const readClipboardPlainText = async () => {
                if (!navigator.clipboard?.readText) {
                    return '';
                }
                try {
                    return await navigator.clipboard.readText();
                } catch (error) {
                    return '';
                }
            };

            const getClipboardPayload = (clipboardEvent) => {
                const payload = { html: '', text: '' };
                const clipboard = clipboardEvent?.clipboardData || window.clipboardData;
                if (!clipboard) {
                    return payload;
                }
                try {
                    payload.html = clipboard.getData('text/html') || '';
                } catch (error) {
                    payload.html = '';
                }
                try {
                    payload.text = clipboard.getData('text/plain') || '';
                } catch (error) {
                    payload.text = '';
                }
                return payload;
            };

            const resolveClipboardPayload = async (clipboardEvent) => {
                const direct = getClipboardPayload(clipboardEvent);
                if (direct.html || direct.text) {
                    return direct;
                }
