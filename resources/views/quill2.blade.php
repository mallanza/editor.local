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
                            <span class="ql-formats gap-2">
                                <button
                                    type="button"
                                    id="quill2-paste"
                                    class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white/70 px-3 py-1.5 text-[0.65rem] font-semibold uppercase tracking-wide text-slate-700 shadow-sm transition hover:text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400"
                                    title="Paste with formatting"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false">
                                        <path d="M7 4h6v2h3v11H4V6h3V4Z" stroke-linejoin="round" />
                                        <path d="M8 1.5h4V4H8z" />
                                    </svg>
                                    <span class="sr-only">Paste with formatting</span>
                                </button>
                                <button
                                    type="button"
                                    id="quill2-paste-text"
                                    class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white/70 px-3 py-1.5 text-[0.65rem] font-semibold uppercase tracking-wide text-slate-700 shadow-sm transition hover:text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400"
                                    title="Paste as plain text"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false">
                                        <path d="M6 3h8v3h3v11H3V6h3V3Z" stroke-linejoin="round" />
                                        <path d="M7 9h6M7 12h4" stroke-linecap="round" />
                                    </svg>
                                    <span class="sr-only">Paste as text</span>
                                </button>
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
                            <span class="ql-formats gap-2">
                                <button
                                    type="button"
                                    id="quill2-accept-current"
                                    class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[0.65rem] font-semibold uppercase tracking-wide text-emerald-700 shadow-sm transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:border-emerald-100 disabled:bg-emerald-50 disabled:text-emerald-300 disabled:pointer-events-none"
                                    title="Accept selected change"
                                    aria-label="Accept selected change"
                                    disabled
                                >
                                    <svg class="h-4.5 w-4.5" viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false">
                                        <path d="M8 4h8l5 5v13a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" stroke-linejoin="round" />
                                        <path d="M16 4v5h5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M10 15.5l3 3.5 5-6" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <span class="sr-only">Accept change</span>
                                </button>
                                <button
                                    type="button"
                                    id="quill2-reject-current"
                                    class="inline-flex items-center gap-1 rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-[0.65rem] font-semibold uppercase tracking-wide text-rose-700 shadow-sm transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:border-rose-100 disabled:bg-rose-50 disabled:text-rose-300 disabled:pointer-events-none"
                                    title="Decline selected change"
                                    aria-label="Decline selected change"
                                    disabled
                                >
                                    <svg class="h-4.5 w-4.5" viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false">
                                        <path d="M8 4h8l5 5v13a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" stroke-linejoin="round" />
                                        <path d="M16 4v5h5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M11 15l6 6M17 15l-6 6" stroke-linecap="round" />
                                    </svg>
                                    <span class="sr-only">Decline change</span>
                                </button>
                            </span>
                            <span class="ql-formats">
                                <button
                                    type="button"
                                    id="quill2-accept-all"
                                    class="inline-flex items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2 text-emerald-700 shadow-sm transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:border-emerald-100 disabled:bg-emerald-50 disabled:text-emerald-300"
                                    title="Accept all pending changes"
                                    disabled
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6">
                                        <path d="M4 10l4 4 8-8" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <span class="sr-only">Accept all changes</span>
                                </button>
                                <button
                                    type="button"
                                    id="quill2-reject-all"
                                    class="inline-flex items-center justify-center rounded-full border border-rose-200 bg-rose-50 px-3 py-2 text-rose-700 shadow-sm transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:border-rose-100 disabled:bg-rose-50 disabled:text-rose-300"
                                    title="Decline all pending changes"
                                    disabled
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6">
                                        <path d="M5 5l10 10M15 5l-10 10" stroke-linecap="round" />
                                    </svg>
                                    <span class="sr-only">Decline all changes</span>
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

        <div
            id="quill2-paste-modal"
            class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-900/40 px-3 py-6 backdrop-blur"
            role="dialog"
            aria-modal="true"
            aria-labelledby="quill2-paste-title"
        >
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl ring-1 ring-black/10">
                <div class="flex flex-col gap-4 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.4em] text-slate-400">Manual Rich Paste</p>
                            <h2 id="quill2-paste-title" class="text-lg font-semibold text-slate-900">Paste clipboard content</h2>
                        </div>
                        <button
                            type="button"
                            id="quill2-paste-close"
                            class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            aria-label="Close"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 20 20" stroke="currentColor" fill="none" stroke-width="1.8">
                                <path d="M5 5l10 10M15 5l-10 10" stroke-linecap="round" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-slate-500">
                        Your browser blocked direct clipboard access. Press <span class="font-semibold">Ctrl / Cmd + V</span> inside the pad below to paste your content manually.
                    </p>
                    <div
                        id="quill2-paste-catcher"
                        class="min-h-[180px] rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 p-4 text-sm text-slate-700 focus:outline-none"
                        contenteditable="true"
                        role="textbox"
                        aria-multiline="true"
                        tabindex="0"
                    ></div>
                    <div class="flex justify-end">
                        <button
                            type="button"
                            id="quill2-paste-cancel"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:bg-slate-50"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
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
            if (typeof window !== 'undefined') {
                window.quill2Debug = window.quill2Debug || {};
                window.quill2Debug.initialDelta = initialDelta;
            }
            const initialChanges = @json($initialChanges ?? []);
            const initialComments = @json($initialComments ?? []);
            const initialHtml = @json($initialHtml ?? null);
                        if (typeof window !== 'undefined') {
                            window.quill2Debug = window.quill2Debug || {};
                            window.quill2Debug.initialHtml = initialHtml;
                        }
            const saveEndpoint = @json(route('quill2.save'));
            const deleteEndpoint = @json(route('quill2.destroy'));
            const currentUser = @json($quillUser ?? ['id' => 'guest-user', 'name' => 'Guest User']);
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
            if (typeof window !== 'undefined') {
                window.quill2Debug = window.quill2Debug || {};
                window.quill2Debug.quill = quill;
            }
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
            const pasteButton = document.getElementById('quill2-paste');
            const pasteTextButton = document.getElementById('quill2-paste-text');
            const commentModal = document.getElementById('quill2-comment-modal');
            const pasteModal = document.getElementById('quill2-paste-modal');
            const pasteCatcher = document.getElementById('quill2-paste-catcher');
            const pasteModalCancel = document.getElementById('quill2-paste-cancel');
            const pasteModalClose = document.getElementById('quill2-paste-close');
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

            const readClipboardHtml = async () => {
                if (!navigator.clipboard?.read) {
                    return '';
                }
                try {
                    const items = await navigator.clipboard.read();
                    for (let i = 0; i < items.length; i += 1) {
                        const item = items[i];
                        const typeList = item?.types ? Array.from(item.types) : [];
                        const hasHtml = typeList.includes('text/html');
                        if (!hasHtml) {
                            continue;
                        }
                        const blob = await item.getType('text/html');
                        const html = await blob.text();
                        if (html && html.trim().length) {
                            return html;
                        }
                    }
                } catch (error) {
                    return '';
                }
                return '';
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
            const changeFormatKey = tracker?._blotName || `${tracker.options.attrPrefix}-change`;
            const changeAttrKeys = [
                tracker.attrNames.id,
                tracker.attrNames.type,
                tracker.attrNames.status,
                tracker.attrNames.userId,
                tracker.attrNames.userName,
                tracker.attrNames.userEmail,
                tracker.attrNames.timestamp,
            ].filter(Boolean);
            let focusedChangeMeta = null;

            const stripTemporaryTags = (value) => {
                if (!value || typeof value !== 'string') {
                    return '';
                }
                return value
                    .replace(/<temporary[^>]*>/gi, '')
                    .replace(/<\/temporary>/gi, '');
            };

            const pruneTableUiArtifacts = (rootNode) => {
                if (!rootNode) {
                    return;
                }
                const docRef = rootNode.ownerDocument || document;
                const nodeFilter = docRef.defaultView?.NodeFilter || window.NodeFilter;
                const NodeCtor = docRef.defaultView?.Node || window.Node;
                const showElements = nodeFilter?.SHOW_ELEMENT ?? 1;
                const walker = docRef.createTreeWalker(rootNode, showElements);
                const removalQueue = [];
                while (walker.nextNode()) {
                    const node = walker.currentNode;
                    if (!node?.tagName) {
                        continue;
                    }
                    const tag = node.tagName.toLowerCase();
                    if (tag === 'temporary') {
                        removalQueue.push(node);
                        continue;
                    }
                    if (tag === 'table' && NodeCtor) {
                        const childNodes = Array.from(node.childNodes || []);
                        childNodes.forEach((child) => {
                            const isWhitespaceText = child.nodeType === NodeCtor.TEXT_NODE && !child.textContent.trim();
                            const isBreak = child.nodeType === NodeCtor.ELEMENT_NODE && child.tagName?.toLowerCase() === 'br';
                            if (isWhitespaceText || isBreak) {
                                child.remove();
                            }
                        });
                    }
                    if (node.classList?.contains('ql-cell-focused')) {
                        node.classList.remove('ql-cell-focused');
                        if (!node.classList.length) {
                            node.removeAttribute('class');
                        }
                    }
                }
                removalQueue.forEach((node) => {
                    const fragment = document.createDocumentFragment();
                    while (node.firstChild) {
                        fragment.appendChild(node.firstChild);
                    }
                    node.replaceWith(fragment);
                });
            };

            const pruneOrphanBreaks = (rootNode) => {
                if (!rootNode) {
                    return;
                }
                const docRef = rootNode.ownerDocument || document;
                const NodeCtor = docRef.defaultView?.Node || window.Node;
                if (!NodeCtor) {
                    return;
                }
                const isDisposable = (node) => {
                    if (!node) {
                        return false;
                    }
                    if (node.nodeType === NodeCtor.TEXT_NODE) {
                        return !node.textContent.trim();
                    }
                    if (node.nodeType === NodeCtor.ELEMENT_NODE && node.tagName?.toLowerCase() === 'br') {
                        return true;
                    }
                    return false;
                };
                while (isDisposable(rootNode.firstChild)) {
                    rootNode.firstChild.remove();
                }
                while (isDisposable(rootNode.lastChild)) {
                    rootNode.lastChild.remove();
                }
            };

            const sanitizeHydratedHtml = (html) => {
                if (!html || typeof html !== 'string') {
                    return '';
                }
                try {
                    const normalized = stripTemporaryTags(html);
                    const parser = new DOMParser();
                    const parsed = parser.parseFromString(normalized, 'text/html');
                    pruneTableUiArtifacts(parsed?.body);
                    pruneOrphanBreaks(parsed?.body);
                    return parsed?.body?.innerHTML ?? normalized;
                } catch (error) {
                    return html;
                }
            };

            if (typeof window !== 'undefined' && window.quill2Debug) {
                window.quill2Debug.sanitizeHydratedHtml = sanitizeHydratedHtml;
            }

            const serializeEditorHtml = () => {
                if (!quill?.root) {
                    return '';
                }
                const clone = quill.root.cloneNode(true);
                pruneTableUiArtifacts(clone);
                return clone.innerHTML;
            };

            if (typeof window !== 'undefined' && window.quill2Debug) {
                window.quill2Debug.serializeEditorHtml = serializeEditorHtml;
            }

            const hasStoredDelta = Boolean(initialDelta && Array.isArray(initialDelta.ops) && initialDelta.ops.length);
            const resolvedDelta = hasStoredDelta
                ? initialDelta
                : {
                    ops: [
                        {
                            insert: initialContent + (initialContent.endsWith('\n') ? '' : '\n'),
                        },
                    ],
                };
            if (initialHtml) {
                const hydratedHtml = sanitizeHydratedHtml(initialHtml);
                quill.clipboard.dangerouslyPasteHTML(0, hydratedHtml, 'silent');
            } else if (hasStoredDelta) {
                quill.setContents(resolvedDelta, silentSource);
            } else {
                quill.setContents(resolvedDelta, silentSource);
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
            const MANUAL_PASTE_CANCELLED = 'quill2-manual-paste-cancelled';

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
                const baseLabel = isSidePanelOpen
                    ? PANEL_TOGGLE_LABELS.open
                    : PANEL_TOGGLE_LABELS.closed;
                const commentCount = commentLog.length;
                togglePanelButton.textContent = commentCount > 0
                    ? `${baseLabel} (${commentCount})`
                    : baseLabel;
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
            const pluralizeWord = (count, singular, plural = `${singular}s`) => (count === 1 ? singular : plural);
            const describePendingSummary = (count) => `${count} pending ${pluralizeWord(count, 'change')}`;

            const resolveChangeMetaAtSelection = () => {
                const selection = quill.getSelection();
                if (!selection) {
                    return null;
                }
                const probes = [];
                if (selection.length && selection.length > 0) {
                    probes.push({ index: selection.index, length: selection.length });
                } else {
                    probes.push({ index: selection.index, length: 0 });
                    if (selection.index > 0) {
                        probes.push({ index: selection.index - 1, length: 1 });
                    }
                }
                for (let i = 0; i < probes.length; i += 1) {
                    const probe = probes[i];
                    const format = quill.getFormat(Math.max(0, probe.index), probe.length);
                    const meta = format?.[changeFormatKey];
                    if (!meta) {
                        continue;
                    }
                    const changeId = meta?.[tracker.attrNames.id];
                    if (changeId) {
                        return meta;
                    }
                }
                return null;
            };

            const describeChangeType = (meta) => (meta?.[tracker.attrNames.type] === 'delete' ? 'deletion' : 'insertion');

            const formatChangePreview = (value = '') => {
                const linebreakMarker = String.fromCharCode(0x23ce);
                return value.replace(/\n/g, linebreakMarker);
            };

            const collectTrackedChangesFromDom = () => {
                if (!quill?.root || !tracker?.attrNames) {
                    return [];
                }
                const selector = `[${tracker.attrNames.id}]`;
                const nodes = quill.root.querySelectorAll(selector);
                if (!nodes.length) {
                    return [];
                }
                const changeMap = new Map();
                nodes.forEach((node) => {
                    const changeId = node.getAttribute(tracker.attrNames.id);
                    if (!changeId || changeMap.has(changeId)) {
                        return;
                    }
                    const range = tracker.findChangeRange(changeId);
                    const snippet = (range && range.length)
                        ? quill.getText(range.index, range.length)
                        : (node.textContent || '');
                    const createdAt = node.getAttribute(tracker.attrNames.timestamp) || new Date().toISOString();
                    const userEmailAttr = tracker.attrNames.userEmail
                        ? node.getAttribute(tracker.attrNames.userEmail)
                        : null;
                    changeMap.set(changeId, {
                        id: changeId,
                        type: node.getAttribute(tracker.attrNames.type) === 'delete' ? 'delete' : 'insert',
                        status: node.getAttribute(tracker.attrNames.status) || 'pending',
                        preview: formatChangePreview(snippet),
                        createdAt,
                        updatedAt: createdAt,
                        length: range?.length ?? snippet.length,
                        user: {
                            id: node.getAttribute(tracker.attrNames.userId) || 'unknown-user',
                            name: node.getAttribute(tracker.attrNames.userName) || 'Unknown',
                            email: userEmailAttr || null,
                        },
                    });
                });
                return Array.from(changeMap.values());
            };

            const updateBulkChangeButtons = (pendingCountOverride = null) => {
                const pendingCount = Number.isFinite(pendingCountOverride)
                    ? pendingCountOverride
                    : countPendingChanges();
                const hasPending = pendingCount > 0;
                const summaryLabel = describePendingSummary(pendingCount);
                if (acceptAllButton) {
                    acceptAllButton.disabled = !hasPending;
                    acceptAllButton.setAttribute('aria-disabled', String(!hasPending));
                    acceptAllButton.setAttribute('title', hasPending ? `Accept ${summaryLabel}` : 'No pending changes to accept');
                }
                if (rejectAllButton) {
                    rejectAllButton.disabled = !hasPending;
                    rejectAllButton.setAttribute('aria-disabled', String(!hasPending));
                    rejectAllButton.setAttribute('title', hasPending ? `Decline ${summaryLabel}` : 'No pending changes to decline');
                }
            };

            const setChangeActionButtonState = (button, intent, descriptor, enabled) => {
                if (!button) {
                    return;
                }
                button.disabled = !enabled;
                button.setAttribute('aria-disabled', String(!enabled));
                const fallbackHint = intent === 'accept'
                    ? 'Place your cursor inside a tracked change to accept it'
                    : 'Place your cursor inside a tracked change to decline it';
                const actionVerb = intent === 'accept' ? 'Accept' : 'Decline';
                button.setAttribute('title', enabled ? `${actionVerb} this ${descriptor}` : fallbackHint);
            };

            const updateChangeActionButtons = (metaOverride = null) => {
                const meta = metaOverride || resolveChangeMetaAtSelection();
                const changeId = meta?.[tracker.attrNames.id] || null;
                const status = meta?.[tracker.attrNames.status];
                const descriptor = describeChangeType(meta) || 'change';
                const isPending = Boolean(changeId) && status === 'pending';
                focusedChangeMeta = isPending
                    ? {
                        id: changeId,
                        type: meta?.[tracker.attrNames.type] || 'insert',
                    }
                    : null;
                setChangeActionButtonState(acceptChangeButton, 'accept', descriptor, isPending);
                setChangeActionButtonState(rejectChangeButton, 'decline', descriptor, isPending);
            };

            const formatActivityTime = (isoString) => {
                if (!isoString) {
                    return '—';
                }
                return new Date(isoString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            };

            const buildActivityItems = () => commentLog
                .map((comment) => ({
                    type: 'comment',
                    id: comment.id,
                    createdAt: comment.createdAt ?? new Date().toISOString(),
                    payload: comment,
                }))
                .sort((a, b) => {
                    const aTime = new Date(a.createdAt).getTime();
                    const bTime = new Date(b.createdAt).getTime();
                    return aTime - bTime;
                });

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

            const captureTrackedSegments = (index, length) => {
                if (!Number.isFinite(index) || !Number.isFinite(length) || length <= 0) {
                    return [];
                }
                const delta = quill.getContents(index, length);
                const segments = [];
                let cursor = 0;
                (delta?.ops || []).forEach((op) => {
                    if (!Object.prototype.hasOwnProperty.call(op, 'insert')) {
                        cursor += typeof op.retain === 'number' ? op.retain : 0;
                        return;
                    }
                    const opLength = typeof op.insert === 'string'
                        ? op.insert.length
                        : 1;
                    const opAttrs = op.attributes || null;
                    const changeAttrs = (() => {
                        if (!opAttrs) {
                            return null;
                        }
                        if (opAttrs[changeFormatKey]?.[tracker.attrNames.id]) {
                            return opAttrs[changeFormatKey];
                        }
                        if (opAttrs[tracker.attrNames.id]) {
                            return opAttrs;
                        }
                        return null;
                    })();
                    if (changeAttrs?.[tracker.attrNames.id]) {
                        const preservedAttrs = {};
                        changeAttrKeys.forEach((key) => {
                            const value = changeAttrs[key];
                            if (value) {
                                preservedAttrs[key] = value;
                            }
                        });
                        if (opLength > 0 && Object.keys(preservedAttrs).length) {
                            segments.push({
                                index: index + cursor,
                                length: opLength,
                                meta: preservedAttrs,
                            });
                        }
                    }
                    cursor += opLength;
                });
                return segments;
            };

            const reapplyInsertStylesForSegments = (segments = []) => {
                if (!segments.length || !tracker || typeof tracker._ensureInsertStyle !== 'function') {
                    return;
                }
                const root = quill?.root;
                if (!root) {
                    return;
                }
                const insertMetaById = new Map();
                segments.forEach(({ meta }) => {
                    const changeId = meta?.[tracker.attrNames.id];
                    if (!changeId || meta?.[tracker.attrNames.type] !== 'insert') {
                        return;
                    }
                    if (!insertMetaById.has(changeId)) {
                        insertMetaById.set(changeId, meta);
                    }
                });
                if (!insertMetaById.size) {
                    return;
                }
                const candidates = root.querySelectorAll(`[${tracker.attrNames.id}]`);
                candidates.forEach((node) => {
                    const changeId = node.getAttribute(tracker.attrNames.id);
                    if (!insertMetaById.has(changeId)) {
                        return;
                    }
                    tracker._ensureInsertStyle(node, insertMetaById.get(changeId));
                });
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

            const captureManualRichPaste = () => new Promise((resolve, reject) => {
                if (!pasteModal || !pasteCatcher) {
                    reject(new Error('quill2-manual-paste-missing'));
                    return;
                }
                const cleanup = () => {
                    pasteModal.classList.add('hidden');
                    pasteModal.classList.remove('flex');
                    toggleBodyScroll(false);
                    pasteCatcher.innerHTML = '';
                    pasteCatcher.removeEventListener('paste', handlePaste);
                    pasteCatcher.removeEventListener('keydown', handleKeydown);
                    pasteModalCancel?.removeEventListener('click', handleCancel);
                    pasteModalClose?.removeEventListener('click', handleCancel);
                };
                const handleCancel = () => {
                    cleanup();
                    const error = new Error(MANUAL_PASTE_CANCELLED);
                    error.code = MANUAL_PASTE_CANCELLED;
                    reject(error);
                };
                const handleKeydown = (event) => {
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        handleCancel();
                    }
                };
                const handlePaste = (event) => {
                    event.preventDefault();
                    const html = event.clipboardData?.getData('text/html') || '';
                    const text = event.clipboardData?.getData('text/plain') || '';
                    cleanup();
                    resolve({ html, text });
                };
                pasteModal.classList.remove('hidden');
                pasteModal.classList.add('flex');
                toggleBodyScroll(true);
                pasteCatcher.innerHTML = '';
                window.requestAnimationFrame(() => pasteCatcher.focus());
                pasteCatcher.addEventListener('paste', handlePaste, { once: true });
                pasteCatcher.addEventListener('keydown', handleKeydown);
                pasteModalCancel?.addEventListener('click', handleCancel, { once: true });
                pasteModalClose?.addEventListener('click', handleCancel, { once: true });
            });

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
                const commentEntries = buildActivityItems();
                const pendingCount = countPendingChanges();
                updateBulkChangeButtons(pendingCount);
                if (activitySummaryEl) {
                    const totalCount = commentEntries.length;
                    const label = pluralizeWord(totalCount, 'comment');
                    activitySummaryEl.textContent = `${totalCount} ${label}`;
                }
                updatePanelToggleLabel();

                clearActivePanelHighlight();
                activityFeedEl.innerHTML = '';
                if (commentEntries.length) {
                    commentEntries.forEach((entry) => {
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
                                <span class="font-semibold text-gray-700">${authorName}</span>
                                <time datetime="${comment.createdAt ?? ''}">${formatActivityTime(comment.createdAt)}</time>
                            </div>
                            <p class="mt-2 text-sm text-gray-800">${body}</p>
                            </p>
                        `;
                        /* <p class="mt-2 text-xs text-gray-500">Excerpt: <span class="font-mono text-gray-700">${snippet}</span> */
                        activityFeedEl.appendChild(item);
                    });
                }

                if (historyCountEl) {
                    historyCountEl.textContent = '0';
                }
                if (historySection) {
                    historySection.classList.add('hidden');
                    historySection.open = false;
                }
                if (historyFeedEl) {
                    historyFeedEl.innerHTML = '';
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
            pasteButton?.addEventListener('click', () => pasteFromClipboard(false));
            pasteTextButton?.addEventListener('click', () => pasteFromClipboard(true));

            const toolbarModule = quill.getModule('toolbar');
            if (toolbarModule && typeof toolbarModule.addHandler === 'function') {
                const originalCleanHandler = typeof toolbarModule.handlers?.clean === 'function'
                    ? toolbarModule.handlers.clean.bind(toolbarModule)
                    : null;
                toolbarModule.addHandler('clean', (value) => {
                    const selection = quill.getSelection();
                    const effectiveRange = (() => {
                        if (!selection) {
                            const totalLength = quill.getLength();
                            return { index: 0, length: totalLength };
                        }
                        if (selection.length && selection.length > 0) {
                            return { index: selection.index, length: selection.length };
                        }
                        const docLength = quill.getLength();
                        const caretIndex = Math.max(0, Math.min(selection.index, Math.max(0, docLength - 1)));
                        const scopeLength = Math.max(0, Math.min(1, docLength - caretIndex));
                        return { index: caretIndex, length: scopeLength };
                    })();
                    const preservedSegments = captureTrackedSegments(
                        effectiveRange.index,
                        effectiveRange.length,
                    );
                    if (originalCleanHandler) {
                        originalCleanHandler(value);
                    } else if (selection) {
                        const { index, length } = effectiveRange;
                        if (length > 0) {
                            quill.removeFormat(index, length, userSource);
                        }
                    } else {
                        quill.removeFormat(0, quill.getLength(), userSource);
                    }
                    preservedSegments.forEach(({ index: segIndex, length: segLength, meta }) => {
                        if (segLength > 0 && meta?.[tracker.attrNames.id]) {
                            const formatPayload = { [changeFormatKey]: { ...meta } };
                            quill.formatText(segIndex, segLength, formatPayload, silentSource);
                        }
                    });
                    if (preservedSegments.length) {
                        reapplyInsertStylesForSegments(preservedSegments);
                        refreshChangeTooltips();
                        decorateTrackedTables();
                    }
                });
            }

            const handleFocusedChangeResolution = (action) => {
                const changeId = focusedChangeMeta?.id;
                if (!changeId) {
                    setStatus('Place your cursor inside a tracked change first.', 'error');
                    return;
                }
                const record = tracker.getChange(changeId);
                if (!record || record.status !== 'pending') {
                    focusedChangeMeta = null;
                    updateChangeActionButtons();
                    setStatus('This change has already been resolved.', 'error');
                    return;
                }
                if (action === 'accept') {
                    tracker.acceptChange(changeId);
                } else {
                    tracker.rejectChange(changeId);
                }
                const descriptor = record.type === 'delete' ? 'deletion' : 'insertion';
                const verb = action === 'accept' ? 'Accepted' : 'Declined';
                const tone = action === 'accept' ? 'success' : 'error';
                setStatus(`${verb} this ${descriptor}.`, tone);
                updateChangeActionButtons();
                finalizeHistoryBatch();
            };

            acceptChangeButton?.addEventListener('click', () => handleFocusedChangeResolution('accept'));
            rejectChangeButton?.addEventListener('click', () => handleFocusedChangeResolution('decline'));

            const handleBulkChangeResolution = (action) => {
                const pendingCount = countPendingChanges();
                if (!pendingCount) {
                    const actionLabel = action === 'accept' ? 'accept' : 'decline';
                    setStatus(`No pending changes to ${actionLabel}.`, 'error');
                    return;
                }
                if (action === 'accept') {
                    tracker.acceptAll();
                } else {
                    tracker.rejectAll();
                }
                const tone = action === 'accept' ? 'success' : 'error';
                const verb = action === 'accept' ? 'Accepted' : 'Declined';
                setStatus(`${verb} ${describePendingSummary(pendingCount)}.`, tone);
                finalizeHistoryBatch();
            };

            acceptAllButton?.addEventListener('click', () => handleBulkChangeResolution('accept'));
            rejectAllButton?.addEventListener('click', () => handleBulkChangeResolution('decline'));

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

            const TABLE_CHANGE_ATTR = 'data-q2-table-change';
            const tableChangeClasses = ['q2-table-change--insert', 'q2-table-change--delete'];

            const decorateTrackedTables = () => {
                if (!quill?.root) {
                    return;
                }
                const changeMap = new Map(tracker.getChanges({ sort: 'asc' }).map((change) => [change.id, change]));
                const tables = quill.root.querySelectorAll('table');
                tables.forEach((table) => {
                    table.removeAttribute(TABLE_CHANGE_ATTR);
                    tableChangeClasses.forEach((className) => table.classList.remove(className));
                    table.removeAttribute('title');
                    table.removeAttribute('data-q2-table-change-by');
                });
                const trackedNodes = quill.root.querySelectorAll('table [data-q2-change-id]');
                if (!trackedNodes.length) {
                    return;
                }
                trackedNodes.forEach((node) => {
                    const table = node.closest('table');
                    if (!table) {
                        return;
                    }
                    const changeType = node.getAttribute(tracker.attrNames.type);
                    const changeId = node.getAttribute(tracker.attrNames.id);
                    if (!changeType) {
                        return;
                    }
                    const normalizedType = changeType === 'delete' ? 'delete' : 'insert';
                    table.setAttribute(TABLE_CHANGE_ATTR, normalizedType);
                    table.classList.add(`q2-table-change--${normalizedType}`);
                    if (!changeId) {
                        return;
                    }
                    const changeMeta = changeMap.get(changeId);
                    const tooltip = describeChangeTooltip(changeMeta);
                    if (tooltip) {
                        table.setAttribute('title', tooltip);
                        if (changeMeta?.user?.name || changeMeta?.user?.email) {
                            const actor = changeMeta.user.name || changeMeta.user.email;
                            table.setAttribute('data-q2-table-change-by', actor);
                        }
                    }
                });
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

            const pruneResolvedChangeArtifacts = () => {
                if (!quill?.root || !tracker?.options?.attrPrefix) {
                    return;
                }
                const blotClass = `${tracker.options.attrPrefix}-change-inline`;
                const selector = `.${blotClass}:not([${tracker.attrNames.id}])`;
                const staleNodes = quill.root.querySelectorAll(selector);
                if (!staleNodes.length) {
                    return;
                }
                staleNodes.forEach((node) => {
                    node.removeAttribute('title');
                    node.classList.remove(blotClass);
                    if (!node.classList.length) {
                        node.removeAttribute('class');
                    }
                    if (!node.getAttribute('style')?.trim()) {
                        node.removeAttribute('style');
                    }
                    if (node.hasAttributes()) {
                        return;
                    }
                    const fragment = document.createDocumentFragment();
                    while (node.firstChild) {
                        fragment.appendChild(node.firstChild);
                    }
                    node.replaceWith(fragment);
                });
            };

            const finalizeHistoryBatch = () => {
                if (quill?.history?.cutoff) {
                    quill.history.cutoff();
                }
            };

            tracker.on('ledger-change', (changes) => {
                renderActivityFeed();
                renderHiddenSpans(changes);
                refreshChangeTooltips();
                decorateTrackedTables();
                pruneResolvedChangeArtifacts();
                updateChangeActionButtons();
                updateBulkChangeButtons();
            });

            const seedInitialChanges = () => {
                if (Array.isArray(initialChanges) && initialChanges.length) {
                    tracker.loadChanges(initialChanges);
                    return true;
                }
                const domHydrated = collectTrackedChangesFromDom();
                if (domHydrated.length) {
                    tracker.loadChanges(domHydrated);
                    return true;
                }
                return false;
            };

            if (!seedInitialChanges()) {
                renderActivityFeed();
                renderHiddenSpans(tracker.getChanges({ sort: 'asc' }));
            }

            refreshChangeTooltips();
            decorateTrackedTables();
            pruneResolvedChangeArtifacts();
            applyCommentHighlights();
            updateBulkChangeButtons();
            refreshPanelVisibility();
            const handleSelectionChange = () => {
                updateCommentButtonState();
                updateChangeActionButtons();
            };
            quill.on('selection-change', handleSelectionChange);
            handleSelectionChange();

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
            const BLOCKLIST_TAG_SET = new Set(BLOCKLIST_TAGS.map((tag) => tag.toLowerCase()));
            const GLOBAL_PASTE_ATTRS = new Set(['href', 'src', 'alt', 'title', 'colspan', 'rowspan', 'cellpadding', 'cellspacing', 'width', 'height', 'scope']);
            const TAG_PASTE_ATTRS = {
                a: new Set(['href', 'title', 'target', 'rel']),
                img: new Set(['src', 'alt', 'title', 'width', 'height']),
                td: new Set(['colspan', 'rowspan', 'width', 'height']),
                th: new Set(['colspan', 'rowspan', 'scope', 'width', 'height']),
                table: new Set(['border', 'cellpadding', 'cellspacing']),
                col: new Set(['span', 'width']),
                colgroup: new Set(['span']),
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

            const TABLE_STRUCTURE_TAGS = new Set(['table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'colgroup', 'col']);
            const SAFE_DATA_ATTR_PATTERN = /^data-[a-z0-9_\-:]+$/i;
            const CLASS_TOKEN_PATTERN = /^[a-z0-9_\-]+$/i;

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

            const sanitizeClassList = (value) => {
                if (!value || typeof value !== 'string') {
                    return '';
                }
                return value
                    .split(/\s+/)
                    .map((token) => token.trim())
                    .filter((token) => token && CLASS_TOKEN_PATTERN.test(token))
                    .join(' ');
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
                const showElementFlag = window.NodeFilter?.SHOW_ELEMENT ?? 1;
                const walker = parsed.createTreeWalker(parsed.body, showElementFlag, null);
                const pendingRemoval = [];
                while (walker.nextNode()) {
                    const node = walker.currentNode;
                    if (!node) {
                        continue;
                    }
                    const tag = node.tagName?.toLowerCase() ?? '';
                    if (!tag || BLOCKLIST_TAG_SET.has(tag) || tag.startsWith('o:')) {
                        pendingRemoval.push(node);
                        continue;
                    }
                    Array.from(node.attributes).forEach((attr) => {
                        const attrName = attr.name.toLowerCase();
                        if (attrName.startsWith('data-q2-')) {
                            return;
                        }
                        if (attrName.startsWith('data-')) {
                            if (SAFE_DATA_ATTR_PATTERN.test(attrName)) {
                                return;
                            }
                            node.removeAttribute(attr.name);
                            return;
                        }
                        if (attrName.startsWith('aria-')) {
                            return;
                        }
                        if (attrName === 'class') {
                            if (TABLE_STRUCTURE_TAGS.has(tag)) {
                                const sanitizedClasses = sanitizeClassList(attr.value);
                                if (sanitizedClasses) {
                                    node.setAttribute(attr.name, sanitizedClasses);
                                    return;
                                }
                            }
                            node.removeAttribute(attr.name);
                            return;
                        }
                        if (attrName === 'style' || attrName.startsWith('on')) {
                            node.removeAttribute(attr.name);
                            return;
                        }
                        const tagAllowList = TAG_PASTE_ATTRS[tag];
                        if (tagAllowList) {
                            if (tagAllowList.has(attrName) || GLOBAL_PASTE_ATTRS.has(attrName)) {
                                return;
                            }
                            node.removeAttribute(attr.name);
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

            const htmlToPlainText = (html = '') => {
                if (!html || typeof html !== 'string') {
                    return '';
                }
                try {
                    const parser = new DOMParser();
                    const parsed = parser.parseFromString(html, 'text/html');
                    return parsed?.body?.textContent?.trim() ?? '';
                } catch (error) {
                    return '';
                }
            };

            const insertSafeHtmlFragment = (safeHtml) => {
                if (!safeHtml || !safeHtml.trim().length) {
                    return false;
                }
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
                    decorateTrackedTables();
                });
                return true;
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
                    if (!insertSafeHtmlFragment(safeHtml)) {
                        setStatus('Paste failed. Please try again.', 'error');
                    }
                };

                quill.root.addEventListener('paste', (event) => {
                    handlePaste(event).catch((error) => {
                        console.error('Quill Lite paste handler error', error);
                        setStatus('Paste failed. Please try again.', 'error');
                    });
                }, { capture: true });
            };

            const pasteFromClipboard = async (preferPlainText = false) => {
                const resolveManualPayload = async () => {
                    if (!pasteModal || !pasteCatcher) {
                        throw new Error('quill2-manual-paste-unavailable');
                    }
                    return captureManualRichPaste();
                };

                try {
                    if (preferPlainText) {
                        let textPayload = navigator.clipboard?.readText ? await readClipboardPlainText() : '';
                        if (!textPayload || !textPayload.trim().length) {
                            const manualPayload = await resolveManualPayload();
                            textPayload = manualPayload.text;
                            if ((!textPayload || !textPayload.trim().length) && manualPayload.html) {
                                textPayload = htmlToPlainText(manualPayload.html);
                            }
                        }
                        if (!textPayload || !textPayload.trim().length) {
                            setStatus('Clipboard does not contain plain text to paste.', 'error');
                            return;
                        }
                        if (insertSafeHtmlFragment(plainTextToHtml(textPayload))) {
                            setStatus('Pasted as plain text.', 'success');
                        } else {
                            setStatus('Paste failed. Please try again.', 'error');
                        }
                        return;
                    }

                    let htmlPayload = navigator.clipboard?.read ? await readClipboardHtml() : '';
                    let textPayload = navigator.clipboard?.readText ? await readClipboardPlainText() : '';
                    if ((!htmlPayload || !htmlPayload.trim().length) && (!textPayload || !textPayload.trim().length)) {
                        const manualPayload = await resolveManualPayload();
                        htmlPayload = manualPayload.html || '';
                        textPayload = manualPayload.text || '';
                    }
                    if ((!htmlPayload || !htmlPayload.trim().length) && (!textPayload || !textPayload.trim().length)) {
                        setStatus('Clipboard is empty.', 'error');
                        return;
                    }
                    const safeHtml = composeSafeHtml(htmlPayload, textPayload);
                    if (!safeHtml) {
                        setStatus('Clipboard is empty.', 'error');
                        return;
                    }
                    if (insertSafeHtmlFragment(safeHtml)) {
                        setStatus('Pasted from clipboard.', 'success');
                    } else {
                        setStatus('Paste failed. Please try again.', 'error');
                    }
                } catch (error) {
                    if (error?.code === MANUAL_PASTE_CANCELLED || error?.message === MANUAL_PASTE_CANCELLED) {
                        setStatus('Paste cancelled.', 'muted');
                        return;
                    }
                    console.error('Clipboard read failed', error);
                    if (preferPlainText) {
                        setStatus('Clipboard access blocked. Allow permissions and try again.', 'error');
                        return;
                    }
                    try {
                        const manualPayload = await captureManualRichPaste();
                        const safeHtml = composeSafeHtml(manualPayload.html, manualPayload.text);
                        if (!safeHtml) {
                            setStatus('Clipboard is empty.', 'error');
                            return;
                        }
                        if (insertSafeHtmlFragment(safeHtml)) {
                            setStatus('Pasted from clipboard.', 'success');
                        } else {
                            setStatus('Paste failed. Please try again.', 'error');
                        }
                    } catch (manualError) {
                        if (manualError?.code === MANUAL_PASTE_CANCELLED || manualError?.message === MANUAL_PASTE_CANCELLED) {
                            setStatus('Paste cancelled.', 'muted');
                        } else {
                            setStatus('Clipboard access blocked. Allow permissions and try again.', 'error');
                        }
                    }
                }
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
            decorateTrackedTables();

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
                            html: serializeEditorHtml(),
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

        const startWhenQuillReady = (attempt = 0) => {
            if (window.Quill) {
                initQuillLite();
                return;
            }
            if (attempt > 200) {
                console.error('Quill never became available. Is Vite serving assets?');
                return;
            }
            window.setTimeout(() => startWhenQuillReady(attempt + 1), 50);
        };

        const bootQuillLite = () => startWhenQuillReady();

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootQuillLite, { once: true });
        } else {
            bootQuillLite();
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

        #quill2-root #quill2-editor table[data-q2-table-change] {
            position: relative;
            outline: 2px solid transparent;
            border-radius: 0.75rem;
        }

        #quill2-root #quill2-editor table.q2-table-change--insert {
            outline-color: var(--q2-insert-border, var(--q2-insert-border-default));
            box-shadow: 0 0 0 2px var(--q2-insert-bg, var(--q2-insert-bg-default));
        }

        #quill2-root #quill2-editor table.q2-table-change--insert::after,
        #quill2-root #quill2-editor table.q2-table-change--delete::after {
            content: attr(data-q2-table-change) ' table';
            position: absolute;
            top: -0.85rem;
            right: 0.5rem;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            padding: 0.15rem 0.55rem;
            border-radius: 999px;
            color: #0f172a;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid currentColor;
        }

        #quill2-root #quill2-editor table.q2-table-change--delete {
            outline-color: #fb7185;
            box-shadow: 0 0 0 2px rgba(251, 113, 133, 0.3);
        }

        #quill2-root #quill2-editor table.q2-table-change--delete::after {
            color: #9f1239;
            border-color: #fda4af;
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

        #quill2-root #quill2-editor[data-view-mode="clean"] table[data-q2-table-change] {
            position: static;
            outline: none;
            box-shadow: none;
            border-radius: 0;
        }

        #quill2-root #quill2-editor[data-view-mode="clean"] table[data-q2-table-change="delete"] {
            display: none;
        }

        #quill2-root #quill2-editor[data-view-mode="clean"] table[data-q2-table-change]::after {
            content: none;
            display: none;
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
