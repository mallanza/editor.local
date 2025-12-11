
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
            let focusedChangeMeta = null;

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
                const commentEntries = buildActivityItems();
                const pendingCount = countPendingChanges();
                updateBulkChangeButtons(pendingCount);
                if (activitySummaryEl) {
                    const totalCount = commentEntries.length;
                    const label = pluralizeWord(totalCount, 'comment');
                    activitySummaryEl.textContent = `${totalCount} ${label}`;
                }

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
                                <span class="font-semibold text-gray-700">Comment • ${authorName}</span>
                                <time datetime="${comment.createdAt ?? ''}">${formatActivityTime(comment.createdAt)}</time>
                            </div>
                            <p class="mt-2 text-sm text-gray-800">${body}</p>
                            <p class="mt-2 text-xs text-gray-500">Excerpt: <span class="font-mono text-gray-700">${snippet}</span></p>
                        `;
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
                pruneResolvedChangeArtifacts();
                updateChangeActionButtons();
                updateBulkChangeButtons();
            });

            if (Array.isArray(initialChanges) && initialChanges.length) {
                tracker.loadChanges(initialChanges);
            } else {
                renderActivityFeed();
                renderHiddenSpans(tracker.getChanges({ sort: 'asc' }));
            }

            refreshChangeTooltips();
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
    