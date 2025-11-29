@extends('layouts.app')

@section('content')
    <div id="quill2-root" class="max-w-5xl mx-auto py-8 space-y-6">
        <header class="rounded-[2rem] border border-white/40 bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-800 p-1 shadow-xl">
            <div class="rounded-[calc(2rem-0.5rem)] bg-slate-900/70 p-6 text-slate-100">
                <p class="text-xs font-semibold uppercase tracking-[0.4em] text-indigo-300">Quill Lite</p>
                <div class="mt-2 flex flex-wrap items-end gap-3">
                    <h1 class="text-3xl font-semibold leading-tight text-white">Inline Tracked Changes</h1>
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-indigo-200">No Persistence</span>
                </div>
                <p class="mt-3 text-sm text-slate-200/80">
                    Explore tracked edits, comments, and smart typography entirely on the client. This demo keeps everything in memory so you can embed it anywhere without touching your backend.
                </p>
            </div>
        </header>

        <section class="rounded-[2rem] border border-white/60 bg-white/80 shadow-xl backdrop-blur">
            <div class="flex flex-col divide-y divide-slate-100">
                <div class="flex flex-col gap-4 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                        <div>
                            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.4em] text-slate-400">View mode</p>
                        </div>
                        <div class="flex items-center rounded-full bg-slate-100 p-1 text-xs font-semibold uppercase tracking-widest text-slate-500">
                            <button
                                type="button"
                                id="quill2-view-redline"
                                aria-pressed="true"
                                class="inline-flex items-center rounded-full px-4 py-1.5 text-xs font-semibold transition bg-white text-slate-900 shadow -translate-y-[1px]"
                            >
                                Redline
                            </button>
                            <button
                                type="button"
                                id="quill2-view-clean"
                                aria-pressed="false"
                                class="inline-flex items-center rounded-full px-4 py-1.5 text-xs font-semibold transition bg-transparent text-slate-500"
                            >
                                Clean Draft
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            id="quill2-toggle-panel"
                            class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white shadow-sm transition-colors hover:bg-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-500"
                        >
                            <svg class="h-3 w-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h8M2 6h8M2 9h8" /></svg>
                            Hide Comments
                        </button>
                    </div>
                </div>
                <div id="quill2-shell" class="flex min-h-[400px] w-full flex-row items-stretch">
                    <div id="quill2-editor-pane" class="flex flex-1 flex-col gap-4 border-r border-slate-100 bg-white p-6">
                        <div class="rounded-3xl border border-slate-100 bg-white shadow-sm">
                            <div id="quill2-toolbar" class="ql-toolbar ql-snow rounded-t-3xl border-b border-slate-100 px-4 py-2">
                            <span class="ql-formats">
                                <select class="ql-header">
                                    <option selected></option>
                                    <option value="1">H1</option>
                                    <option value="2">H2</option>
                                </select>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-bold" type="button"></button>
                                <button class="ql-italic" type="button"></button>
                                <button class="ql-underline" type="button"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-list" value="ordered" type="button"></button>
                                <button class="ql-list" value="bullet" type="button"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-clean" type="button"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-table-better" aria-label="Insert table" type="button"></button>
                            </span>
                            <span class="ql-formats">
                                <button
                                    type="button"
                                    id="quill2-add-comment"
                                    class="relative inline-flex items-center justify-center rounded-full border border-slate-200 bg-white/60 px-3 py-2 text-slate-900 shadow-sm transition hover:text-sky-500 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-400 disabled:pointer-events-none"
                                    title="Add comment"
                                    disabled
                                >
                                    <svg class="h-4 w-4 transition-colors" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6">
                                        <path d="M6 9h8M10 5v8" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M10 1.5C5.31 1.5 1.5 4.86 1.5 9c0 1.69.56 3.21 1.5 4.46V18l3.77-1.88c1.01.24 2.08.38 3.23.38 4.69 0 8.5-3.36 8.5-7.5S14.69 1.5 10 1.5Z" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </span>
                            </div>
                            <div id="quill2-editor" class="ql-container ql-snow min-h-[360px] rounded-b-3xl px-4 py-4"></div>
                        </div>
                    </div>
                    <div id="quill2-panel-resizer" class="hidden w-px shrink-0 bg-slate-100 md:block md:self-stretch" aria-hidden="true"></div>
                    <aside
                        id="quill2-side-panel"
                        class="flex-none border-l border-slate-100 bg-gradient-to-b from-white to-slate-50/60 p-6 transition-[width] duration-150"
                        style="width: 320px;"
                    >
                        <div class="flex flex-col gap-6">
                            <section aria-labelledby="quill2-activity-heading" class="space-y-4 rounded-3xl border border-white/70 bg-white/90 p-4 shadow-sm">
                                <div class="flex items-center justify-between">
                                    <h3 id="quill2-activity-heading" class="text-sm font-semibold text-slate-800">Activity</h3>
                                    <p id="quill2-activity-summary" class="text-xs font-medium text-slate-500">0 items</p>
                                </div>
                                <ul
                                    id="quill2-activity-feed"
                                    class="max-h-[32rem] space-y-3 overflow-y-auto pr-1 text-sm text-slate-700"
                                    aria-live="polite"
                                ></ul>
                                <details id="quill2-history-section" class="group rounded-2xl border border-slate-100 bg-white/80 p-3" aria-live="polite">
                                    <summary class="flex cursor-pointer items-center justify-between text-xs font-semibold text-slate-600">
                                        <span>History</span>
                                        <span class="flex items-center gap-2 text-[0.65rem] font-medium uppercase tracking-wide text-slate-500">
                                            <span id="quill2-history-count">0</span>
                                            <svg class="h-4 w-4 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path d="M6 8l4 4 4-4" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                    </summary>
                                    <ul
                                        id="quill2-history-feed"
                                        class="mt-3 space-y-2 border-t border-dashed border-slate-200 pt-3 text-sm text-slate-600"
                                    ></ul>
                                </details>
                            </section>
                        </div>
                    </aside>
                </div>
            </div>
        </section>

        <div class="mt-8 rounded-[1.5rem] border border-slate-100 bg-white/90 px-6 py-5 shadow-lg md:flex md:items-center md:justify-between">
            <div class="space-y-1">
                <h2 class="text-sm font-semibold text-slate-800">Document actions</h2>
                <p id="quill2-status" class="text-xs text-slate-500">Not saved</p>
            </div>
            <div class="mt-3 flex flex-wrap gap-3 md:mt-0">
                <button
                    type="button"
                    id="quill2-save"
                    class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white shadow-sm transition hover:bg-emerald-500"
                >
                    <span class="h-2 w-2 rounded-full bg-white"></span>
                    Save
                </button>
                <button
                    type="button"
                    id="quill2-delete"
                    class="inline-flex items-center gap-2 rounded-2xl bg-rose-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white shadow-sm transition hover:bg-rose-500"
                >
                    Delete
                </button>
                <button
                    type="button"
                    id="quill2-download"
                    class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white shadow-sm transition hover:bg-indigo-500"
                >
                    Download
                </button>
            </div>
        </div>

        <section class="mt-8 rounded-3xl border border-slate-100 bg-slate-50/80 p-5 shadow-inner">
            <h2 class="text-sm font-semibold text-gray-700">Hidden spans (machine-readable)</h2>
            <p class="text-xs text-gray-500">They stay off-screen but mirror the feed so you can scrape or inspect them as needed.</p>
            <div id="quill2-hidden-tracking" class="sr-only" aria-hidden="true"></div>
        </section>

        <div
            id="quill2-comment-modal"
            class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 px-3 py-6 backdrop-blur"
            role="dialog"
            aria-modal="true"
            aria-labelledby="quill2-comment-title"
        >
            <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl ring-1 ring-black/10">
                <form id="quill2-comment-form" class="flex flex-col gap-3 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.4em] text-slate-400">New Comment</p>
                            <h2 id="quill2-comment-title" class="text-lg font-semibold text-slate-900">Add feedback</h2>
                        </div>
                        <button
                            type="button"
                            id="quill2-comment-close"
                            class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            aria-label="Close"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 20 20" stroke="currentColor" fill="none" stroke-width="1.8">
                                <path d="M5 5l10 10M15 5l-10 10" stroke-linecap="round" />
                            </svg>
                        </button>
                    </div>
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 p-3 text-xs text-slate-500">
                        <p class="font-semibold text-slate-700">Selected text</p>
                        <p id="quill2-comment-snippet" class="mt-1 max-h-20 overflow-y-auto whitespace-pre-wrap font-mono text-[0.65rem] text-slate-600"></p>
                    </div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="quill2-comment-body">
                        Comment
                    </label>
                    <textarea
                        id="quill2-comment-body"
                        class="h-28 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 shadow-inner focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
                        placeholder="Share your feedback..."
                        required
                    ></textarea>
                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            id="quill2-comment-cancel"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:bg-slate-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            id="quill2-comment-submit"
                            class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white shadow-sm transition hover:bg-slate-800"
                        >
                            Add Comment
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/quill-lite-change-tracker.js') }}"></script>
    <script>
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

            const initialContent = @json($initialContent ?? '');
            const initialDelta = @json($initialDelta ?? ['ops' => []]);
            const initialChanges = @json($initialChanges ?? []);
            const initialComments = @json($initialComments ?? []);
            const initialHtml = @json($initialHtml ?? null);
            const saveEndpoint = @json(route('quill2.save'));
            const deleteEndpoint = @json(route('quill2.destroy'));
            const currentUser = @json($quillUser ?? ['id' => 'guest-user', 'name' => 'Guest User']);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const quillModules = {
                toolbar: '#quill2-toolbar',
                history: {
                    delay: 400,
                    maxStack: 100,
                    userOnly: true,
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
                const plainText = await readClipboardPlainText();
                return { html: '', text: plainText || '' };
            };

            let currentViewMode = null;
            let isSidePanelOpen = true;
            let isResizingPanel = false;
            let panelStartX = 0;
            const DEFAULT_PANEL_WIDTH = sidePanel?.offsetWidth ?? 320;
            let panelStartWidth = DEFAULT_PANEL_WIDTH;
            let lastPanelWidth = DEFAULT_PANEL_WIDTH;
            let activePanelItem = null;
            let lastActiveActivityRef = null;
            let pendingCommentSelection = null;
            let pendingCommentSnippet = '';

            const viewToggleActiveClasses = ['bg-white', 'text-slate-900', 'shadow', '-translate-y-[1px]'];
            const viewToggleInactiveClasses = ['bg-transparent', 'text-slate-500', 'shadow-none', 'translate-y-0'];

            const tracker = new window.QuillLiteChangeTracker(quill, {
                attrPrefix: 'q2',
                user: {
                    id: currentUser?.id ?? 'guest-user',
                    name: currentUser?.name ?? 'Guest User',
                    email: currentUser?.email ?? null,
                },
            });

            const resolvedDelta = (initialDelta && Array.isArray(initialDelta.ops) && initialDelta.ops.length)
                ? initialDelta
                : {
                    ops: [
                        {
                            insert: initialContent + (initialContent.endsWith('\n') ? '' : '\n'),
                        },
                    ],
                };
            if (initialHtml) {
                quill.clipboard.dangerouslyPasteHTML(initialHtml, 'silent');
            } else {
                quill.setContents(resolvedDelta);
            }

            const setStatus = (message, tone = 'muted') => {
                if (!statusEl) {
                    return;
                }
                statusEl.textContent = message;
                statusEl.classList.remove('text-emerald-600', 'text-rose-600', 'text-gray-500', 'text-slate-500');
                if (tone === 'success') {
                    statusEl.classList.add('text-emerald-600');
                } else if (tone === 'error') {
                    statusEl.classList.add('text-rose-600');
                } else {
                    statusEl.classList.add('text-slate-500');
                }
            };

            const PANEL_TOGGLE_LABELS = {
                open: 'Hide Comments',
                closed: 'Show Comments',
            };

            const applyViewToggleStyles = () => {
                const toggles = [
                    [redlineBtn, currentViewMode === 'redline'],
                    [cleanBtn, currentViewMode === 'clean'],
                ];
                toggles.forEach(([button, isActive]) => {
                    if (!button) {
                        return;
                    }
                    button.setAttribute('aria-pressed', String(isActive));
                    viewToggleActiveClasses.forEach((cls) => button.classList.toggle(cls, isActive));
                    viewToggleInactiveClasses.forEach((cls) => button.classList.toggle(cls, !isActive));
                });
            };

            const updatePanelToggleLabel = () => {
                if (!togglePanelButton) {
                    return;
                }
                togglePanelButton.textContent = isSidePanelOpen
                    ? PANEL_TOGGLE_LABELS.open
                    : PANEL_TOGGLE_LABELS.closed;
            };

            const refreshPanelVisibility = () => {
                if (!sidePanel) {
                    return;
                }
                if (!isSidePanelOpen) {
                    lastPanelWidth = sidePanel.offsetWidth || lastPanelWidth;
                } else if (lastPanelWidth) {
                    sidePanel.style.width = `${lastPanelWidth}px`;
                }
                sidePanel.classList.toggle('hidden', !isSidePanelOpen);
                if (panelResizer) {
                    panelResizer.classList.toggle('hidden', !isSidePanelOpen);
                }
                updatePanelToggleLabel();
            };

            const ensureSidePanelVisible = () => {
                if (!isSidePanelOpen) {
                    isSidePanelOpen = true;
                    refreshPanelVisibility();
                }
            };

            const clearActivePanelHighlight = (resetMemory = false) => {
                if (activePanelItem) {
                    activePanelItem.classList.remove('quill2-activity-active');
                    activePanelItem = null;
                }
                if (resetMemory) {
                    lastActiveActivityRef = null;
                }
            };

            const setActivePanelItem = (element) => {
                if (element === activePanelItem) {
                    return;
                }
                clearActivePanelHighlight();
                if (!element) {
                    lastActiveActivityRef = null;
                    return;
                }
                ensureSidePanelVisible();
                const isHistoryItem = Boolean(element.closest('#quill2-history-feed'));
                if (isHistoryItem && historySection) {
                    historySection.classList.remove('hidden');
                    historySection.open = true;
                }
                element.classList.add('quill2-activity-active');
                if (!element.hasAttribute('tabindex')) {
                    element.setAttribute('tabindex', '-1');
                }
                element.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                element.focus?.({ preventScroll: true });
                activePanelItem = element;
                lastActiveActivityRef = {
                    type: element.dataset.activityType,
                    id: element.dataset.commentId || element.dataset.changeId || null,
                    scope: isHistoryItem ? 'history' : 'activity',
                };
            };

            const restoreActivePanelItem = () => {
                if (!lastActiveActivityRef?.id) {
                    return;
                }
                let target = null;
                if (lastActiveActivityRef.type === 'comment') {
                    target = activityFeedEl?.querySelector(`[data-comment-id="${lastActiveActivityRef.id}"]`);
                } else if (lastActiveActivityRef.type === 'change') {
                    target = activityFeedEl?.querySelector(`[data-change-id="${lastActiveActivityRef.id}"]`)
                        || historyFeedEl?.querySelector(`[data-change-id="${lastActiveActivityRef.id}"]`);
                }
                if (!target) {
                    lastActiveActivityRef = null;
                    return;
                }
                if (target.closest('#quill2-history-feed') && historySection) {
                    historySection.classList.remove('hidden');
                    historySection.open = true;
                }
                target.classList.add('quill2-activity-active');
                activePanelItem = target;
            };

            const MIN_PANEL_WIDTH = 240;
            const MAX_PANEL_WIDTH = 640;
            const clampPanelWidth = (value) => Math.max(MIN_PANEL_WIDTH, Math.min(MAX_PANEL_WIDTH, value));

            const stopPanelResize = () => {
                if (!isResizingPanel) {
                    return;
                }
                isResizingPanel = false;
                document.body.classList.remove('select-none');
                document.removeEventListener('mousemove', handlePanelResize);
                document.removeEventListener('mouseup', stopPanelResize);
            };

            const handlePanelResize = (event) => {
                if (!isResizingPanel || !sidePanel) {
                    return;
                }
                const deltaX = event.clientX - panelStartX;
                let nextWidth = clampPanelWidth(panelStartWidth - deltaX);
                sidePanel.style.width = `${nextWidth}px`;
                lastPanelWidth = nextWidth;
            };

            panelResizer?.addEventListener('mousedown', (event) => {
                if (!isSidePanelOpen) {
                    return;
                }
                isResizingPanel = true;
                panelStartX = event.clientX;
                panelStartWidth = sidePanel?.offsetWidth ?? lastPanelWidth;
                document.body.classList.add('select-none');
                document.addEventListener('mousemove', handlePanelResize);
                document.addEventListener('mouseup', stopPanelResize, { once: true });
            });

            togglePanelButton?.addEventListener('click', () => {
                isSidePanelOpen = !isSidePanelOpen;
                refreshPanelVisibility();
            });

            const countPendingChanges = () => tracker.getChanges().filter((change) => change.status === 'pending').length;

            const formatActivityTime = (isoString) => {
                if (!isoString) {
                    return '—';
                }
                return new Date(isoString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            };

            const buildActivityItems = () => {
                const changeItems = tracker.getChanges({ sort: 'asc' }).map((change) => ({
                    type: 'change',
                    id: change.id,
                    createdAt: change.createdAt ?? change.updatedAt ?? new Date().toISOString(),
                    payload: change,
                }));
                const commentItems = commentLog.map((comment) => ({
                    type: 'comment',
                    id: comment.id,
                    createdAt: comment.createdAt ?? new Date().toISOString(),
                    payload: comment,
                }));
                return [...changeItems, ...commentItems].sort((a, b) => {
                    const aTime = new Date(a.createdAt).getTime();
                    const bTime = new Date(b.createdAt).getTime();
                    return aTime - bTime;
                });
            };

            const focusEditorRange = (range) => {
                if (!range || !Number.isFinite(range.index)) {
                    return false;
                }
                const index = Math.max(0, Number(range.index));
                const length = Number.isFinite(range.length) ? Math.max(0, Number(range.length)) : 0;
                quill.setSelection(index, length, silentSource);
                quill.focus();
                if (editorScrollContainer) {
                    const bounds = quill.getBounds(index, Math.max(length, 1));
                    const nextTop = Math.max(0, (bounds?.top ?? 0) + editorScrollContainer.scrollTop - 60);
                    editorScrollContainer.scrollTo({ top: nextTop, behavior: 'smooth' });
                }
                return true;
            };

            const focusCommentById = (commentId) => {
                if (!commentId) {
                    return false;
                }
                const comment = commentLog.find((entry) => entry.id === commentId);
                if (!comment) {
                    return false;
                }
                return focusEditorRange(comment.range);
            };

            const focusChangeById = (changeId) => {
                if (!changeId) {
                    return false;
                }
                const range = tracker.findChangeRange(changeId);
                if (!range) {
                    return false;
                }
                return focusEditorRange(range);
            };

            const findCommentByPosition = (index, length = 0) => {
                if (!Number.isFinite(index)) {
                    return null;
                }
                const start = Math.max(0, index);
                const end = start + Math.max(0, length);
                return commentLog.find((comment) => {
                    const commentStart = Number(comment.range?.index);
                    const commentLength = Number(comment.range?.length);
                    if (!Number.isFinite(commentStart) || !Number.isFinite(commentLength)) {
                        return false;
                    }
                    const normalizedStart = Math.max(0, commentStart);
                    const normalizedEnd = normalizedStart + Math.max(1, commentLength);
                    return start <= normalizedEnd && normalizedStart <= end;
                }) || null;
            };

            const highlightPanelItemForChange = (changeId) => {
                if (!changeId) {
                    return false;
                }
                const target = activityFeedEl?.querySelector(`[data-change-id="${changeId}"]`)
                    || historyFeedEl?.querySelector(`[data-change-id="${changeId}"]`);
                if (!target) {
                    return false;
                }
                setActivePanelItem(target);
                return true;
            };

            const highlightPanelItemForComment = (commentId) => {
                if (!commentId) {
                    return false;
                }
                const target = activityFeedEl?.querySelector(`[data-comment-id="${commentId}"]`);
                if (!target) {
                    return false;
                }
                setActivePanelItem(target);
                return true;
            };

            const toggleBodyScroll = (locked) => {
                document.body.classList.toggle('overflow-hidden', Boolean(locked));
            };

            const openCommentModal = (selection) => {
                if (!commentModal || !commentSnippetEl || !commentBodyInput) {
                    return;
                }
                pendingCommentSelection = {
                    index: Number(selection.index),
                    length: Number(selection.length),
                };
                pendingCommentSnippet = quill.getText(selection.index, selection.length).trim() || '(empty selection)';
                commentSnippetEl.textContent = pendingCommentSnippet;
                commentBodyInput.value = '';
                commentModal.classList.remove('hidden');
                commentModal.classList.add('flex');
                toggleBodyScroll(true);
                window.requestAnimationFrame(() => commentBodyInput.focus());
            };

            const closeCommentModal = () => {
                if (!commentModal) {
                    return;
                }
                commentModal.classList.add('hidden');
                commentModal.classList.remove('flex');
                toggleBodyScroll(false);
                pendingCommentSelection = null;
                pendingCommentSnippet = '';
                if (commentBodyInput) {
                    commentBodyInput.value = '';
                }
            };

            const handlePanelItemSelection = (element) => {
                if (!element) {
                    return;
                }
                const { activityType } = element.dataset;
                let handled = false;
                if (activityType === 'comment') {
                    handled = focusCommentById(element.dataset.commentId);
                } else if (activityType === 'change') {
                    handled = focusChangeById(element.dataset.changeId);
                }
                if (handled) {
                    setActivePanelItem(element);
                } else if (activityType === 'change') {
                    setStatus('Unable to locate that change in the document anymore.', 'error');
                } else if (activityType === 'comment') {
                    setStatus('Unable to locate that commented text.', 'error');
                }
            };

            const renderActivityFeed = () => {
                if (!activityFeedEl) {
                    return;
                }
                const rawEntries = buildActivityItems();
                const pendingEntries = [];
                const resolvedChangeEntries = [];
                rawEntries.forEach((entry) => {
                    if (entry.type === 'change' && entry.payload?.status && entry.payload.status !== 'pending') {
                        resolvedChangeEntries.push(entry);
                    } else {
                        pendingEntries.push(entry);
                    }
                });
                const pendingCount = countPendingChanges();
                if (activitySummaryEl) {
                    const totalCount = rawEntries.length;
                    const itemLabel = totalCount === 1 ? 'item' : 'items';
                    const pendingLabel = pendingCount === 1 ? 'pending change' : 'pending changes';
                    activitySummaryEl.textContent = `${totalCount} ${itemLabel} • ${pendingCount} ${pendingLabel}`;
                }

                clearActivePanelHighlight();
                activityFeedEl.innerHTML = '';
                if (!pendingEntries.length) {
                    const empty = document.createElement('li');
                    empty.className = 'rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 p-4 text-xs font-medium text-slate-500';
                    empty.textContent = resolvedChangeEntries.length
                        ? 'No active items. Completed changes live under History.'
                        : 'Start typing or add a comment to see activity.';
                    activityFeedEl.appendChild(empty);
                } else {
                    pendingEntries.forEach((entry) => {
                        if (entry.type === 'comment') {
                            const comment = entry.payload;
                            const item = document.createElement('li');
                            item.className = 'rounded-2xl border border-amber-100 bg-white/90 p-4 shadow-sm';
                            item.dataset.activityType = 'comment';
                            item.dataset.commentId = comment.id;
                            item.setAttribute('tabindex', '-1');
                            const authorName = encodeContent(comment.author?.name ?? comment.author ?? 'Unknown');
                            const body = encodeContent(comment.text ?? '');
                            const snippet = encodeContent(comment.snippet ?? '');
                            item.innerHTML = `
                                <div class="flex items-center justify-between text-xs text-gray-500">
                                    <span class="font-semibold text-gray-700">Comment • ${authorName}</span>
                                    <time datetime="${comment.createdAt ?? ''}">${formatActivityTime(comment.createdAt)}</time>
                                </div>
                                <p class="mt-2 text-sm text-gray-800">${body}</p>
                                <p class="mt-2 text-xs text-gray-500">Excerpt: <span class="font-mono text-gray-700">${snippet}</span></p>
                            `;
                            activityFeedEl.appendChild(item);
                            return;
                        }
                        const record = entry.payload;
                        const item = document.createElement('li');
                        item.className = 'rounded-2xl border border-slate-100 bg-white/95 p-4 shadow-sm';
                        item.dataset.activityType = 'change';
                        item.dataset.changeId = record.id;
                        item.setAttribute('tabindex', '-1');
                        const statusCls = record.type === 'insert' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700';
                        const actor = record.user?.name ?? 'Unknown';
                        const statusLabel = record.status;
                        const resolutionSuffix = record.status !== 'pending'
                            ? ` · ${record.resolvedBy?.name ?? 'Unknown'}`
                            : '';
                        item.innerHTML = `
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span class="font-semibold text-slate-800">Change • ${record.type}</span>
                                <time datetime="${record.createdAt ?? ''}">${formatActivityTime(record.createdAt)}</time>
                            </div>
                            <p class="mt-3 break-words text-slate-800">${record.preview}</p>
                            <div class="mt-3 text-xs text-slate-500">
                                <span>Author: <strong>${actor}</strong></span>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
                                <span>Status: <strong class="capitalize">${statusLabel}</strong>${resolutionSuffix}</span>
                                <span class="rounded-full px-2 py-0.5 text-[0.6rem] font-semibold uppercase tracking-wide ${statusCls}">${record.id.slice(0, 8)}</span>
                            </div>
                            <div class="mt-4 flex gap-2">
                                <button
                                    type="button"
                                    class="flex-1 rounded-full bg-emerald-600 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-emerald-500"
                                    data-change-action="accept"
                                    data-change-id="${record.id}"
                                >
                                    Accept
                                </button>
                                <button
                                    type="button"
                                    class="flex-1 rounded-full bg-rose-600 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-rose-500"
                                    data-change-action="reject"
                                    data-change-id="${record.id}"
                                >
                                    Reject
                                </button>
                            </div>
                        `;
                        activityFeedEl.appendChild(item);
                    });
                }

                if (historyCountEl) {
                    historyCountEl.textContent = resolvedChangeEntries.length;
                }
                if (historySection) {
                    const hasHistory = resolvedChangeEntries.length > 0;
                    historySection.classList.toggle('hidden', !hasHistory);
                    if (!hasHistory) {
                        historySection.open = false;
                    }
                }
                if (historyFeedEl) {
                    historyFeedEl.innerHTML = '';
                    if (resolvedChangeEntries.length) {
                        resolvedChangeEntries.forEach((entry) => {
                            const record = entry.payload;
                            const item = document.createElement('li');
                            item.className = 'rounded-2xl border border-slate-100 bg-white p-3 text-xs text-slate-600 shadow-sm';
                            item.dataset.activityType = 'change';
                            item.dataset.changeId = record.id;
                            item.setAttribute('tabindex', '-1');
                            const statusCls = record.type === 'insert' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700';
                            const actor = record.user?.name ?? 'Unknown';
                            const resolver = record.resolvedBy?.name ?? actor;
                            item.innerHTML = `
                                <div class="flex items-center justify-between text-[0.65rem] uppercase tracking-wide text-slate-500">
                                    <span class="font-semibold text-slate-700">Change • ${record.type}</span>
                                    <span class="rounded-full px-2 py-0.5 text-[0.6rem] font-semibold ${statusCls}">${record.status}</span>
                                </div>
                                <p class="mt-2 text-sm text-slate-800">${record.preview}</p>
                                <div class="mt-2 space-y-1 text-[0.7rem] text-slate-500">
                                    <p><span class="font-semibold text-slate-700">Author:</span> ${actor}</p>
                                    <p><span class="font-semibold text-slate-700">Resolved by:</span> ${resolver}</p>
                                    <p><span class="font-semibold text-slate-700">Updated:</span> ${formatActivityTime(record.updatedAt ?? record.createdAt)}</p>
                                    <p class="font-mono text-[0.65rem] text-slate-400">${record.id.slice(0, 12)}</p>
                                </div>
                            `;
                            historyFeedEl.appendChild(item);
                        });
                    }
                }
                restoreActivePanelItem();
            };

            const applyCommentHighlights = () => {
                if (!commentLog.length) {
                    return;
                }
                commentLog.forEach((comment) => {
                    const index = Number(comment?.range?.index);
                    const length = Number(comment?.range?.length);
                    if (!Number.isFinite(index) || !Number.isFinite(length) || length <= 0) {
                        return;
                    }
                    quill.formatText(index, length, { background: '#FEF9C3' }, silentSource);
                });
            };

            activityFeedEl?.addEventListener('click', (event) => {
                const changeButton = event.target.closest('[data-change-action]');
                if (changeButton) {
                    const { changeAction, changeId } = changeButton.dataset;
                    if (changeAction === 'accept') {
                        tracker.acceptChange(changeId);
                    } else if (changeAction === 'reject') {
                        tracker.rejectChange(changeId);
                    }
                    return;
                }
                const card = event.target.closest('[data-activity-type]');
                if (card) {
                    handlePanelItemSelection(card);
                }
            });

            historyFeedEl?.addEventListener('click', (event) => {
                const card = event.target.closest('[data-change-id]');
                if (card) {
                    handlePanelItemSelection(card);
                }
            });

            quill.root.addEventListener('click', (event) => {
                const targetNode = event.target instanceof Element ? event.target : event.target?.parentElement;
                if (!targetNode) {
                    return;
                }
                const changeNode = targetNode.closest('[data-q2-change-id]');
                if (changeNode) {
                    const changeId = changeNode.getAttribute('data-q2-change-id');
                    highlightPanelItemForChange(changeId);
                    return;
                }
                window.requestAnimationFrame(() => {
                    const selection = quill.getSelection();
                    if (!selection) {
                        return;
                    }
                    const comment = findCommentByPosition(selection.index, selection.length);
                    if (comment) {
                        highlightPanelItemForComment(comment.id);
                    }
                });
            });

            const updateCommentButtonState = () => {
                if (!addCommentButton) {
                    return;
                }
                const selection = quill.getSelection();
                const enabled = selection && selection.length > 0;
                addCommentButton.disabled = !enabled;
            };

            const addCommentFromSelection = () => {
                const selection = quill.getSelection(true);
                if (!selection || selection.length === 0) {
                    setStatus('Select a word or sentence before adding a comment.', 'error');
                    return;
                }
                openCommentModal(selection);
            };

            addCommentButton?.addEventListener('click', addCommentFromSelection);

            const persistComment = (body) => {
                if (!pendingCommentSelection) {
                    setStatus('Could not find your selected text. Please try again.', 'error');
                    return false;
                }
                const selectionIndex = Math.max(0, Number(pendingCommentSelection.index));
                const selectionLength = Math.max(0, Number(pendingCommentSelection.length));
                const snippet = pendingCommentSnippet || quill.getText(selectionIndex, selectionLength).trim() || '(empty selection)';
                const comment = {
                    id: buildCommentId(),
                    text: body.trim(),
                    snippet,
                    range: {
                        index: selectionIndex,
                        length: selectionLength,
                    },
                    createdAt: new Date().toISOString(),
                    author: {
                        id: tracker.options.user?.id ?? currentUser?.id ?? 'guest-user',
                        name: tracker.options.user?.name ?? currentUser?.name ?? 'Guest User',
                    },
                };
                commentLog.unshift(comment);
                if (selectionLength > 0) {
                    quill.formatText(selectionIndex, selectionLength, { background: '#FEF9C3' }, userSource);
                }
                renderActivityFeed();
                setStatus('Comment added', 'success');
                closeCommentModal();
                quill.setSelection(selectionIndex, selectionLength, silentSource);
                quill.focus();
                return true;
            };

            commentForm?.addEventListener('submit', (event) => {
                event.preventDefault();
                const bodyValue = commentBodyInput?.value?.trim();
                if (!bodyValue) {
                    setStatus('Please enter a comment before submitting.', 'error');
                    commentBodyInput?.focus();
                    return;
                }
                persistComment(bodyValue);
            });

            const abortCommentModal = () => {
                closeCommentModal();
                quill.focus();
            };

            commentCancelButton?.addEventListener('click', abortCommentModal);
            commentCloseButton?.addEventListener('click', abortCommentModal);
            commentModal?.addEventListener('click', (event) => {
                if (event.target === commentModal) {
                    abortCommentModal();
                }
            });
            commentModal?.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    abortCommentModal();
                }
            });

            const renderHiddenSpans = (changes = tracker.getChanges({ sort: 'asc' })) => {
                hiddenContainer.innerHTML = '';
                changes.forEach((change) => {
                    const span = document.createElement('span');
                    span.className = 'quill2-change';
                    span.dataset.id = change.id;
                    span.dataset.type = change.type;
                    span.dataset.status = change.status;
                    span.dataset.length = change.length;
                    span.dataset.timestamp = change.createdAt;
                    span.dataset.userId = change.user?.id ?? '';
                    span.dataset.userName = change.user?.name ?? '';
                    span.textContent = change.preview;
                    hiddenContainer.appendChild(span);
                });
            };

            const describeChangeTooltip = (change) => {
                if (!change) {
                    return null;
                }
                const actor = change.user?.name ?? 'Unknown';
                const verb = change.type === 'insert' ? 'Inserted' : 'Deleted';
                const timestamp = change.createdAt
                    ? new Date(change.createdAt).toLocaleString()
                    : 'Unknown time';
                const excerpt = change.preview ? `\n${change.preview}` : '';
                return `${verb} by ${actor} on ${timestamp}${excerpt}`;
            };

            const refreshChangeTooltips = () => {
                if (!quill || !quill.root) {
                    return;
                }
                const nodes = quill.root.querySelectorAll('[data-q2-change-id]');
                if (!nodes.length) {
                    return;
                }
                const changes = tracker.getChanges({ sort: 'asc' });
                const changeMap = new Map(changes.map((change) => [change.id, change]));
                nodes.forEach((node) => {
                    const changeId = node.getAttribute('data-q2-change-id');
                    const metadata = changeMap.get(changeId);
                    const tooltip = describeChangeTooltip(metadata);
                    if (tooltip) {
                        node.setAttribute('title', tooltip);
                    } else {
                        node.removeAttribute('title');
                    }
                });
            };

            tracker.on('ledger-change', (changes) => {
                renderActivityFeed();
                renderHiddenSpans(changes);
                refreshChangeTooltips();
            });

            if (Array.isArray(initialChanges) && initialChanges.length) {
                tracker.loadChanges(initialChanges);
            } else {
                renderActivityFeed();
                renderHiddenSpans(tracker.getChanges({ sort: 'asc' }));
            }

            refreshChangeTooltips();
            applyCommentHighlights();
            refreshPanelVisibility();
            quill.on('selection-change', updateCommentButtonState);
            updateCommentButtonState();

            downloadButton?.addEventListener('click', () => {
                const payload = tracker.snapshot();
                const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `quill2-snapshot-${Date.now()}.json`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            });

            const setViewMode = (mode) => {
                if (currentViewMode === mode) {
                    return;
                }
                currentViewMode = mode;
                if (mode === 'clean') {
                    if (quillContainer) {
                        quillContainer.dataset.viewMode = 'clean';
                    }
                    quill.root.dataset.viewMode = 'clean';
                } else {
                    if (quillContainer) {
                        delete quillContainer.dataset.viewMode;
                    }
                    delete quill.root.dataset.viewMode;
                }
                applyViewToggleStyles();
            };

            const BLOCKLIST_TAGS = ['meta', 'style', 'script', 'link', 'iframe', 'object', 'embed', 'o:p', 'xml'];
            const GLOBAL_PASTE_ATTRS = new Set(['href', 'src', 'alt', 'title', 'colspan', 'rowspan', 'cellpadding', 'cellspacing', 'width', 'height']);
            const TAG_PASTE_ATTRS = {
                a: new Set(['href', 'title', 'target', 'rel']),
                img: new Set(['src', 'alt', 'title', 'width', 'height']),
                td: new Set(['colspan', 'rowspan']),
                th: new Set(['colspan', 'rowspan']),
            };
            const WORD_STRIP_REGEXES = [
                /<!--\[if.*?endif\]-->/gis,
                /<style[^>]*>[\s\S]*?<\/style>/gi,
                /<meta[^>]*>/gi,
                /<link[^>]*>/gi,
                /<xml[^>]*>[\s\S]*?<\/xml>/gi,
                /class="?Mso[a-z0-9]+"?/gi,
                /lang="?[a-z-]+"?/gi,
            ];

            const normalizeLegacyTags = (value) => (value || '')
                .replace(/<b(\s|>)/gi, '<strong$1')
                .replace(/<\/b>/gi, '</strong>')
                .replace(/<i(\s|>)/gi, '<em$1')
                .replace(/<\/i>/gi, '</em>');

            const cleanOfficeMarkup = (value) => {
                if (!value) {
                    return '';
                }
                let output = value;
                WORD_STRIP_REGEXES.forEach((regex) => {
                    output = output.replace(regex, '');
                });
                return output;
            };

            const sanitizeHtmlFragment = (html) => {
                if (!html || typeof html !== 'string') {
                    return '';
                }
                const normalized = normalizeLegacyTags(cleanOfficeMarkup(html));
                const parser = new DOMParser();
                const parsed = parser.parseFromString(normalized, 'text/html');
                if (!parsed?.body) {
                    return normalized;
                }
                const removeSelector = BLOCKLIST_TAGS.join(',');
                if (removeSelector) {
                    parsed.body.querySelectorAll(removeSelector).forEach((node) => node.remove());
                }
                const showElementFlag = window.NodeFilter?.SHOW_ELEMENT ?? 1;
                const walker = parsed.createTreeWalker(parsed.body, showElementFlag, null);
                const pendingRemoval = [];
                while (walker.nextNode()) {
                    const node = walker.currentNode;
                    if (!node) {
                        continue;
                    }
                    const tag = node.tagName?.toLowerCase() ?? '';
                    if (tag.startsWith('o:') || tag === 'xml') {
                        pendingRemoval.push(node);
                        continue;
                    }
                    Array.from(node.attributes).forEach((attr) => {
                        const attrName = attr.name.toLowerCase();
                        if (attrName.startsWith('data-q2-')) {
                            return;
                        }
                        if (attrName === 'style' || attrName.startsWith('on')) {
                            node.removeAttribute(attr.name);
                            return;
                        }
                        const tagAllowList = TAG_PASTE_ATTRS[tag];
                        if (tagAllowList) {
                            if (!tagAllowList.has(attrName)) {
                                node.removeAttribute(attr.name);
                            }
                            return;
                        }
                        if (!GLOBAL_PASTE_ATTRS.has(attrName)) {
                            node.removeAttribute(attr.name);
                        }
                    });
                }
                pendingRemoval.forEach((node) => node.remove());
                return parsed.body.innerHTML;
            };

            const escapeHtml = (value = '') => value
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

            const plainTextToHtml = (text) => {
                if (!text) {
                    return '<p><br></p>';
                }
                const escaped = escapeHtml(text);
                const paragraphs = escaped
                    .split(/\n{2,}/)
                    .map((chunk) => `<p>${chunk.replace(/\n/g, '<br>')}</p>`)
                    .join('');
                return paragraphs || '<p><br></p>';
            };

            const composeSafeHtml = (html, plainText) => {
                const sanitized = sanitizeHtmlFragment(html);
                if (sanitized && sanitized.trim().length) {
                    return sanitized;
                }
                if (plainText && plainText.trim().length) {
                    return plainTextToHtml(plainText);
                }
                return '';
            };

            const registerSanitizedPasteHandler = () => {
                const handlePaste = async (event) => {
                    if (event.clipboardData?.files?.length) {
                        return;
                    }
                    const payload = await resolveClipboardPayload(event);
                    const safeHtml = composeSafeHtml(payload.html, payload.text);
                    if (!safeHtml) {
                        return;
                    }

                    event.preventDefault();
                    event.stopPropagation();

                    const selection = quill.getSelection(true) || { index: quill.getLength(), length: 0 };
                    const docLengthBefore = quill.getLength();

                    tracker.withBatchChange('insert', () => {
                        if (selection.length) {
                            quill.deleteText(selection.index, selection.length, userSource);
                        }
                        quill.clipboard.dangerouslyPasteHTML(selection.index, safeHtml, userSource);
                    });

                    const docLengthAfter = quill.getLength();
                    const insertedLength = docLengthAfter - (docLengthBefore - selection.length);
                    const caretIndex = selection.index + Math.max(insertedLength, 0);

                    window.requestAnimationFrame(() => {
                        quill.focus();
                        quill.setSelection(Math.min(caretIndex, quill.getLength()), 0, silentSource);
                        refreshChangeTooltips();
                    });
                };

                quill.root.addEventListener('paste', (event) => {
                    handlePaste(event).catch((error) => {
                        console.error('Quill Lite paste handler error', error);
                        setStatus('Paste failed. Please try again.', 'error');
                    });
                }, { capture: true });
            };

            const registerSmartTypographyBindings = () => {
                const isWordChar = (char) => /[A-Za-z0-9]/.test(char);
                const isWhitespace = (char) => !char || /\s/.test(char);
                const SMART_QUOTES = {
                    single: { open: '‘', close: '’' },
                    double: { open: '“', close: '”' },
                };

                const resolveSmartQuote = (kind, prefix = '', suffix = '') => {
                    const prev = prefix.slice(-1) || '';
                    const next = suffix.slice(0, 1) || '';
                    const { open, close } = SMART_QUOTES[kind];
                    const looksLikeWordLeft = isWordChar(prev);
                    const looksLikeWordRight = isWordChar(next);
                    const isBoundaryLeft = !prev || /[\s([{<]/.test(prev);
                    if (isBoundaryLeft && !looksLikeWordRight) {
                        return open;
                    }
                    if (!isBoundaryLeft && (!next || /[\s)\]}>.,!?;:]/.test(next))) {
                        return close;
                    }
                    if (looksLikeWordLeft && (kind === 'single' || looksLikeWordRight)) {
                        return close;
                    }
                    return open;
                };

                const insertGlyph = (glyph, range) => {
                    tracker.withBatchChange('insert', () => {
                        quill.insertText(range.index, glyph, userSource);
                    });
                    quill.setSelection(range.index + glyph.length, 0, 'silent');
                };

                const createQuoteHandler = (kind) => (range, context) => {
                    if (!range || context?.event?.altKey || context?.event?.ctrlKey || context?.event?.metaKey) {
                        return true;
                    }
                    const glyph = resolveSmartQuote(kind, context.prefix, context.suffix);
                    if (!glyph) {
                        return true;
                    }
                    insertGlyph(glyph, range);
                    return false;
                };

                quill.keyboard.addBinding({ key: 222, collapsed: true, shiftKey: false }, createQuoteHandler('single'));
                quill.keyboard.addBinding({ key: 222, collapsed: true, shiftKey: true }, createQuoteHandler('double'));
                quill.keyboard.addBinding({ key: 189, collapsed: true }, (range, context) => {
                    if (!range || range.index < 1) {
                        return true;
                    }
                    const event = context?.event;
                    if (event && (event.altKey || event.ctrlKey || event.metaKey)) {
                        return true;
                    }
                    const prevChar = quill.getText(range.index - 1, 1);
                    if (prevChar !== '-') {
                        return true;
                    }
                    tracker.withBatchChange('insert', () => {
                        quill.deleteText(range.index - 1, 1, userSource);
                        quill.insertText(range.index - 1, '—', userSource);
                    });
                    quill.setSelection(range.index, 0, 'silent');
                    return false;
                });
            };

            redlineBtn?.addEventListener('click', () => setViewMode('redline'));
            cleanBtn?.addEventListener('click', () => setViewMode('clean'));

            setViewMode('redline');
            registerSanitizedPasteHandler();
            registerSmartTypographyBindings();
            refreshChangeTooltips();

            saveButton?.addEventListener('click', async () => {
                if (!saveEndpoint) {
                    return;
                }
                saveButton.disabled = true;
                setStatus('Saving...');
                try {
                    const snapshot = tracker.snapshot();
                    const response = await fetch(saveEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            delta: snapshot.delta,
                            changes: snapshot.changes,
                            comments: commentLog,
                            text: snapshot.text,
                            html: quill.root.innerHTML,
                        }),
                    });
                    if (!response.ok) {
                        throw new Error('Server error');
                    }
                    const payload = await response.json();
                    const savedAt = payload.updated_at ? new Date(payload.updated_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'now';
                    setStatus(`Saved at ${savedAt}`, 'success');
                } catch (error) {
                    setStatus(`Save failed: ${error.message}`, 'error');
                } finally {
                    saveButton.disabled = false;
                }
            });

            deleteButton?.addEventListener('click', async () => {
                if (!deleteEndpoint) {
                    return;
                }
                const confirmed = window.confirm('Delete the stored document and reset the editor?');
                if (!confirmed) {
                    return;
                }
                deleteButton.disabled = true;
                setStatus('Deleting...');
                try {
                    const response = await fetch(deleteEndpoint, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                        },
                    });
                    if (!response.ok) {
                        throw new Error('Server error');
                    }
                    window.location.reload();
                } catch (error) {
                    deleteButton.disabled = false;
                    setStatus(`Delete failed: ${error.message}`, 'error');
                }
            });

            window.quill2Tracker = tracker;
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initQuillLite, { once: true });
        } else {
            initQuillLite();
        }
    </script>
