@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto py-8 space-y-6">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Quill Proof-of-Concept</p>
                <h2 class="text-2xl font-semibold text-gray-900">
                    {{ $document->title ?? 'Untitled Document' }}
                </h2>
            </div>
            <div class="text-sm text-gray-500">
                <span id="quill-last-saved">
                    Version {{ $document->version ?? 1 }} · {{ optional($document->updated_at)->diffForHumans() ?? 'never saved' }}
                </span>
            </div>
        </header>

        <div class="flex flex-wrap items-center gap-4 rounded-md border bg-white p-4 shadow-sm">
            <button
                type="button"
                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                id="quill-save-button"
            >
                Save
            </button>
            <div class="inline-flex overflow-hidden rounded-md border border-gray-200" role="group" aria-label="View mode toggle">
                <button
                    type="button"
                    id="quill-view-redline"
                    class="quill-view-toggle selected border-r border-gray-200 px-3 py-2 text-sm font-medium text-gray-700"
                >
                    Redline
                </button>
                <button
                    type="button"
                    id="quill-view-clean"
                    class="quill-view-toggle px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50"
                >
                    Clean
                </button>
            </div>
            <p id="quill-view-label" class="text-sm font-medium text-gray-500">
                View: Redline · edits enabled
            </p>
            <button
                type="button"
                class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                id="quill-add-comment"
            >
                Add Comment
            </button>
            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600">
                <label class="inline-flex items-center gap-1">
                    <input type="checkbox" class="comment-filter rounded border-gray-300" data-comment-filter="active" checked>
                    Active
                </label>
                <label class="inline-flex items-center gap-1">
                    <input type="checkbox" class="comment-filter rounded border-gray-300" data-comment-filter="resolved" checked>
                    Resolved
                </label>
                <label class="inline-flex items-center gap-1">
                    <input type="checkbox" class="comment-filter rounded border-gray-300" data-comment-filter="closed">
                    Closed
                </label>
            </div>
            <p id="quill-save-status" class="text-sm text-gray-500"></p>
        </div>

        <div class="space-y-4">
            <div id="quill-toolbar" class="rounded-md border bg-white p-3 shadow-sm">
                <div class="flex flex-wrap gap-2">
                    <span class="ql-formats">
                        <select class="ql-header">
                            <option selected></option>
                            <option value="1">H1</option>
                            <option value="2">H2</option>
                            <option value="3">H3</option>
                        </select>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-bold" type="button"></button>
                        <button class="ql-italic" type="button"></button>
                        <button class="ql-underline" type="button"></button>
                        <button class="ql-strike" type="button"></button>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-list" value="ordered" type="button"></button>
                        <button class="ql-list" value="bullet" type="button"></button>
                        <button class="ql-indent" value="-1" type="button"></button>
                        <button class="ql-indent" value="1" type="button"></button>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-link" type="button"></button>
                        <button class="ql-blockquote" type="button"></button>
                        <button class="ql-code-block" type="button"></button>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-clean" type="button"></button>
                    </span>
                    <span class="ql-formats flex items-center gap-1">
                        <button type="button" class="table-btn" data-table-action="insert-table">Table</button>
                        <button type="button" class="table-btn" data-table-action="insert-row">+Row</button>
                        <button type="button" class="table-btn" data-table-action="insert-column">+Col</button>
                        <button type="button" class="table-btn" data-table-action="delete-row">−Row</button>
                        <button type="button" class="table-btn" data-table-action="delete-column">−Col</button>
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
                <section class="relative lg:col-span-3">
                    <div
                        id="quill-editor"
                        data-document-id="{{ $document->id }}"
                        data-document-version="{{ $document->version ?? 1 }}"
                        class="min-h-[500px] rounded-md border bg-white"
                    ></div>
                    <div
                        id="change-bubble"
                        class="pointer-events-auto absolute z-20 hidden min-w-[200px] max-w-xs rounded-md border border-gray-200 bg-white p-3 text-sm shadow-lg"
                    >
                        <p id="change-bubble-meta" class="text-xs text-gray-500"></p>
                        <div class="mt-2 flex items-center gap-2">
                            <button
                                type="button"
                                id="change-accept"
                                class="rounded bg-emerald-600 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-500"
                            >
                                Accept
                            </button>
                            <button
                                type="button"
                                id="change-reject"
                                class="rounded bg-rose-600 px-3 py-1 text-xs font-semibold text-white hover:bg-rose-500"
                            >
                                Reject
                            </button>
                        </div>
                    </div>
                </section>

                <aside class="lg:col-span-1">
                    <div class="rounded-md border bg-white shadow-sm">
                        <header class="border-b px-4 py-3">
                            <h2 class="text-sm font-semibold text-gray-700">Comments</h2>
                        </header>
                        <div id="comment-list" class="divide-y"></div>
                        <div class="p-4 text-center text-sm text-gray-400" id="comment-empty-state">
                            No comments yet. Select text and click “Add Comment”.
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
@endsection

<div
    id="comment-modal"
    class="fixed inset-0 hidden items-center justify-center bg-black/40 px-4"
>
    <div class="max-w-md w-full rounded-lg bg-white p-6 shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Add Comment</h3>
            <button type="button" id="comment-modal-close" class="text-gray-500 hover:text-gray-700">×</button>
        </div>
        <form id="comment-form" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Comment</label>
                <textarea id="comment-body" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" rows="4" required></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" id="comment-modal-cancel" class="rounded border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save Comment</button>
            </div>
        </form>
    </div>
</div>

<script id="quill-comments-data" type="application/json">
    {!! $comments->toJson(JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>

@push('styles')
    <style>
        #quill-editor .ql-editor {
            min-height: 460px;
        }

        .ql-toolbar .ql-formats button {
            width: auto;
            min-width: 2.25rem;
        }

        .ql-comment {
            background-color: #fef3c7;
            border-bottom: 1px dashed #f59e0b;
        }

        .table-btn {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.125rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: #374151;
            transition: background-color 0.15s ease;
        }

        .table-btn:hover {
            background-color: #f9fafb;
        }

        #comment-modal.show {
            display: flex;
        }

        #quill-editor [data-tc-change-type="insert"] {
            background-color: #e0f2fe;
            border-bottom: 1px solid #38bdf8;
        }

        #quill-editor [data-tc-change-type="delete"] {
            background-color: #fee2e2;
            color: #b91c1c;
            text-decoration: line-through;
        }

        .quill-view-toggle.selected {
            background-color: #111827;
            color: #fff;
        }
    </style>
@endpush