@endpush

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">
    <style>
        #quill2-root #quill2-toolbar.ql-toolbar.ql-snow {
            border: none;
            background: transparent;
        }

        #quill2-root #quill2-editor.ql-container.ql-snow {
            border: none;
            background: transparent;
        }

        #quill2-root {
            --q2-insert-bg-default: #dbeafe;
            --q2-insert-border-default: #0ea5e9;
            --q2-insert-shadow-default: rgba(14, 165, 233, 0.3);
        }

        #quill2-root #quill2-editor .ql-editor {
            font-size: 1rem;
            line-height: 1.7;
            color: #0f172a;
        }

        #quill2-root #quill2-hidden-tracking .quill2-change {
            display: inline;
        }

        #quill2-root #quill2-editor [data-q2-change-type="insert"] {
            position: relative;
            background-color: var(--q2-insert-bg, var(--q2-insert-bg-default));
            box-shadow: inset 0 -0.35em 0 var(--q2-insert-shadow, var(--q2-insert-shadow-default));
            border-bottom: 1px solid var(--q2-insert-border, var(--q2-insert-border-default));
            border-radius: 0.15rem;
        }

        #quill2-root #quill2-editor [data-q2-change-type="delete"] {
            background-color: #fee2e2;
            color: #b91c1c;
            text-decoration: line-through;
            text-decoration-thickness: 2px;
            text-decoration-color: #991b1b;
        }

        #quill2-root #quill2-editor[data-view-mode="clean"] [data-q2-change-type="delete"] {
            display: none;
        }

        #quill2-root #quill2-editor[data-view-mode="clean"] [data-q2-change-type="insert"] {
            background: none;
            box-shadow: none;
            border-bottom: none;
            color: inherit;
        }

        #quill2-root .quill2-activity-active {
            border-color: #fbbf24 !important;
            box-shadow: 0 20px 45px -20px rgba(251, 191, 36, 0.65), 0 0 0 2px rgba(251, 191, 36, 0.85);
            transform: translateY(-1px);
        }

        #quill2-root #quill2-add-comment:disabled {
            opacity: 0.8;
        }

        #quill2-root #quill2-add-comment svg {
            color: currentColor;
        }

        #quill2-root #quill2-add-comment:disabled svg {
            color: #94a3b8;
        }


        body.select-none {
            user-select: none;
        }
    </style>
@endpush