@push('scripts')
    <script>
        const initQuillEditor = () => {
            const editorEl = document.getElementById('quill-editor');
            const saveButton = document.getElementById('quill-save-button');
            const saveStatus = document.getElementById('quill-save-status');
            const lastSavedEl = document.getElementById('quill-last-saved');
            const addCommentButton = document.getElementById('quill-add-comment');
            const commentListEl = document.getElementById('comment-list');
            const commentEmptyState = document.getElementById('comment-empty-state');
            const commentDataEl = document.getElementById('quill-comments-data');
            const commentModal = document.getElementById('comment-modal');
            const commentForm = document.getElementById('comment-form');
            const commentBodyInput = document.getElementById('comment-body');
            const commentModalClose = document.getElementById('comment-modal-close');
            const commentModalCancel = document.getElementById('comment-modal-cancel');
            const commentFilters = document.querySelectorAll('.comment-filter');
            const viewToggleRedline = document.getElementById('quill-view-redline');
            const viewToggleClean = document.getElementById('quill-view-clean');
            const changeBubble = document.getElementById('change-bubble');
            const changeBubbleMeta = document.getElementById('change-bubble-meta');
            const changeAcceptBtn = document.getElementById('change-accept');
            const changeRejectBtn = document.getElementById('change-reject');
            const viewLabel = document.getElementById('quill-view-label');

            const VIEW_STORAGE_KEY = 'quill-view-mode';

            if (!editorEl || !window.Quill) {
                return;
            }

            const Inline = window.Quill.import('blots/inline');

            class CommentBlot extends Inline {
                static create(value) {
                    const node = super.create();
                    node.setAttribute('data-comment-id', value);
                    node.classList.add('ql-comment');
                    return node;
                }

                static formats(node) {
                    return node.getAttribute('data-comment-id');
                }
            }

            CommentBlot.blotName = 'comment';
            CommentBlot.tagName = 'SPAN';
            window.Quill.register(CommentBlot);

            const registerTrackChangeAttributes = () => {
                const Parchment = window.Quill.import('parchment');
                const attributeNames = [
                    'tc-change-id',
                    'tc-change-type',
                    'tc-author-id',
                    'tc-author-name',
                    'tc-timestamp',
                ];

                attributeNames.forEach((name) => {
                    const attr = new Parchment.Attributor(name, `data-${name}`, {
                        scope: Parchment.Scope.INLINE,
                    });
                    window.Quill.register(attr, true);
                });
            };

            registerTrackChangeAttributes();

            const Delta = window.Quill.import('delta');
            const cloneDelta = (delta) => {
                try {
                    return JSON.parse(JSON.stringify(delta ?? { ops: [] }));
                } catch (_) {
                    return { ops: [] };
                }
            };

            const initialDelta = @json($contentDelta ?? ['ops' => []]);
            const initialCleanDelta = @json($cleanDelta ?? ['ops' => []]);
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const currentUser = @json(auth()->check() ? ['id' => auth()->id(), 'name' => auth()->user()->name] : null) || {};
            const documentId = Number(editorEl.dataset.documentId);
            let currentVersion = Number(editorEl.dataset.documentVersion || 1);
            let pendingAnchor = null;
            let lastSelection = null;
            let currentView = 'redline';
            let storedRedlineDelta = null;
            let storedCleanDelta = null;
            let activeChange = null;
            let bubbleSource = null;
            let hoveredChangeId = null;
            let bubblePinned = false;
            let comments = [];
            let bubbleHideTimeout = null;
            let isDirty = false;
            const CHANGE_GROUP_WINDOW_MS = 1500;
            const CHANGE_INSERT_IDLE_MS = CHANGE_GROUP_WINDOW_MS + 250;
            let lastInsertChange = null;
            const DEBUG_STORAGE_KEY = 'quillDebug';
            let DEBUG_TRACK_CHANGES = false;

            try {
                DEBUG_TRACK_CHANGES = window.localStorage?.getItem(DEBUG_STORAGE_KEY) === '1';
            } catch (_) {
                DEBUG_TRACK_CHANGES = false;
            }

            const debugTrack = (...messages) => {
                if (!DEBUG_TRACK_CHANGES) {
                    return;
                }
                console.debug('[QuillDebug]', ...messages);
            };

            window.toggleQuillDebug = (nextState = null) => {
                const resolved = nextState === null ? !DEBUG_TRACK_CHANGES : Boolean(nextState);
                DEBUG_TRACK_CHANGES = resolved;
                try {
                    window.localStorage?.setItem(DEBUG_STORAGE_KEY, resolved ? '1' : '0');
                } catch (_) {
                    // ignore storage errors
                }
                console.info(`[QuillDebug] ${resolved ? 'enabled' : 'disabled'}`);
                return resolved;
            };

            try {
                const savedView = window.localStorage?.getItem(VIEW_STORAGE_KEY);
                if (savedView === 'clean') {
                    currentView = 'clean';
                }
            } catch (_) {
                // ignore storage issues
            }

            const userMeta = {
                id: currentUser.id ?? 'user',
                name: currentUser.name ?? 'Unknown User',
            };

            try {
                const parsedComments = commentDataEl?.textContent ? JSON.parse(commentDataEl.textContent) : [];
                comments = Array.isArray(parsedComments)
                    ? parsedComments.map((comment) => ({
                        ...comment,
                        anchor_index: Number(comment.anchor_index ?? 0) || 0,
                        anchor_length: Number(comment.anchor_length ?? 0) || 0,
                    }))
                    : [];
            } catch (error) {
                console.error('Unable to parse comments JSON', error);
            }

            const modules = {
                toolbar: '#quill-toolbar',
                history: {
                    delay: 500,
                    maxStack: 100,
                    userOnly: true,
                },
                table: true,
            };

            const quill = new window.Quill('#quill-editor', {
                theme: 'snow',
                modules,
            });

            const bootDelta = initialDelta && initialDelta.ops
                ? cloneDelta(initialDelta)
                : { ops: [] };

            quill.setContents(bootDelta);
            storedRedlineDelta = cloneDelta(bootDelta);
            const bootCleanDelta = initialCleanDelta && initialCleanDelta.ops
                ? cloneDelta(initialCleanDelta)
                : buildCleanDelta(bootDelta);
            storedCleanDelta = bootCleanDelta;

            const tableModule = quill.getModule('table');
            const tableButtons = document.querySelectorAll('[data-table-action]');
            const tableActions = {
                'insert-table': () => tableModule?.insertTable?.(3, 3),
                'insert-row': () => tableModule?.insertRowBelow?.(),
                'insert-column': () => tableModule?.insertColumnRight?.(),
                'delete-row': () => tableModule?.deleteRow?.(),
                'delete-column': () => tableModule?.deleteColumn?.(),
            };

            tableButtons.forEach((btn) => {
                btn.addEventListener('click', (event) => {
                    event.preventDefault();
                    if (currentView !== 'redline') {
                        setStatus('Switch to Redline view to modify tables.', true);
                        return;
                    }
                    const action = btn.dataset.tableAction;
                    tableActions[action]?.();
                });
            });

            const setStatus = (message, isError = false) => {
                if (!saveStatus) {
                    return;
                }
                saveStatus.textContent = message;
                saveStatus.classList.toggle('text-red-600', isError);
                saveStatus.classList.toggle('text-gray-500', !isError);
            };

            const resetInsertGrouping = () => {
                lastInsertChange = null;
            };

            const acquireInsertChangeMeta = (cursor, length) => {
                const nowTs = Date.now();
                const canReuse = lastInsertChange
                    && cursor === lastInsertChange.endIndex
                    && (nowTs - lastInsertChange.updatedAt) <= CHANGE_GROUP_WINDOW_MS;

                if (canReuse) {
                    lastInsertChange.endIndex = cursor + length;
                    lastInsertChange.updatedAt = nowTs;
                    return {
                        id: lastInsertChange.id,
                        timestamp: lastInsertChange.timestamp,
                        startIndex: lastInsertChange.startIndex,
                        endIndex: lastInsertChange.endIndex,
                        length: Math.max(0, lastInsertChange.endIndex - lastInsertChange.startIndex),
                        isNew: false,
                    };
                }

                const descriptor = {
                    id: generateChangeId(),
                    timestamp: new Date().toISOString(),
                    startIndex: cursor,
                    endIndex: cursor + length,
                    updatedAt: nowTs,
                };

                lastInsertChange = descriptor;

                return {
                    id: descriptor.id,
                    timestamp: descriptor.timestamp,
                    startIndex: descriptor.startIndex,
                    endIndex: descriptor.endIndex,
                    length: Math.max(0, descriptor.endIndex - descriptor.startIndex),
                    isNew: true,
                };
            };

            const continueTrackedInsertMeta = (cursor, length, attributes = null) => {
                const changeId = attributes?.['tc-change-id'];
                if (!changeId || !lastInsertChange || changeId !== lastInsertChange.id) {
                    return null;
                }

                const nowTs = Date.now();
                const isWithinWindow = (nowTs - lastInsertChange.updatedAt) <= CHANGE_GROUP_WINDOW_MS;
                const isAtTail = cursor === lastInsertChange.endIndex;

                if (!isWithinWindow || !isAtTail) {
                    return null;
                }

                lastInsertChange.endIndex = cursor + length;
                lastInsertChange.updatedAt = nowTs;

                return {
                    id: changeId,
                    timestamp: attributes?.['tc-timestamp'] || lastInsertChange.timestamp,
                    startIndex: lastInsertChange.startIndex,
                    endIndex: lastInsertChange.endIndex,
                    length: Math.max(0, lastInsertChange.endIndex - lastInsertChange.startIndex),
                    isNew: false,
                };
            };

            const splitInsertSegments = (text) => {
                if (typeof text !== 'string' || text.indexOf('\n') === -1) {
                    return [text];
                }

                const segments = [];
                let buffer = '';

                for (let i = 0; i < text.length; i += 1) {
                    const char = text[i];
                    if (char === '\n') {
                        if (buffer) {
                            segments.push(buffer);
                            buffer = '';
                        }
                        segments.push('\n');
                        continue;
                    }
                    buffer += char;
                }

                if (buffer) {
                    segments.push(buffer);
                }

                return segments;
            };

            const markDirty = () => {
                if (isDirty) {
                    return;
                }
                isDirty = true;
                if (saveStatus && !saveStatus.classList.contains('text-red-600')) {
                    setStatus('Unsaved changes');
                }
            };

            const clearDirtyState = (message = null) => {
                isDirty = false;
                if (message) {
                    setStatus(message);
                }
            };

            const handleBeforeUnload = (event) => {
                if (!isDirty) {
                    return;
                }
                event.preventDefault();
                event.returnValue = '';
            };

            window.addEventListener('beforeunload', handleBeforeUnload);

            const updateLastSaved = (isoString, version) => {
                if (!lastSavedEl) {
                    return;
                }
                const savedDate = isoString ? new Date(isoString) : new Date();
                const time = savedDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                lastSavedEl.textContent = `Version ${version} · Saved at ${time}`;
            };

            const findChangeRange = (changeId) => {
                if (!changeId) {
                    return null;
                }
                const contents = quill.getContents();
                const ops = contents?.ops ?? [];
                let cursor = 0;
                let start = null;
                let length = 0;

                for (const op of ops) {
                    if (!op.insert) {
                        continue;
                    }
                    const opLength = typeof op.insert === 'string' ? op.insert.length : 1;
                    if (op.attributes?.['tc-change-id'] === changeId) {
                        if (start === null) {
                            start = cursor;
                            length = 0;
                        }
                        length += opLength;
                    } else if (start !== null) {
                        break;
                    }
                    cursor += opLength;
                }

                if (start === null) {
                    return null;
                }

                return { index: start, length };
            };

            const clearBubbleHideTimeout = () => {
                if (bubbleHideTimeout) {
                    clearTimeout(bubbleHideTimeout);
                    bubbleHideTimeout = null;
                }
            };

            const hideChangeBubble = () => {
                clearBubbleHideTimeout();
                changeBubble?.classList.add('hidden');
                activeChange = null;
                bubbleSource = null;
                hoveredChangeId = null;
                bubblePinned = false;
            };

            const showChangeBubble = (payload, origin = 'selection') => {
                if (!changeBubble || !payload) {
                    return;
                }

                clearBubbleHideTimeout();

                const { bounds, type, author, timestamp, id, range } = payload;
                changeBubble.style.left = `${Math.max(bounds.left - 10, 0)}px`;
                changeBubble.style.top = `${bounds.bottom + 8}px`;
                const readableDate = timestamp ? new Date(timestamp).toLocaleString() : 'Just now';
                changeBubbleMeta.textContent = `${type === 'delete' ? 'Deletion' : 'Insertion'} by ${author ?? 'Unknown User'} · ${readableDate}`;
                changeBubble.classList.remove('hidden');
                bubbleSource = origin;
                hoveredChangeId = origin === 'hover' ? id : null;
                activeChange = {
                    id,
                    type,
                    range,
                };
            };

            const getNodeBoundsRelativeToEditor = (node) => {
                if (!node?.getBoundingClientRect || !editorEl?.getBoundingClientRect) {
                    return null;
                }
                const nodeRect = node.getBoundingClientRect();
                const editorRect = editorEl.getBoundingClientRect();
                return {
                    left: nodeRect.left - editorRect.left,
                    right: nodeRect.right - editorRect.left,
                    top: nodeRect.top - editorRect.top,
                    bottom: nodeRect.bottom - editorRect.top,
                };
            };

            const buildChangePayloadFromNode = (node) => {
                if (!node) {
                    return null;
                }

                const changeId = node.getAttribute('data-tc-change-id');
                if (!changeId) {
                    return null;
                }

                const changeRange = findChangeRange(changeId);
                let bounds = null;

                if (changeRange) {
                    try {
                        bounds = quill.getBounds(changeRange.index, changeRange.length || 1);
                    } catch (_) {
                        bounds = null;
                    }
                }

                if (!bounds) {
                    bounds = getNodeBoundsRelativeToEditor(node);
                }

                if (!bounds) {
                    return null;
                }

                return {
                    id: changeId,
                    type: node.getAttribute('data-tc-change-type'),
                    author: node.getAttribute('data-tc-author-name'),
                    timestamp: node.getAttribute('data-tc-timestamp'),
                    bounds,
                    range: changeRange,
                };
            };

            const maybeHideHoverBubble = (immediate = false) => {
                if (bubbleSource !== 'hover' || bubblePinned) {
                    return;
                }

                if (immediate) {
                    hideChangeBubble();
                    return;
                }

                clearBubbleHideTimeout();
                bubbleHideTimeout = window.setTimeout(() => {
                    hideChangeBubble();
                }, 150);
            };

            const buildCleanDelta = (rawDelta) => {
                const ops = [];
                const sourceOps = rawDelta?.ops ?? [];

                sourceOps.forEach((op) => {
                    if (!op.insert) {
                        return;
                    }
                    const nextAttributes = { ...(op.attributes || {}) };
                    if (nextAttributes['tc-change-type'] === 'delete') {
                        return;
                    }

                    delete nextAttributes['tc-change-id'];
                    delete nextAttributes['tc-change-type'];
                    delete nextAttributes['tc-author-id'];
                    delete nextAttributes['tc-author-name'];
                    delete nextAttributes['tc-timestamp'];
                    delete nextAttributes['comment'];

                    if (Object.keys(nextAttributes).length > 0) {
                        ops.push({ insert: op.insert, attributes: nextAttributes });
                    } else {
                        ops.push({ insert: op.insert });
                    }
                });

                return { ops };
            };



            const saveEndpoint = '{{ route('quill.save') }}';
            const commentRoutes = {
                store: '{{ route('quill.comments.store') }}',
                update: '{{ url('/quill/comments') }}',
            };
            const changeRoutes = {
                store: '{{ route('quill.changes.store') }}',
                base: '{{ url('/quill/changes') }}',
            };
            const CHANGE_PERSIST_DEBOUNCE_MS = 250;
            const changePersistQueue = new Map();
            const changePersistInflight = new Map();
            const changeBuffers = new Map();
            const mergePendingChange = (existing, incoming) => {
                debugTrack('merge-change-payload', { existing, incoming });
                if (!existing) {
                    return incoming;
                }

                const merged = { ...existing };

                if (typeof incoming.dispatchDelayMs === 'number') {
                    merged.dispatchDelayMs = incoming.dispatchDelayMs;
                }

                if (typeof incoming.anchor_index === 'number') {
                    if (typeof merged.anchor_index === 'number') {
                        merged.anchor_index = Math.min(merged.anchor_index, incoming.anchor_index);
                    } else {
                        merged.anchor_index = incoming.anchor_index;
                    }
                }

                if (incoming.delta?.ops?.length) {
                    const existingOps = merged.delta?.ops ? merged.delta.ops.map((op) => ({ ...op })) : [];
                    const incomingOps = incoming.delta.ops.map((op) => ({ ...op }));
                    merged.delta = { ops: existingOps.concat(incomingOps) };
                    merged.requiresSnapshot = false;
                    merged.anchor_length = measureDeltaLength(merged.delta);
                } else if (typeof incoming.anchor_length === 'number' && !merged.delta) {
                    merged.anchor_length = incoming.anchor_length;
                }

                merged.change_uuid = incoming.change_uuid;
                merged.change_type = incoming.change_type;
                merged.requiresSnapshot = merged.requiresSnapshot && !merged.delta;

                debugTrack('merged-change-payload', merged);
                return merged;
            };

            const normalizePlainDelta = (delta) => {
                if (!delta) {
                    return null;
                }

                if (typeof delta.ops === 'undefined' && typeof delta === 'object' && Array.isArray(delta)) {
                    return { ops: delta };
                }

                return toPlainDelta(delta);
            };

            const appendInsertBuffer = (changeId, deltaFragment, anchorRange) => {
                const fragment = normalizePlainDelta(deltaFragment);

                if (!fragment || !Array.isArray(fragment.ops) || fragment.ops.length === 0) {
                    return null;
                }

                const existing = changeBuffers.get(changeId) || {
                    delta: new Delta(),
                    anchorIndex: typeof anchorRange?.index === 'number' ? anchorRange.index : 0,
                    anchorLength: 0,
                };

                existing.delta = existing.delta.concat(fragment);

                if (typeof anchorRange?.index === 'number') {
                    existing.anchorIndex = Math.min(existing.anchorIndex, anchorRange.index);
                }

                const fragmentLength = measureDeltaLength(fragment);
                const rangeLength = Number.isFinite(anchorRange?.length) ? anchorRange.length : fragmentLength;
                const fragmentEnd = (anchorRange?.index ?? existing.anchorIndex) + rangeLength;
                const currentEnd = existing.anchorIndex + existing.anchorLength;
                existing.anchorLength = Math.max(currentEnd, fragmentEnd) - existing.anchorIndex;

                changeBuffers.set(changeId, existing);

                return {
                    delta: toPlainDelta(existing.delta),
                    anchor_index: existing.anchorIndex,
                    anchor_length: existing.anchorLength,
                };
            };

            const buildChangePersistPayload = (pending) => {
                if (!pending || !pending.change_uuid || !pending.change_type) {
                    debugTrack('skip-persist-build', pending);
                    return null;
                }

                const anchorRangeHint = Number.isFinite(pending.anchor_index) && Number.isFinite(pending.anchor_length)
                    ? { index: pending.anchor_index, length: pending.anchor_length }
                    : null;

                let anchorIndex = Number.isFinite(pending.anchor_index) ? pending.anchor_index : null;
                let anchorLength = Number.isFinite(pending.anchor_length) ? pending.anchor_length : null;
                let resolvedDelta = pending.delta ? toPlainDelta(pending.delta) : null;
                let anchorText = null;

                const resolveSnapshot = () => {
                    const liveRange = findChangeRange(pending.change_uuid);

                    if (!liveRange && !anchorRangeHint) {
                        return null;
                    }

                    if (!liveRange) {
                        return snapshotChangeDelta(null, anchorRangeHint);
                    }

                    if (!anchorRangeHint) {
                        return snapshotChangeDelta(pending.change_uuid, liveRange);
                    }

                    const combinedIndex = Math.min(anchorRangeHint.index, liveRange.index);
                    const combinedEnd = Math.max(
                        anchorRangeHint.index + anchorRangeHint.length,
                        liveRange.index + liveRange.length,
                    );
                    const combinedRange = {
                        index: combinedIndex,
                        length: Math.max(0, combinedEnd - combinedIndex),
                    };

                    return snapshotChangeDelta(pending.change_uuid, combinedRange);
                };

                const needsSnapshot = pending.requiresSnapshot || !resolvedDelta?.ops?.length;

                if (needsSnapshot) {
                    const snapshot = resolveSnapshot();

                    if (!snapshot?.delta?.ops?.length) {
                        if (!resolvedDelta?.ops?.length) {
                            debugTrack('snapshot-empty', { pending, anchorRangeHint });
                            return null;
                        }
                    } else {
                        resolvedDelta = snapshot.delta;
                        anchorIndex = snapshot.index;
                        anchorLength = snapshot.length;
                        anchorText = snapshot.text ?? null;
                    }
                }

                const finalAnchorIndex = Number.isFinite(anchorIndex) ? Math.max(0, anchorIndex) : 0;
                const finalAnchorLength = Number.isFinite(anchorLength)
                    ? Math.max(0, anchorLength)
                    : measureDeltaLength(resolvedDelta);

                if (!anchorText && Number.isFinite(finalAnchorIndex) && Number.isFinite(finalAnchorLength)) {
                    try {
                        anchorText = quill.getText(finalAnchorIndex, finalAnchorLength);
                    } catch (_) {
                        anchorText = null;
                    }
                }

                return {
                    document_id: documentId,
                    change_uuid: pending.change_uuid,
                    change_type: pending.change_type,
                    delta: { ops: resolvedDelta.ops.map((op) => ({ ...op })) },
                    anchor_index: finalAnchorIndex,
                    anchor_length: finalAnchorLength,
                    anchor_text: anchorText,
                };
            };

            const dispatchChangePersist = (pending) => {
                const payload = buildChangePersistPayload(pending);

                if (!payload) {
                    return Promise.resolve();
                }

                debugTrack('dispatch-change', payload);

                const request = fetch(changeRoutes.store, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                }).catch((error) => {
                    console.error('Unable to persist change', error);
                });

                const tracked = request.finally(() => {
                    changePersistInflight.delete(payload.change_uuid);
                    changeBuffers.delete(payload.change_uuid);
                });

                changePersistInflight.set(payload.change_uuid, tracked);
                return tracked;
            };

            const queueChangePersist = (pending) => {
                if (!pending?.change_uuid) {
                    return;
                }

                const existing = changePersistQueue.get(pending.change_uuid);
                if (existing?.timeoutId) {
                    clearTimeout(existing.timeoutId);
                }

                const mergedPending = mergePendingChange(existing?.pending, pending);
                const delay = Number.isFinite(mergedPending.dispatchDelayMs)
                    ? mergedPending.dispatchDelayMs
                    : CHANGE_PERSIST_DEBOUNCE_MS;

                const timeoutId = window.setTimeout(() => {
                    changePersistQueue.delete(mergedPending.change_uuid);
                    debugTrack('queue-dispatch', mergedPending);
                    dispatchChangePersist(mergedPending);
                }, delay);

                debugTrack('queue-change', mergedPending);
                changePersistQueue.set(mergedPending.change_uuid, { timeoutId, pending: mergedPending, delay });
            };

            const flushPendingChangePersists = async () => {
                const queued = Array.from(changePersistQueue.values());
                changePersistQueue.clear();

                debugTrack('flush-pending-changes', {
                    queued: queued.length,
                    inflight: changePersistInflight.size,
                });

                queued.forEach(({ timeoutId, pending }) => {
                    if (timeoutId) {
                        clearTimeout(timeoutId);
                    }
                    debugTrack('flush-dispatch', pending);
                    dispatchChangePersist(pending);
                });

                if (changePersistInflight.size === 0) {
                    return;
                }

                await Promise.allSettled(changePersistInflight.values());
            };

            const filterState = {
                active: true,
                resolved: true,
                closed: false,
            };

            const renderComments = () => {
                if (!commentListEl || !commentEmptyState) {
                    return;
                }

                const visible = comments.filter((comment) => filterState[comment.status]);

                if (visible.length === 0) {
                    commentEmptyState.classList.remove('hidden');
                    commentListEl.innerHTML = '';
                    return;
                }

                commentEmptyState.classList.add('hidden');

                commentListEl.innerHTML = visible.map((comment) => `
                    <article
                        class="px-4 py-3 text-sm text-gray-700 cursor-pointer hover:bg-gray-50"
                        data-comment-entry
                        data-comment-id="${comment.id}"
                        data-anchor-index="${comment.anchor_index}"
                        data-anchor-length="${comment.anchor_length}"
                    >
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>${comment.user_name ?? 'Unknown User'}</span>
                            <select
                                class="rounded border border-gray-300 bg-white text-xs text-gray-600"
                                data-comment-status
                                data-comment-id="${comment.id}"
                            >
                                ${['active', 'resolved', 'closed'].map((status) => `
                                    <option value="${status}" ${comment.status === status ? 'selected' : ''}>
                                        ${status.charAt(0).toUpperCase() + status.slice(1)}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                        <p class="mt-2 text-gray-800">${comment.body}</p>
                        <p class="mt-1 text-xs text-gray-400">
                            Anchored @ ${comment.anchor_index} (${comment.anchor_length} chars)
                        </p>
                    </article>
                `).join('');
            };

            const clearCommentFormat = (comment) => {
                if (!comment) {
                    return;
                }

                if (typeof comment.anchor_index !== 'number' || typeof comment.anchor_length !== 'number') {
                    return;
                }

                quill.formatText(comment.anchor_index, comment.anchor_length, 'comment', false, window.Quill.sources.SILENT);
            };

            const applyCommentFormat = (comment) => {
                if (!comment || comment.status !== 'active') {
                    return;
                }

                if (typeof comment.anchor_index !== 'number' || typeof comment.anchor_length !== 'number') {
                    return;
                }
                quill.formatText(comment.anchor_index, comment.anchor_length, 'comment', comment.id, window.Quill.sources.SILENT);
            };

            const buildCommentRangeMap = (delta) => {
                const ranges = {};
                const ops = delta?.ops ?? [];
                let cursor = 0;

                ops.forEach((op) => {
                    if (typeof op.retain === 'number') {
                        cursor += op.retain;
                        return;
                    }

                    if (typeof op.insert === 'undefined') {
                        return;
                    }

                    const length = typeof op.insert === 'string'
                        ? op.insert.length
                        : 1;

                    if (length === 0) {
                        return;
                    }

                    const commentId = op.attributes?.comment;

                    if (commentId) {
                        if (!ranges[commentId]) {
                            ranges[commentId] = {
                                index: cursor,
                                length: 0,
                            };
                        }
                        ranges[commentId].length += length;
                    }

                    cursor += length;
                });

                return ranges;
            };

            const measureDeltaLength = (delta) => {
                const ops = delta?.ops ?? [];
                let total = 0;
                ops.forEach((op) => {
                    if (typeof op.insert === 'string') {
                        total += op.insert.length;
                    } else if (typeof op.insert !== 'undefined') {
                        total += 1;
                    } else if (typeof op.retain === 'number') {
                        total += op.retain;
                    }
                });
                return total;
            };

            const updateViewToggle = () => {
                document.querySelectorAll('.quill-view-toggle').forEach((btn) => {
                    btn.classList.toggle('selected', btn.id === `quill-view-${currentView}`);
                });
            };

            const updateTableButtonsState = () => {
                const isRedline = currentView === 'redline';
                tableButtons.forEach((btn) => {
                    btn.disabled = !isRedline;
                    btn.classList.toggle('opacity-50', !isRedline);
                });
            };

            const updateViewLabel = () => {
                if (!viewLabel) {
                    return;
                }
                if (currentView === 'clean') {
                    viewLabel.textContent = 'View: Clean · read-only snapshot';
                } else {
                    viewLabel.textContent = 'View: Redline · edits enabled';
                }
            };

            const persistViewPreference = (view) => {
                try {
                    window.localStorage?.setItem(VIEW_STORAGE_KEY, view);
                } catch (_) {
                    // ignore storage errors
                }
            };

            const renderEditorFromState = () => {
                if (!storedRedlineDelta) {
                    storedRedlineDelta = cloneDelta(quill.getContents());
                }

                const isCleanView = currentView === 'clean';
                const sourceDelta = cloneDelta(storedRedlineDelta);
                let targetDelta;

                if (isCleanView) {
                    const preferredClean = !isDirty && storedCleanDelta?.ops
                        ? cloneDelta(storedCleanDelta)
                        : buildCleanDelta(sourceDelta);
                    targetDelta = preferredClean;
                } else {
                    targetDelta = sourceDelta;
                }

                quill.setContents(targetDelta, window.Quill.sources.SILENT);
                quill.enable(!isCleanView);
                comments.forEach(applyCommentFormat);
                updateViewToggle();
                updateTableButtonsState();
                updateViewLabel();

                if (isCleanView) {
                    hideChangeBubble();
                } else {
                    storedRedlineDelta = cloneDelta(quill.getContents());
                }
            };

            const applyView = (view) => {
                if (view === currentView) {
                    return;
                }

                currentView = view;
                persistViewPreference(view);
                renderEditorFromState();
            };

            renderComments();

            commentFilters.forEach((checkbox) => {
                const status = checkbox.dataset.commentFilter;
                if (status in filterState) {
                    filterState[status] = checkbox.checked;
                }

                checkbox.addEventListener('change', () => {
                    filterState[status] = checkbox.checked;
                    renderComments();
                });
            });

            const openCommentModal = () => {
                if (!commentModal) return;
                commentModal.classList.add('show');
                commentBodyInput?.focus();
            };

            const closeCommentModal = () => {
                pendingAnchor = null;
                commentBodyInput.value = '';
                commentModal?.classList.remove('show');
            };

            const toPlainDelta = (delta) => {
                if (!delta || !Array.isArray(delta.ops)) {
                    return null;
                }

                return {
                    ops: delta.ops.map((op) => ({ ...op })),
                };
            };

            const snapshotChangeDelta = (changeId, rangeOverride = null) => {
                if (!changeId && !rangeOverride) {
                    return null;
                }

                const range = rangeOverride || findChangeRange(changeId);

                if (!range || range.length <= 0) {
                    return null;
                }

                try {
                    return {
                        delta: toPlainDelta(quill.getContents(range.index, range.length)),
                        index: range.index,
                        length: range.length,
                        text: quill.getText(range.index, range.length),
                    };
                } catch (error) {
                    console.warn('Unable to snapshot change delta', error);
                    return null;
                }
            };

            const persistChangeRecord = (changeId, changeType, deltaOverride = null, anchorRange = null) => {
                if (!documentId || !changeId) {
                    return;
                }

                const anchorIndex = Number(anchorRange?.index);
                const anchorLength = Number(anchorRange?.length);

                const pending = {
                    change_uuid: changeId,
                    change_type: changeType,
                    delta: deltaOverride ? toPlainDelta(deltaOverride) : null,
                    anchor_index: Number.isFinite(anchorIndex) ? Math.max(0, anchorIndex) : null,
                    anchor_length: Number.isFinite(anchorLength)
                        ? Math.max(0, anchorLength)
                        : null,
                    requiresSnapshot: !deltaOverride,
                    dispatchDelayMs: changeType === 'insert'
                        ? CHANGE_INSERT_IDLE_MS
                        : CHANGE_PERSIST_DEBOUNCE_MS,
                };

                if (changeType === 'insert' && deltaOverride) {
                    const buffered = appendInsertBuffer(changeId, deltaOverride, anchorRange);
                    if (buffered) {
                        pending.anchor_index = buffered.anchor_index;
                        pending.anchor_length = buffered.anchor_length;
                    }
                    pending.delta = null;
                    pending.requiresSnapshot = true;
                }

                debugTrack('persist-change-record', pending);
                queueChangePersist(pending);
            };

            const generateChangeId = () => {
                if (window.crypto?.randomUUID) {
                    return window.crypto.randomUUID();
                }
                return `tc-${Date.now()}-${Math.floor(Math.random() * 1e6)}`;
            };

            const trackChangeAttributes = (changeId, type, timestamp) => ({
                'tc-change-id': changeId,
                'tc-change-type': type,
                'tc-author-id': userMeta.id,
                'tc-author-name': userMeta.name,
                'tc-timestamp': timestamp,
            });

            const handleTrackChanges = (delta, oldDelta) => {
                let cursor = 0;

                delta.ops.forEach((op) => {
                    if (typeof op.retain === 'number') {
                        cursor += op.retain;
                        return;
                    }

                    if (op.insert) {
                        const processSegment = (segment, forceNewChange = false) => {
                            const segmentLength = typeof segment === 'string' ? segment.length : 1;

                            if (segmentLength === 0) {
                                return;
                            }

                            const continuationMeta = forceNewChange
                                ? null
                                : continueTrackedInsertMeta(cursor, segmentLength, op.attributes);

                            if (continuationMeta) {
                                const anchorRange = {
                                    index: continuationMeta.startIndex ?? cursor,
                                    length: continuationMeta.length || segmentLength,
                                };
                                const insertedDelta = new Delta().insert(segment, op.attributes ? { ...op.attributes } : undefined);
                                debugTrack('insert-change', { cursor, length: segmentLength, anchorRange, changeMeta: continuationMeta, segment, op });
                                persistChangeRecord(continuationMeta.id, 'insert', insertedDelta, anchorRange);
                                cursor += segmentLength;
                                return;
                            }

                            const changeMeta = acquireInsertChangeMeta(cursor, segmentLength);
                            const attrs = trackChangeAttributes(changeMeta.id, 'insert', changeMeta.timestamp);
                            const anchorRange = {
                                index: changeMeta.startIndex ?? cursor,
                                length: changeMeta.length || segmentLength,
                            };
                            const insertedDelta = new Delta().insert(segment, op.attributes ? { ...op.attributes } : undefined);
                            debugTrack('insert-change', { cursor, length: segmentLength, anchorRange, changeMeta, segment, op });
                            quill.formatText(cursor, segmentLength, attrs, window.Quill.sources.SILENT);
                            persistChangeRecord(changeMeta.id, 'insert', insertedDelta, anchorRange);
                            cursor += segmentLength;
                        };

                        const segments = typeof op.insert === 'string'
                            ? splitInsertSegments(op.insert)
                            : [op.insert];

                        segments.forEach((segment) => {
                            const isNewline = segment === '\n';
                            if (isNewline) {
                                resetInsertGrouping();
                            }

                            processSegment(segment, isNewline);

                            if (isNewline) {
                                resetInsertGrouping();
                            }
                        });

                        return;
                    }

                    if (typeof op.delete === 'number' && op.delete > 0) {
                        resetInsertGrouping();
                        const length = op.delete;
                        const removed = oldDelta.slice(cursor, cursor + length);
                        const changeId = generateChangeId();
                        debugTrack('delete-change', { cursor, length, changeId, removed });
                        const timestamp = new Date().toISOString();
                        const attrs = trackChangeAttributes(changeId, 'delete', timestamp);
                        let reinsertion = new Delta().retain(cursor);

                        removed.ops.forEach((removedOp) => {
                            if (typeof removedOp.insert === 'undefined') {
                                return;
                            }
                            reinsertion = reinsertion.insert(removedOp.insert, {
                                ...(removedOp.attributes || {}),
                                ...attrs,
                            });
                        });

                        const anchorRange = { index: cursor, length };
                        quill.updateContents(reinsertion, window.Quill.sources.SILENT);
                        quill.setSelection(cursor, 0, window.Quill.sources.SILENT);
                        persistChangeRecord(changeId, 'delete', removed, anchorRange);
                        cursor += length;
                    }
                });

                storedRedlineDelta = cloneDelta(quill.getContents());
            };

            quill.on('text-change', (delta, oldDelta, source) => {
                debugTrack('quill:text-change', { delta, oldDelta, source });
                if (source !== window.Quill.sources.USER || currentView !== 'redline') {
                    return;
                }
                handleTrackChanges(delta, oldDelta);
                hideChangeBubble();
                markDirty();
            });

            quill.on('selection-change', (range) => {
                if (range && range.length > 0) {
                    lastSelection = {
                        index: range.index,
                        length: range.length,
                    };
                }

                if (!range || currentView !== 'redline') {
                    hideChangeBubble();
                    return;
                }

                const formats = quill.getFormat(range);
                const changeId = formats['tc-change-id'];

                if (!changeId) {
                    hideChangeBubble();
                    return;
                }

                const changeRange = findChangeRange(changeId);

                if (!changeRange) {
                    hideChangeBubble();
                    return;
                }

                const bounds = quill.getBounds(changeRange.index, changeRange.length || 1);
                showChangeBubble({
                    id: changeId,
                    type: formats['tc-change-type'],
                    author: formats['tc-author-name'],
                    timestamp: formats['tc-timestamp'],
                    bounds,
                    range: changeRange,
                }, 'selection');
            });

            const handleChangeHover = (event) => {
                if (!editorEl || currentView !== 'redline') {
                    return;
                }

                const eventTarget = event.target instanceof Element ? event.target : null;
                const target = eventTarget ? eventTarget.closest('[data-tc-change-id]') : null;
                if (!target) {
                    maybeHideHoverBubble();
                    return;
                }

                const payload = buildChangePayloadFromNode(target);
                if (!payload) {
                    maybeHideHoverBubble();
                    return;
                }

                const bubbleHidden = changeBubble ? changeBubble.classList.contains('hidden') : true;
                if (bubbleSource === 'hover' && hoveredChangeId === payload.id && !bubbleHidden) {
                    return;
                }

                showChangeBubble(payload, 'hover');
            };

            editorEl?.addEventListener('pointermove', handleChangeHover);
            editorEl?.addEventListener('pointerleave', () => {
                maybeHideHoverBubble(false);
            });

            changeBubble?.addEventListener('mouseenter', () => {
                bubblePinned = true;
                clearBubbleHideTimeout();
            });

            changeBubble?.addEventListener('mouseleave', () => {
                bubblePinned = false;
                maybeHideHoverBubble();
            });

            const changeDecisionRequest = async (changeId, action) => {
                if (!changeId) {
                    throw new Error('Missing change id');
                }

                debugTrack('change-decision-request', { changeId, action });
                const response = await fetch(`${changeRoutes.base}/${changeId}/${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        document_id: documentId,
                    }),
                });

                if (!response.ok) {
                    let errorPayload = null;
                    try {
                        errorPayload = await response.json();
                    } catch (_) {
                        // ignore json parse errors
                    }
                    const message = errorPayload?.message || 'Change decision failed';
                    throw new Error(message);
                }

                const payload = await response.json();
                debugTrack('change-decision-response', payload);
                setStatus(`Change ${action}ed`);
                return payload;
            };

            const handleChangeDecision = async (action) => {
                if (!activeChange) {
                    return;
                }

                changeAcceptBtn.disabled = true;
                changeRejectBtn.disabled = true;

                try {
                    await flushPendingChangePersists();
                    const payload = await changeDecisionRequest(activeChange.id, action);
                    const documentPayload = payload?.document;

                    if (documentPayload?.content_delta) {
                        storedRedlineDelta = cloneDelta(documentPayload.content_delta);
                        if (documentPayload.clean_delta?.ops) {
                            storedCleanDelta = cloneDelta(documentPayload.clean_delta);
                        }
                        if (typeof documentPayload.version === 'number') {
                            currentVersion = documentPayload.version;
                            editorEl.dataset.documentVersion = currentVersion;
                        }
                        renderEditorFromState();
                        clearDirtyState();
                        if (documentPayload.updated_at) {
                            updateLastSaved(documentPayload.updated_at, currentVersion);
                        }
                    }

                    hideChangeBubble();
                } catch (error) {
                    console.error(error);
                    setStatus(error.message || `Unable to ${action} change`, true);
                } finally {
                    changeAcceptBtn.disabled = false;
                    changeRejectBtn.disabled = false;
                }
            };

            changeAcceptBtn?.addEventListener('click', () => handleChangeDecision('accept'));
            changeRejectBtn?.addEventListener('click', () => handleChangeDecision('reject'));

            viewToggleRedline?.addEventListener('click', () => applyView('redline'));
            viewToggleClean?.addEventListener('click', () => applyView('clean'));
            renderEditorFromState();

            saveButton?.addEventListener('click', async () => {
                if (!documentId) {
                    setStatus('Document not initialized.', true);
                    return;
                }

                saveButton.disabled = true;
                setStatus('Saving…');

                try {
                    await flushPendingChangePersists();
                    const liveEditorDelta = cloneDelta(quill.getContents());
                    const redlineSource = currentView === 'clean'
                        ? cloneDelta(storedRedlineDelta || liveEditorDelta)
                        : liveEditorDelta;
                    const contentDelta = cloneDelta(redlineSource);
                    const baseDelta = buildCleanDelta(contentDelta);

                    const commentRanges = buildCommentRangeMap(contentDelta);
                    const commentPayload = comments.map((comment) => {
                        const range = commentRanges?.[comment.id] ?? commentRanges?.[String(comment.id)];
                        const fallbackIndex = Number(comment.anchor_index ?? 0) || 0;
                        const fallbackLength = Number(comment.anchor_length ?? 0) || 0;
                        const rangeIndex = range?.index;
                        const rangeLength = range?.length;

                        return {
                            id: comment.id,
                            anchor_index: typeof rangeIndex === 'number' ? rangeIndex : fallbackIndex,
                            anchor_length: typeof rangeLength === 'number' ? rangeLength : fallbackLength,
                        };
                    });

                    debugTrack('save-request', {
                        documentId,
                        comments: commentPayload,
                    });

                    const response = await fetch(saveEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            document_id: documentId,
                            comments: commentPayload,
                        }),
                    });

                    if (!response.ok) {
                        throw new Error('Save failed');
                    }

                    const payload = await response.json();
                    debugTrack('save-response', payload);
                    currentVersion = payload.version ?? currentVersion;
                    editorEl.dataset.documentVersion = currentVersion;
                    if (payload.content_delta?.ops) {
                        storedRedlineDelta = cloneDelta(payload.content_delta);
                    }
                    if (payload.clean_delta?.ops) {
                        storedCleanDelta = cloneDelta(payload.clean_delta);
                    }
                    renderEditorFromState();
                    updateLastSaved(payload.saved_at, currentVersion);
                    clearDirtyState('Saved');
                } catch (error) {
                    console.error(error);
                    setStatus('Unable to save changes', true);
                } finally {
                    saveButton.disabled = false;
                }
            });

            addCommentButton?.addEventListener('click', () => {
                if (currentView !== 'redline') {
                    setStatus('Switch to Redline view to add comments.', true);
                    return;
                }

                const selection = quill.getSelection(true) || lastSelection;

                if (!selection || selection.length === 0) {
                    setStatus('Select text to comment on.', true);
                    return;
                }

                pendingAnchor = {
                    index: selection.index,
                    length: selection.length,
                };
                openCommentModal();
            });

            const submitComment = async (body) => {
                if (!pendingAnchor) {
                    return;
                }

                setStatus('Saving comment…');

                try {
                    const response = await fetch(commentRoutes.store, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            document_id: documentId,
                            anchor_index: pendingAnchor.index,
                            anchor_length: pendingAnchor.length,
                            body,
                        }),
                    });

                    if (!response.ok) {
                        throw new Error('Comment save failed');
                    }

                    const payload = await response.json();
                    const newComment = payload.comment;
                    if (newComment) {
                        newComment.anchor_index = Number(newComment.anchor_index ?? pendingAnchor.index ?? 0) || 0;
                        newComment.anchor_length = Number(newComment.anchor_length ?? pendingAnchor.length ?? 0) || 0;
                        comments.unshift(newComment);
                    }
                    renderComments();
                    applyCommentFormat(newComment);
                    closeCommentModal();
                    setStatus('Comment added');
                } catch (error) {
                    console.error(error);
                    setStatus('Unable to save comment', true);
                }
            };

            commentForm?.addEventListener('submit', (event) => {
                event.preventDefault();
                const body = commentBodyInput.value.trim();
                if (!body) {
                    commentBodyInput.focus();
                    return;
                }
                submitComment(body);
            });

            commentModalClose?.addEventListener('click', closeCommentModal);
            commentModalCancel?.addEventListener('click', closeCommentModal);

            commentModal?.addEventListener('click', (event) => {
                if (event.target === commentModal) {
                    closeCommentModal();
                }
            });

            commentListEl?.addEventListener('click', (event) => {
                const entry = event.target.closest('[data-comment-entry]');
                if (!entry || event.target.matches('select')) {
                    return;
                }
                const commentId = Number(entry.dataset.commentId);
                const comment = comments.find((item) => item.id === commentId);
                if (!comment) {
                    return;
                }
                quill.setSelection(comment.anchor_index, comment.anchor_length, 'api');
                if (typeof quill.scrollSelectionIntoView === 'function') {
                    quill.scrollSelectionIntoView();
                } else if (typeof quill.scrollIntoView === 'function') {
                    quill.scrollIntoView();
                }
            });

            const updateCommentStatus = async (commentId, nextStatus, selectEl) => {
                const endpoint = `${commentRoutes.update}/${commentId}`;
                selectEl.disabled = true;

                try {
                    const response = await fetch(endpoint, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ status: nextStatus }),
                    });

                    if (!response.ok) {
                        throw new Error('Status update failed');
                    }

                    const payload = await response.json();
                    const updatedComment = payload.comment;
                    const index = comments.findIndex((item) => item.id === updatedComment.id);
                    if (index !== -1) {
                        const previous = comments[index];
                        comments[index] = updatedComment;
                        comments[index].anchor_index = Number(updatedComment.anchor_index ?? previous.anchor_index ?? 0) || 0;
                        comments[index].anchor_length = Number(updatedComment.anchor_length ?? previous.anchor_length ?? 0) || 0;
                        if (previous?.status === 'active' && updatedComment.status !== 'active') {
                            clearCommentFormat(previous);
                        }
                        if (updatedComment.status === 'active') {
                            applyCommentFormat(updatedComment);
                        }
                    }
                    renderComments();
                    setStatus('Comment updated');
                } catch (error) {
                    console.error(error);
                    setStatus('Unable to update comment', true);
                } finally {
                    selectEl.disabled = false;
                }
            };

            commentListEl?.addEventListener('change', (event) => {
                const select = event.target.closest('[data-comment-status]');
                if (!select) {
                    return;
                }
                const commentId = Number(select.dataset.commentId);
                const nextStatus = select.value;
                updateCommentStatus(commentId, nextStatus, select);
                event.stopPropagation();
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initQuillEditor, { once: true });
        } else {
            initQuillEditor();
        }
    </script>
@endpush
