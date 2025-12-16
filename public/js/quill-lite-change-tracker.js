(function (global) {
    if (global.QuillLiteChangeTracker) {
        return;
    }

    const DEFAULT_OPTIONS = {
        attrPrefix: 'q2',
        autoTrack: true,
        source: null,
        user: {
            id: 'anonymous',
            name: 'Anonymous User',
            email: null,
        },
    };

    const STYLE_VARS_KEY = '__styleVars';
    const INSERT_STYLE_VARS = ['--q2-insert-bg', '--q2-insert-border', '--q2-insert-shadow'];

    const buildStyleVarAttrNames = (prefix = 'q2') => ({
        bg: `data-${prefix}-insert-bg`,
        border: `data-${prefix}-insert-border`,
        shadow: `data-${prefix}-insert-shadow`,
    });

    const EMBED_PLACEHOLDER = '[embed]';
    const PREVIEW_MAX_LENGTH = 320;
    const LINEBREAK_MARKER = '\u23ce';
    const STRUCTURED_ATTR_PATTERN = /^(table|ql-table|list|header|blockquote)/i;

    const ensureArray = (value) => (Array.isArray(value) ? value : []);

    class QuillLiteChangeTracker {
        constructor(quill, options = {}) {
            if (!quill) {
                throw new Error('QuillLiteChangeTracker requires a Quill instance');
            }
            if (typeof global.Quill === 'undefined') {
                throw new Error('QuillLiteChangeTracker requires Quill to be loaded');
            }

            this.quill = quill;
            this.QuillRef = global.Quill;
            this.Parchment = this.QuillRef.import('parchment');
            this.Delta = this.QuillRef.import('delta');
            this.options = {
                ...DEFAULT_OPTIONS,
                ...options,
                user: {
                    ...DEFAULT_OPTIONS.user,
                    ...(options.user || {}),
                },
            };
            this.options.source = this.options.source ?? this.QuillRef.sources.USER;

            this.attrNames = this._buildAttrNames(this.options.attrPrefix);
            this.styleVarAttrNames = buildStyleVarAttrNames(this.options.attrPrefix);
            this._blotName = `${this.options.attrPrefix}-change`;
            this._ledger = new Map();
            this._listeners = new Map();
            this._trackingEnabled = Boolean(this.options.autoTrack);
            this._lastCursor = 0;
            this._insertContinuance = null;
            this._deleteContinuance = null;
            this._continuanceWindow = 1500;
            this._activeBatch = null;
            this._pendingDeleteDirection = null;
            this._styleVarNames = [...INSERT_STYLE_VARS];
            this._userPaletteCache = new Map();

            this._registerBlot();
            this._bind();
        }

        enableTracking() {
            this._trackingEnabled = true;
        }

        disableTracking() {
            this._trackingEnabled = false;
        }

        setCurrentUser(user) {
            if (!user) {
                return;
            }
            this.options.user = {
                ...this.options.user,
                ...user,
            };
        }

        beginBatchChange(type = 'insert') {
            const normalized = type === 'delete' ? 'delete' : 'insert';
            const now = Date.now();
            const timestamp = new Date(now).toISOString();
            const id = this._generateId();
            this._activeBatch = {
                id,
                type: normalized,
                startedAt: timestamp,
                expires: now + this._continuanceWindow,
            };
            return id;
        }

        endBatchChange(batchId) {
            if (!this._activeBatch) {
                return;
            }
            if (!batchId || batchId === this._activeBatch.id) {
                this._activeBatch = null;
            }
        }

        withBatchChange(type, fn) {
            if (typeof fn !== 'function') {
                return undefined;
            }
            const batchId = this.beginBatchChange(type);
            try {
                return fn(batchId);
            } finally {
                this.endBatchChange(batchId);
            }
        }

        on(eventName, handler) {
            if (!eventName || typeof handler !== 'function') {
                return () => undefined;
            }
            if (!this._listeners.has(eventName)) {
                this._listeners.set(eventName, new Set());
            }
            this._listeners.get(eventName).add(handler);
            return () => this.off(eventName, handler);
        }

        off(eventName, handler) {
            if (!eventName || !this._listeners.has(eventName)) {
                return;
            }
            const handlers = this._listeners.get(eventName);
            handlers.delete(handler);
            if (!handlers.size) {
                this._listeners.delete(eventName);
            }
        }

        destroy() {
            if (this._boundTextChange) {
                this.quill.off('text-change', this._boundTextChange);
            }
            if (this._boundKeydown) {
                this.quill.root?.removeEventListener('keydown', this._boundKeydown);
            }
            this._listeners.clear();
        }

        getChanges({ sort = 'desc' } = {}) {
            const records = Array.from(this._ledger.values());
            return records.sort((a, b) => {
                if (sort === 'asc') {
                    return a.createdAt.localeCompare(b.createdAt);
                }
                return b.createdAt.localeCompare(a.createdAt);
            });
        }

        getChange(changeId) {
            return changeId ? this._ledger.get(changeId) || null : null;
        }

        acceptChange(changeId) {
            return this._resolveChange(changeId, 'accept');
        }

        rejectChange(changeId) {
            return this._resolveChange(changeId, 'reject');
        }

        acceptAll() {
            this.getChanges({ sort: 'asc' }).forEach((change) => {
                if (change.status === 'pending') {
                    this.acceptChange(change.id);
                }
            });
        }

        rejectAll() {
            this.getChanges({ sort: 'asc' }).forEach((change) => {
                if (change.status === 'pending') {
                    this.rejectChange(change.id);
                }
            });
        }

        snapshot() {
            return {
                text: this.quill.getText(),
                delta: this.quill.getContents(),
                changes: this.getChanges({ sort: 'asc' }),
            };
        }

        loadChanges(changes = []) {
            this._ledger.clear();
            ensureArray(changes).forEach((change) => {
                const normalized = this._normalizeIncomingChange(change);
                if (!normalized) {
                    return;
                }
                this._ledger.set(normalized.id, normalized);
            });
            this._emit('ledger-change', this.getChanges());
        }

        findChangeRange(changeId) {
            const ranges = this.findChangeRanges(changeId);
            if (!ranges.length) {
                return null;
            }
            if (ranges.length === 1) {
                return ranges[0];
            }

            // Backward-compatible behavior: return a single bounding range.
            // Note that for non-contiguous change segments this will include untracked content
            // between segments. Call findChangeRanges() to operate on segments precisely.
            const start = ranges[0].index;
            const last = ranges[ranges.length - 1];
            const end = last.index + last.length;
            return { index: start, length: Math.max(0, end - start) };
        }

        findChangeRanges(changeId) {
            if (!changeId) {
                return [];
            }
            const contents = this.quill.getContents();
            let cursor = 0;
            let activeStart = null;
            let activeEnd = null;
            const ranges = [];

            const closeActive = () => {
                if (activeStart === null) {
                    return;
                }
                const end = activeEnd ?? activeStart;
                const length = Math.max(0, end - activeStart);
                if (length > 0) {
                    ranges.push({ index: activeStart, length });
                }
                activeStart = null;
                activeEnd = null;
            };

            ensureArray(contents.ops).forEach((op) => {
                if (!Object.prototype.hasOwnProperty.call(op, 'insert')) {
                    closeActive();
                    cursor += typeof op.retain === 'number' ? op.retain : 0;
                    return;
                }
                const opLength = typeof op.insert === 'string' ? op.insert.length : 1;
                const changeMeta = op.attributes?.[this._blotName];
                const matches = changeMeta?.[this.attrNames.id] === changeId;
                if (matches) {
                    if (activeStart === null) {
                        activeStart = cursor;
                        activeEnd = cursor + opLength;
                    } else {
                        activeEnd = cursor + opLength;
                    }
                } else {
                    closeActive();
                }
                cursor += opLength;
            });
            closeActive();

            // Ensure stable ordering.
            ranges.sort((a, b) => a.index - b.index);
            return ranges;
        }

        _bind() {
            this._boundTextChange = (delta, oldDelta, source) => {
                if (!this._trackingEnabled) {
                    return;
                }
                if (source === this.QuillRef.sources.SILENT) {
                    return;
                }
                this._handleUserDelta(delta, oldDelta);
            };
            this.quill.on('text-change', this._boundTextChange);

            this._boundKeydown = (event) => {
                if (event.defaultPrevented) {
                    this._pendingDeleteDirection = null;
                    return;
                }
                if (event.key === 'Delete') {
                    this._pendingDeleteDirection = 'forward';
                } else if (event.key === 'Backspace') {
                    this._pendingDeleteDirection = 'backward';
                } else {
                    this._pendingDeleteDirection = null;
                }
            };
            this.quill.root?.addEventListener('keydown', this._boundKeydown, { capture: true });
        }

        _handleUserDelta(delta, oldDelta) {
            this._lastCursor = 0;
            ensureArray(delta.ops).forEach((op) => {
                if (typeof op.retain === 'number') {
                    this._lastCursor += op.retain;
                    return;
                }
                if (typeof op.insert === 'string') {
                    this._processInsert(this._lastCursor, op.insert);
                    this._lastCursor += op.insert.length;
                    return;
                }
                if (Object.prototype.hasOwnProperty.call(op, 'insert')) {
                    this._processEmbedInsert(this._lastCursor, op.insert);
                    this._lastCursor += 1;
                    return;
                }
                if (typeof op.delete === 'number') {
                    this._processDelete(this._lastCursor, op.delete, oldDelta);
                }
            });
        }

        _processEmbedInsert(index, embed) {
            // Treat non-string inserts (images, embeds, table structures) as length-1 inserts.
            if (!Object.prototype.hasOwnProperty.call({ insert: embed }, 'insert')) {
                return;
            }
            if (this._isTableishEmbed(embed)) {
                return;
            }
            this._deleteContinuance = null;
            this._insertContinuance = null;
            const length = 1;
            const timestamp = new Date().toISOString();
            const batch = this._resolveBatchContext('insert', null);
            const changeId = batch?.id ?? this._generateId();
            const changeTimestamp = batch?.startedAt ?? timestamp;
            const attrs = this._composeAttrs({
                id: changeId,
                type: 'insert',
                status: 'pending',
                timestamp: changeTimestamp,
            });

            this.quill.formatText(index, length, { [this._blotName]: attrs }, this.QuillRef.sources.SILENT);

            const change = {
                id: changeId,
                type: 'insert',
                status: 'pending',
                preview: EMBED_PLACEHOLDER,
                createdAt: changeTimestamp,
                updatedAt: changeTimestamp,
                length,
                user: { ...this.options.user },
            };
            this._upsertChange(change, 'insert');
        }

        _applyChangeFormat(index, length, attrs) {
            if (!Number.isFinite(index) || !Number.isFinite(length) || length <= 0) {
                return false;
            }

            // Prefer a retain-based delta format application.
            // This is notably more reliable than a single formatText() call for multi-block
            // paste operations (e.g. HTML -> multiple paragraphs/headers), where formatText
            // can be inconsistent across line boundaries in some Quill builds.
            try {
                const payload = new this.Delta().retain(index).retain(length, { [this._blotName]: attrs });
                this.quill.updateContents(payload, this.QuillRef.sources.SILENT);
                return true;
            } catch (error) {
                // fall through
            }

            try {
                this.quill.formatText(index, length, { [this._blotName]: attrs }, this.QuillRef.sources.SILENT);
                return true;
            } catch (error) {
                // fall through
            }

            // Last resort: apply in smaller chunks.
            // This is slower, but keeps the editor from ending up partially tracked.
            const chunkSize = 256;
            for (let offset = 0; offset < length; offset += chunkSize) {
                const chunkLen = Math.min(chunkSize, length - offset);
                try {
                    const payload = new this.Delta().retain(index + offset).retain(chunkLen, { [this._blotName]: attrs });
                    this.quill.updateContents(payload, this.QuillRef.sources.SILENT);
                } catch (error) {
                    try {
                        this.quill.formatText(index + offset, chunkLen, { [this._blotName]: attrs }, this.QuillRef.sources.SILENT);
                    } catch (innerError) {
                        // give up on this chunk
                    }
                }
            }
            return true;
        }

        _processInsert(index, text) {
            if (!text) {
                return;
            }
            this._deleteContinuance = null;
            const length = text.length;
            const timestamp = new Date().toISOString();
            const continuingChange = this._lookupContinuingInsert(index);
            const batch = this._resolveBatchContext('insert', continuingChange);
            const changeId = continuingChange?.id ?? batch?.id ?? this._generateId();
            const changeTimestamp = continuingChange?.createdAt ?? batch?.startedAt ?? timestamp;
            const attrs = this._composeAttrs({
                id: changeId,
                type: 'insert',
                status: 'pending',
                timestamp: changeTimestamp,
            });

            this._applyChangeFormat(index, length, attrs);
            const fragment = this._stringPreview(text);
            if (continuingChange) {
                continuingChange.length += length;
                continuingChange.preview = (continuingChange.preview || '') + fragment;
                continuingChange.updatedAt = timestamp;
                this._ledger.set(changeId, continuingChange);
                this._emit('ledger-change', this.getChanges());
                this._emit('change', { reason: 'insert-extend', change: continuingChange });
            } else {
                const change = {
                    id: changeId,
                    type: 'insert',
                    status: 'pending',
                    preview: fragment,
                    createdAt: changeTimestamp,
                    updatedAt: changeTimestamp,
                    length,
                    user: { ...this.options.user },
                };
                this._upsertChange(change, 'insert');
            }
            this._insertContinuance = {
                id: changeId,
                index: index + length,
                expires: Date.now() + this._continuanceWindow,
            };
        }

        _processDelete(index, length, oldDelta) {
            if (length <= 0) {
                return;
            }
            this._insertContinuance = null;
            const removed = oldDelta.slice(index, index + length);
            const { residualDelta, adjustedChangeIds } = this._splitRemovedDelta(removed);
            if (adjustedChangeIds.size) {
                adjustedChangeIds.forEach((changeId) => this._refreshInsertChange(changeId));
            }
            const deltaLength = this._measureDeltaLength(residualDelta);
            if (deltaLength <= 0) {
                return;
            }
            if (this._isStructuralDelete(residualDelta) || this._isTableStructuralDelete(residualDelta)) {
                this._pendingDeleteDirection = null;
                return;
            }
            const preview = this._previewFromDelta(residualDelta);
            const timestamp = new Date().toISOString();
            const continuingChange = this._lookupContinuingDelete(index);
            const batch = this._resolveBatchContext('delete', continuingChange);
            const id = continuingChange?.id ?? batch?.id ?? this._generateId();
            const changeTimestamp = continuingChange?.createdAt ?? batch?.startedAt ?? timestamp;
            const attrs = this._composeAttrs({
                id,
                type: 'delete',
                status: 'pending',
                timestamp: changeTimestamp,
            });
            let insertionDelta = new this.Delta().retain(index);
            const embedOffsetsToFormat = [];
            let localOffset = 0;
            ensureArray(residualDelta.ops).forEach((op) => {
                if (!Object.prototype.hasOwnProperty.call(op, 'insert')) {
                    return;
                }
                const preservedAttrs = op.attributes ? { ...op.attributes } : {};
                if (Object.prototype.hasOwnProperty.call(preservedAttrs, this._blotName)) {
                    delete preservedAttrs[this._blotName];
                }

                // IMPORTANT: embeds (like images) cannot reliably carry object-valued attributes
                // in the inserted delta. Insert with preserved attrs, then apply q2-change via
                // formatText so Quill can wrap/mark it appropriately.
                if (typeof op.insert === 'object') {
                    insertionDelta = insertionDelta.insert(op.insert, preservedAttrs);
                    embedOffsetsToFormat.push(localOffset);
                    localOffset += 1;
                    return;
                }

                const mergedAttrs = { ...preservedAttrs, [this._blotName]: attrs };
                insertionDelta = insertionDelta.insert(op.insert, mergedAttrs);
                localOffset += typeof op.insert === 'string' ? op.insert.length : 0;
            });
            this.quill.updateContents(insertionDelta, this.QuillRef.sources.SILENT);

            if (embedOffsetsToFormat.length) {
                embedOffsetsToFormat.forEach((offset) => {
                    try {
                        this.quill.formatText(index + offset, 1, { [this._blotName]: attrs }, this.QuillRef.sources.SILENT);
                    } catch (error) {
                        // best-effort; embed will still exist even if format fails
                    }
                });
            }
            const direction = this._pendingDeleteDirection;
            const caretIndex = direction === 'forward'
                ? index + deltaLength
                : Math.max(0, index);
            this.quill.setSelection(caretIndex, 0, this.QuillRef.sources.SILENT);
            this._pendingDeleteDirection = null;
            if (continuingChange) {
                continuingChange.length += deltaLength;
                continuingChange.preview = (continuingChange.preview || '') + preview;
                continuingChange.updatedAt = timestamp;
                this._ledger.set(id, continuingChange);
                this._emit('ledger-change', this.getChanges());
                this._emit('change', { reason: 'delete-extend', change: continuingChange });
            } else {
                const change = {
                    id,
                    type: 'delete',
                    status: 'pending',
                    preview,
                    createdAt: changeTimestamp,
                    updatedAt: changeTimestamp,
                    length: deltaLength,
                    user: { ...this.options.user },
                };
                this._upsertChange(change, 'delete');
            }
            this._normalizeChangeFormatting(id);
            this._markDeleteContinuance(id);
            const mergedId = this._mergeNeighborDeletes(id);
            if (mergedId !== id) {
                this._markDeleteContinuance(mergedId);
                this._normalizeChangeFormatting(mergedId);
            }
        }

        _resolveChange(changeId, action) {
            if (!changeId) {
                return null;
            }
            const change = this._ledger.get(changeId);
            if (!change || change.status !== 'pending') {
                return change || null;
            }
            const ranges = this.findChangeRanges(changeId);
            const range = this.findChangeRange(changeId);
            let applied = false;

            const appearsToBeWholeTableDelete = change.type === 'delete' ? this._appearsToBeWholeTableDelete(change) : false;
            if (ranges.length) {
                if (change.type === 'insert') {
                    if (action === 'accept') {
                        ranges.forEach((r) => this._resetFormatting(r));
                        applied = true;
                    } else {
                        // Delete from end -> start so indices remain stable.
                        [...ranges]
                            .sort((a, b) => b.index - a.index)
                            .forEach((r) => {
                                this.quill.deleteText(r.index, r.length, this.QuillRef.sources.SILENT);
                            });
                        applied = true;
                    }
                } else if (change.type === 'delete') {
                    if (action === 'accept') {
                        // Structured deletes (notably tables) need special handling.
                        // Use the (possibly bounding) range for table-span checks, but delete
                        // exact segments for normal inline delete changes.
                        const handled = this._resolveStructuredDelete(change, range);
                        if (!handled && !appearsToBeWholeTableDelete) {
                            [...ranges]
                                .sort((a, b) => b.index - a.index)
                                .forEach((r) => {
                                    this.quill.deleteText(r.index, r.length, this.QuillRef.sources.SILENT);
                                });
                            applied = true;
                        } else {
                            applied = true;
                        }
                    } else {
                        ranges.forEach((r) => this._resetFormatting(r));
                        applied = true;
                    }
                }
            } else if (change.type === 'delete' && action === 'accept') {
                // Structured deletes (notably tables) may not map to a stable Delta range.
                // Resolve from DOM structure; only mark accepted if we applied a change.
                applied = this._resolveStructuredDelete(change, null);
            }

            if (!applied) {
                return change;
            }
            const resolution = action === 'accept' ? 'accepted' : 'rejected';
            change.status = resolution;
            change.resolvedAt = new Date().toISOString();
            change.resolvedBy = { ...this.options.user };
            this._upsertChange(change, 'resolve');
            return change;
        }

        _appearsToBeWholeTableDelete(change) {
            if (!change || change.type !== 'delete' || !this.quill?.root) {
                return false;
            }
            const root = this.quill.root;
            const selector = `[${this.attrNames.id}="${change.id}"]`;
            const nodes = root.querySelectorAll(selector);
            if (!nodes.length) {
                return false;
            }
            const tables = new Set();
            nodes.forEach((node) => {
                const table = node.closest && node.closest('table');
                if (table) {
                    tables.add(table);
                }
            });
            if (tables.size !== 1) {
                return false;
            }
            const [tableElement] = Array.from(tables);
            if (!tableElement) {
                return false;
            }
            const deleteNodeSelector = `[${this.attrNames.type}="delete"][${this.attrNames.id}]`;
            const deleteNodes = tableElement.querySelectorAll(deleteNodeSelector);
            const deleteIds = new Set(
                Array.from(deleteNodes)
                    .map((node) => node.getAttribute(this.attrNames.id))
                    .filter(Boolean)
            );
            const onlyThisDeleteChange = deleteIds.size === 1 && deleteIds.has(change.id);
            if (!onlyThisDeleteChange) {
                return false;
            }
            try {
                const clone = tableElement.cloneNode(true);
                clone.querySelectorAll('.ql-ui').forEach((node) => node.remove());
                clone.querySelectorAll(`[${this.attrNames.type}="delete"]`).forEach((node) => node.remove());
                const remainingText = (clone.textContent || '').replace(/\s+/g, '');
                const remainingEmbeds = clone.querySelector('img, video, iframe, object, embed');
                return !remainingText.length && !remainingEmbeds;
            } catch (error) {
                return false;
            }
        }

        _resolveStructuredDelete(change, range) {
            if (!change || change.type !== 'delete') {
                return false;
            }
            if (!this.quill || !this.quill.root) {
                return false;
            }
            const root = this.quill.root;
            const selector = `[${this.attrNames.id}="${change.id}"]`;
            const nodes = root.querySelectorAll(selector);
            if (!nodes.length) {
                return false;
            }
            const tables = new Set();
            nodes.forEach((node) => {
                const table = node.closest && node.closest('table');
                if (table) {
                    tables.add(table);
                }
            });
            if (!tables.size) {
                return false;
            }
            if (tables.size > 1) {
                return false;
            }
            const [tableElement] = Array.from(tables);
            if (!tableElement) {
                return false;
            }

            // If this delete change appears to represent a full-table deletion, accept should
            // remove the entire table structure (not just the text inside tracked spans).
            // Otherwise we can end up with an empty "committed" table after accepting.
            const deleteNodeSelector = `[${this.attrNames.type}="delete"][${this.attrNames.id}]`;
            const deleteNodes = tableElement.querySelectorAll(deleteNodeSelector);
            const deleteIds = new Set(
                Array.from(deleteNodes)
                    .map((node) => node.getAttribute(this.attrNames.id))
                    .filter(Boolean)
            );
            const onlyThisDeleteChange = deleteIds.size === 1 && deleteIds.has(change.id);

            let tableWouldBeEmptyAfterRemovingDeletes = false;
            if (onlyThisDeleteChange) {
                try {
                    const clone = tableElement.cloneNode(true);
                    clone.querySelectorAll('.ql-ui').forEach((node) => node.remove());
                    clone.querySelectorAll(`[${this.attrNames.type}="delete"]`).forEach((node) => node.remove());
                    const remainingText = (clone.textContent || '').replace(/\s+/g, '');
                    const remainingEmbeds = clone.querySelector('img, video, iframe, object, embed');
                    tableWouldBeEmptyAfterRemovingDeletes = !remainingText.length && !remainingEmbeds;
                } catch (error) {
                    tableWouldBeEmptyAfterRemovingDeletes = false;
                }
            }

            const shouldDeleteWholeTable = onlyThisDeleteChange && tableWouldBeEmptyAfterRemovingDeletes;

            // If we don't believe this is a whole-table delete and we don't have a delta range,
            // we can't safely resolve a structured delete.
            if (!range && !shouldDeleteWholeTable) {
                return false;
            }

            const parchmentFind = this.Parchment && typeof this.Parchment.find === 'function'
                ? this.Parchment.find.bind(this.Parchment)
                : (this.QuillRef && typeof this.QuillRef.find === 'function'
                    ? this.QuillRef.find.bind(this.QuillRef)
                    : null);
            if (!parchmentFind) {
                return false;
            }

            // quill-table-better does not necessarily use the blotName "table".
            // Walk up the blot chain and pick the outermost *table-like* blot.
            let blot = parchmentFind(tableElement, true) || parchmentFind(tableElement) || parchmentFind(nodes[0], true) || parchmentFind(nodes[0]);
            if (!blot) {
                return false;
            }
            let current = blot;
            let tableBlot = null;
            let domTableBlot = null;
            let tableWrapperBlot = null;
            while (current && current !== this.quill.scroll) {
                const blotName = current.statics?.blotName;
                if (!tableBlot && blotName && /table/i.test(blotName)) {
                    tableBlot = current;
                }
                const domNode = current.domNode;
                if (!domTableBlot && domNode && domNode.tagName && domNode.tagName.toLowerCase() === 'table') {
                    domTableBlot = current;
                }
                if (!tableWrapperBlot && domNode && domNode.contains && domNode.contains(tableElement)) {
                    tableWrapperBlot = current;
                }
                current = current.parent;
            }

            const structuralBlot = domTableBlot || tableWrapperBlot || tableBlot;
            const targetBlot = shouldDeleteWholeTable ? structuralBlot : (structuralBlot || blot);
            if (!targetBlot) {
                return false;
            }

            const candidateBlots = Array.from(
                new Set([domTableBlot, tableWrapperBlot, tableBlot, blot].filter(Boolean))
            );

            const deleteTableUsingCandidate = (candidate) => {
                // Prefer index via Quill API when available; otherwise fall back to blot.offset().
                let index = null;
                if (typeof this.quill.getIndex === 'function') {
                    try {
                        index = this.quill.getIndex(candidate);
                    } catch (error) {
                        index = null;
                    }
                }
                if (index === null && typeof candidate.offset === 'function') {
                    index = candidate.offset(this.quill.scroll);
                }
                const length = typeof candidate.length === 'function' ? candidate.length() : null;

                if ((typeof index !== 'number' || typeof length !== 'number' || length <= 0) && typeof candidate.remove === 'function') {
                    try {
                        candidate.remove();
                        if (typeof this.quill.update === 'function') {
                            this.quill.update(this.QuillRef.sources.SILENT);
                        }
                        return true;
                    } catch (error) {
                        return false;
                    }
                }

                if (typeof index !== 'number' || typeof length !== 'number' || length <= 0) {
                    return false;
                }

                this.quill.deleteText(index, length, this.QuillRef.sources.SILENT);
                return true;
            };

            // If this delete represents a full-table delete, accept should remove the whole table
            // even if the tracked delete range doesn't span the underlying table blot.
            if (shouldDeleteWholeTable) {
                // Try candidates until the original table element disappears.
                for (let i = 0; i < candidateBlots.length; i += 1) {
                    const didAttempt = deleteTableUsingCandidate(candidateBlots[i]);
                    if (didAttempt && !root.contains(tableElement)) {
                        return true;
                    }
                }
                // Nothing actually removed the table; treat as not handled.
                return false;
            }

            // For non-whole-table deletes, we require an indexable structural target.
            let index = null;
            if (typeof this.quill.getIndex === 'function') {
                try {
                    index = this.quill.getIndex(targetBlot);
                } catch (error) {
                    index = null;
                }
            }
            if (index === null && typeof targetBlot.offset === 'function') {
                index = targetBlot.offset(this.quill.scroll);
            }
            const length = typeof targetBlot.length === 'function' ? targetBlot.length() : null;
            if (typeof index !== 'number' || typeof length !== 'number' || length <= 0) {
                return false;
            }

            // Only delete the whole table if this delete change actually spans the entire table.
            // Otherwise, accepting a delete inside a table should only remove the tracked range.
            const changeStart = range.index;
            const changeEnd = range.index + range.length;
            const tableStart = index;
            const tableEnd = index + length;

            // Primary guard: only delete the whole table if the tracked range spans it.
            if (changeStart <= tableStart && changeEnd >= tableEnd) {
                this.quill.deleteText(index, length, this.QuillRef.sources.SILENT);
                return true;
            }
            return false;
        }

        _resetFormatting(range) {
            if (!range) {
                return;
            }
            this.quill.formatText(range.index, range.length, { [this._blotName]: false }, this.QuillRef.sources.SILENT);
        }

        _upsertChange(change, reason) {
            this._ledger.set(change.id, change);
            this._emit('ledger-change', this.getChanges());
            this._emit('change', { reason, change });
        }

        _emit(eventName, payload) {
            const handlers = this._listeners.get(eventName);
            if (!handlers) {
                return;
            }
            handlers.forEach((handler) => {
                try {
                    handler(payload);
                } catch (error) {
                    console.error('QuillLiteChangeTracker listener error', error);
                }
            });
        }

        _registerBlot() {
            if (this.QuillRef.imports?.[`formats/${this._blotName}`]) {
                return;
            }
            const InlineBlot = this.QuillRef.import('blots/inline');
            const tracker = this;
            const blotName = this._blotName;

            class QuillLiteChangeBlot extends InlineBlot {
                static create(value) {
                    const node = super.create();
                    tracker._writeDataset(node, value);
                    tracker._ensureInsertStyle(node, value);
                    return node;
                }

                static formats(node) {
                    return tracker._readDataset(node);
                }

                format(name, value) {
                    if (name === blotName) {
                        if (!value) {
                            tracker._clearDataset(this.domNode);
                        } else {
                            tracker._writeDataset(this.domNode, value);
                            tracker._ensureInsertStyle(this.domNode, value);
                        }
                    } else {
                        super.format(name, value);
                    }
                }
            }

            QuillLiteChangeBlot.blotName = blotName;
            QuillLiteChangeBlot.tagName = 'span';
            QuillLiteChangeBlot.className = `${this.options.attrPrefix}-change-inline`;

            this.QuillRef.register(QuillLiteChangeBlot, true);
        }

        _buildAttrNames(prefix = 'q2') {
            return {
                id: `data-${prefix}-change-id`,
                type: `data-${prefix}-change-type`,
                status: `data-${prefix}-change-status`,
                userId: `data-${prefix}-user-id`,
                userName: `data-${prefix}-user-name`,
                userEmail: `data-${prefix}-user-email`,
                timestamp: `data-${prefix}-timestamp`,
            };
        }

        _composeAttrs({ id, type, status, timestamp }) {
            const payload = {
                [this.attrNames.id]: id,
                [this.attrNames.type]: type,
                [this.attrNames.status]: status,
                [this.attrNames.userId]: this.options.user.id,
                [this.attrNames.userName]: this.options.user.name,
                [this.attrNames.userEmail]: this.options.user.email ?? '',
                [this.attrNames.timestamp]: timestamp,
            };
            if (type === 'insert') {
                const styleVars = this._buildInsertStyleVars(this.options.user);
                if (styleVars) {
                    payload[STYLE_VARS_KEY] = styleVars;
                }
            }
            return payload;
        }

        _generateId() {
            if (global.crypto?.randomUUID) {
                return global.crypto.randomUUID();
            }
            return `q2-${Date.now()}-${Math.floor(Math.random() * 1e5)}`;
        }

        _measureDeltaLength(delta) {
            return ensureArray(delta?.ops).reduce((sum, op) => {
                if (typeof op.insert === 'string') {
                    return sum + op.insert.length;
                }
                if (Object.prototype.hasOwnProperty.call(op, 'insert')) {
                    return sum + 1;
                }
                if (typeof op.retain === 'number') {
                    return sum + op.retain;
                }
                return sum;
            }, 0);
        }

        _stringPreview(text) {
            const normalized = (text || '').replace(/\n/g, LINEBREAK_MARKER);
            if (normalized.length <= PREVIEW_MAX_LENGTH) {
                return normalized;
            }
            return `${normalized.slice(0, PREVIEW_MAX_LENGTH - 3)}...`;
        }

        _previewFromDelta(delta) {
            const parts = [];
            ensureArray(delta?.ops).forEach((op) => {
                if (typeof op.insert === 'string') {
                    parts.push(op.insert);
                } else if (Object.prototype.hasOwnProperty.call(op, 'insert')) {
                    parts.push(EMBED_PLACEHOLDER);
                }
            });
            return this._stringPreview(parts.join(''));
        }

        _isStructuralDelete(delta) {
            let hasMeaningfulContent = false;
            ensureArray(delta?.ops).forEach((op) => {
                if (!Object.prototype.hasOwnProperty.call(op, 'insert')) {
                    return;
                }
                const opAttrs = op.attributes || null;
                if (typeof op.insert !== 'string') {
                    hasMeaningfulContent = true;
                    return;
                }
                const stripped = op.insert.replace(/\n/g, '').trim();
                if (stripped.length || this._hasStructuredAttributes(opAttrs)) {
                    hasMeaningfulContent = true;
                }
            });
            return !hasMeaningfulContent;
        }

        _hasStructuredAttributes(attrs) {
            if (!attrs) {
                return false;
            }
            return Object.keys(attrs).some((attrName) => STRUCTURED_ATTR_PATTERN.test(attrName) || attrName.includes('table-'));
        }

        _isTableishEmbed(embed) {
            if (!embed || typeof embed !== 'object') {
                return false;
            }
            return Object.keys(embed).some((key) => String(key).toLowerCase().includes('table'));
        }

        _isTableStructuralDelete(delta) {
            // Row/column operations often delete only table newline structure.
            // Reinserting those newlines as inline-tracked content corrupts table rendering.
            let hasNonNewlineContent = false;
            let hasTableNewlines = false;
            ensureArray(delta?.ops).forEach((op) => {
                if (!Object.prototype.hasOwnProperty.call(op, 'insert')) {
                    return;
                }
                if (typeof op.insert !== 'string') {
                    // Only treat *table-ish* embeds as table structure.
                    // Other embeds (e.g., images) should be redlined normally.
                    if (this._isTableishEmbed(op.insert)) {
                        hasTableNewlines = true;
                    } else {
                        hasNonNewlineContent = true;
                    }
                    return;
                }
                const opAttrs = op.attributes || null;
                const stripped = op.insert.replace(/\n/g, '').trim();
                if (stripped.length) {
                    hasNonNewlineContent = true;
                }
                if (op.insert.includes('\n') && this._hasTableAttributes(opAttrs)) {
                    hasTableNewlines = true;
                }
            });
            return hasTableNewlines && !hasNonNewlineContent;
        }

        _hasTableAttributes(attrs) {
            if (!attrs) {
                return false;
            }
            return Object.keys(attrs).some((attrName) => String(attrName).toLowerCase().includes('table'));
        }

        _writeDataset(node, data = {}) {
            if (!node || !data) {
                return;
            }
            const styleVars = data[STYLE_VARS_KEY];
            Object.entries(data).forEach(([key, value]) => {
                if (key === STYLE_VARS_KEY) {
                    return;
                }
                if (value == null || value === false) {
                    node.removeAttribute(key);
                } else {
                    node.setAttribute(key, value);
                }
            });
            if (styleVars) {
                // Persist style vars as data attributes so server-side HTML sanitization
                // (which strips style="") does not wipe per-user colors.
                // These data-q2-* attributes are allow-listed.
                try {
                    const bg = styleVars['--q2-insert-bg'];
                    const border = styleVars['--q2-insert-border'];
                    const shadow = styleVars['--q2-insert-shadow'];
                    if (this.styleVarAttrNames?.bg) {
                        if (bg) node.setAttribute(this.styleVarAttrNames.bg, bg);
                        else node.removeAttribute(this.styleVarAttrNames.bg);
                    }
                    if (this.styleVarAttrNames?.border) {
                        if (border) node.setAttribute(this.styleVarAttrNames.border, border);
                        else node.removeAttribute(this.styleVarAttrNames.border);
                    }
                    if (this.styleVarAttrNames?.shadow) {
                        if (shadow) node.setAttribute(this.styleVarAttrNames.shadow, shadow);
                        else node.removeAttribute(this.styleVarAttrNames.shadow);
                    }
                } catch (error) {
                    // best-effort
                }
                this._applyStyleVars(node, styleVars);
            }
        }

        _readDataset(node) {
            if (!node) {
                return null;
            }
            const value = {};
            Object.values(this.attrNames).forEach((attrName) => {
                const attrValue = node.getAttribute(attrName);
                if (attrValue != null) {
                    value[attrName] = attrValue;
                }
            });

            // Re-hydrate persisted palette vars (if present) back into the blot formats.
            // This makes the palette survive HTML snapshots and round-trip through Quill.
            try {
                const bg = this.styleVarAttrNames?.bg ? node.getAttribute(this.styleVarAttrNames.bg) : null;
                const border = this.styleVarAttrNames?.border ? node.getAttribute(this.styleVarAttrNames.border) : null;
                const shadow = this.styleVarAttrNames?.shadow ? node.getAttribute(this.styleVarAttrNames.shadow) : null;
                if (bg || border || shadow) {
                    value[STYLE_VARS_KEY] = {
                        '--q2-insert-bg': bg || '',
                        '--q2-insert-border': border || '',
                        '--q2-insert-shadow': shadow || '',
                    };
                }
            } catch (error) {
                // ignore
            }
            return value;
        }

        _clearDataset(node) {
            if (!node) {
                return;
            }
            Object.values(this.attrNames).forEach((attrName) => {
                node.removeAttribute(attrName);
            });
            if (this.styleVarAttrNames) {
                Object.values(this.styleVarAttrNames).forEach((attrName) => {
                    if (attrName) {
                        node.removeAttribute(attrName);
                    }
                });
            }
            if (node.style && this._styleVarNames?.length) {
                this._styleVarNames.forEach((varName) => {
                    node.style.removeProperty(varName);
                });
            }
        }

        _applyStyleVars(node, vars = {}) {
            if (!node?.style) {
                return;
            }
            Object.entries(vars).forEach(([varName, value]) => {
                if (!varName) {
                    return;
                }
                if (value == null || value === '') {
                    node.style.removeProperty(varName);
                } else {
                    node.style.setProperty(varName, value);
                }
            });
        }

        _ensureInsertStyle(node, payload = null) {
            if (!node) {
                return;
            }
            const typeMeta = payload?.[this.attrNames.type] ?? node.getAttribute(this.attrNames.type);
            if (typeMeta !== 'insert') {
                return;
            }
            const alreadyStyled = node.style?.getPropertyValue('--q2-insert-bg');
            if (payload?.[STYLE_VARS_KEY] || alreadyStyled) {
                return;
            }
            const user = this._resolveUserFromMeta(payload, node);
            if (!user) {
                return;
            }
            const vars = this._buildInsertStyleVars(user);
            if (vars) {
                this._applyStyleVars(node, vars);
            }
        }

        _resolveUserFromMeta(payload = {}, node = null) {
            const readAttr = (attrName) => {
                if (!attrName) {
                    return null;
                }
                if (payload && Object.prototype.hasOwnProperty.call(payload, attrName)) {
                    return payload[attrName];
                }
                return node?.getAttribute?.(attrName) ?? null;
            };
            const id = readAttr(this.attrNames.userId);
            const name = readAttr(this.attrNames.userName);
            const email = readAttr(this.attrNames.userEmail);
            if (!id && !name && !email) {
                return null;
            }
            return {
                id: id || this.options.user.id,
                name: name || this.options.user.name,
                email: email || null,
            };
        }

        _buildInsertStyleVars(user) {
            const palette = this._resolveUserPalette(user);
            if (!palette) {
                return null;
            }
            return {
                '--q2-insert-bg': palette.bg,
                '--q2-insert-border': palette.border,
                '--q2-insert-shadow': palette.shadow,
            };
        }

        _resolveUserPalette(user) {
            const key = this._resolveUserKey(user);
            if (!key) {
                return null;
            }
            if (this._userPaletteCache.has(key)) {
                return this._userPaletteCache.get(key);
            }
            const hue = this._hashStringToHue(key);
            const palette = {
                bg: `hsla(${hue}, 85%, 92%, 1)`,
                border: `hsla(${hue}, 70%, 45%, 1)`,
                shadow: `hsla(${hue}, 85%, 55%, 0.35)`,
            };
            this._userPaletteCache.set(key, palette);
            return palette;
        }

        _resolveUserKey(user) {
            if (!user) {
                return null;
            }
            const identifier = user.email || user.id || user.name || 'anonymous';
            if (!identifier) {
                return null;
            }
            return String(identifier).toLowerCase();
        }

        _hashStringToHue(value) {
            if (!value) {
                return 210;
            }
            const hash = this._hashString(value);
            return Math.abs(hash % 360);
        }

        _hashString(value) {
            let hash = 0;
            for (let i = 0; i < value.length; i += 1) {
                hash = (hash << 5) - hash + value.charCodeAt(i);
                hash |= 0;
            }
            return hash;
        }

        _normalizeIncomingChange(raw) {
            if (!raw || !raw.id) {
                return null;
            }
            const id = String(raw.id);
            const type = raw.type === 'delete' ? 'delete' : 'insert';
            const allowedStatuses = new Set(['pending', 'accepted', 'rejected']);
            const status = allowedStatuses.has(raw.status) ? raw.status : 'pending';
            const baseUser = this.options.user || {};
            const rawUser = raw.user || {};
            const user = {
                id: rawUser.id || baseUser.id || 'anonymous',
                name: rawUser.name || baseUser.name || 'Anonymous User',
                email: rawUser.email || baseUser.email || null,
            };
            const length = Number.isFinite(raw.length) && raw.length >= 0 ? raw.length : 0;
            const preview = this._stringPreview(typeof raw.preview === 'string' ? raw.preview : '');
            const createdAt = typeof raw.createdAt === 'string' && raw.createdAt.trim().length
                ? raw.createdAt
                : new Date().toISOString();
            const updatedAt = typeof raw.updatedAt === 'string' && raw.updatedAt.trim().length
                ? raw.updatedAt
                : createdAt;
            const normalized = {
                id,
                type,
                status,
                preview,
                createdAt,
                updatedAt,
                length,
                user,
            };
            if (status !== 'pending') {
                if (typeof raw.resolvedAt === 'string' && raw.resolvedAt.trim().length) {
                    normalized.resolvedAt = raw.resolvedAt;
                }
                if (raw.resolvedBy && typeof raw.resolvedBy === 'object') {
                    normalized.resolvedBy = { ...raw.resolvedBy };
                }
            }
            return normalized;
        }

        _splitRemovedDelta(delta) {
            const residualOps = [];
            const adjustedChangeIds = new Set();
            ensureArray(delta?.ops).forEach((op) => {
                if (!Object.prototype.hasOwnProperty.call(op, 'insert')) {
                    residualOps.push(op);
                    return;
                }
                const meta = op.attributes?.[this._blotName];
                if (this._isOwnedPendingInsert(meta)) {
                    const changeId = meta?.[this.attrNames.id];
                    if (changeId) {
                        adjustedChangeIds.add(changeId);
                    }
                    return;
                }
                residualOps.push(op);
            });
            return {
                residualDelta: { ops: residualOps },
                adjustedChangeIds,
            };
        }

        _isOwnedPendingInsert(meta) {
            if (!meta) {
                return false;
            }
            if (meta?.[this.attrNames.type] !== 'insert') {
                return false;
            }
            if (meta?.[this.attrNames.status] !== 'pending') {
                return false;
            }
            return (meta?.[this.attrNames.userId] ?? null) === this.options.user.id;
        }

        _refreshInsertChange(changeId) {
            if (!changeId) {
                return;
            }
            const change = this._ledger.get(changeId);
            if (!change) {
                return;
            }
            const range = this.findChangeRange(changeId);
            if (!range || range.length <= 0) {
                this._removeChange(changeId, 'insert-adjust-empty');
                return;
            }
            const text = this.quill.getText(range.index, range.length);
            change.length = range.length;
            change.preview = this._stringPreview(text);
            change.updatedAt = new Date().toISOString();
            this._upsertChange(change, 'insert-adjust');
        }

        _removeChange(changeId, reason = 'remove') {
            if (!changeId || !this._ledger.has(changeId)) {
                return;
            }
            const change = this._ledger.get(changeId);
            this._ledger.delete(changeId);
            if (this._insertContinuance?.id === changeId) {
                this._insertContinuance = null;
            }
            if (this._deleteContinuance?.id === changeId) {
                this._deleteContinuance = null;
            }
            this._emit('ledger-change', this.getChanges());
            this._emit('change', { reason, change });
        }

        _lookupContinuingInsert(index) {
            const now = Date.now();
            const continuance = this._insertContinuance;
            if (continuance && continuance.expires > now && continuance.index === index) {
                const change = this._ledger.get(continuance.id);
                if (change && change.type === 'insert' && change.status === 'pending' && (change.user?.id ?? null) === this.options.user.id) {
                    return change;
                }
            }
            if (index > 0) {
                const format = this.quill.getFormat(index - 1, 1)?.[this._blotName];
                const id = format?.[this.attrNames.id];
                if (id && format[this.attrNames.type] === 'insert') {
                    const change = this._ledger.get(id);
                    if (change && change.status === 'pending' && (change.user?.id ?? null) === this.options.user.id) {
                        this._insertContinuance = {
                            id,
                            index,
                            expires: now + this._continuanceWindow,
                        };
                        return change;
                    }
                }
            }
            return null;
        }

        _getBatchContext(type) {
            const batch = this._activeBatch;
            if (!batch || batch.type !== type) {
                return null;
            }
            const now = Date.now();
            if (batch.expires && now > batch.expires) {
                this._activeBatch = null;
                return null;
            }
            batch.expires = now + this._continuanceWindow;
            return batch;
        }

        _resolveBatchContext(type, continuingChange) {
            const batch = this._getBatchContext(type);
            if (!batch) {
                return null;
            }
            if (continuingChange && continuingChange.id !== batch.id) {
                return null;
            }
            if (!continuingChange && this._ledger.has(batch.id)) {
                return null;
            }
            return batch;
        }

        _lookupContinuingDelete(index) {
            const now = Date.now();
            const continuance = this._deleteContinuance;
            if (continuance && continuance.expires > now) {
                const change = this._ledger.get(continuance.id);
                if (
                    change &&
                    change.type === 'delete' &&
                    change.status === 'pending' &&
                    (change.user?.id ?? null) === this.options.user.id
                ) {
                    const range = this.findChangeRange(change.id);
                    if (range && index >= range.index && index <= range.index + range.length) {
                        return change;
                    }
                }
            }
            const neighborOffsets = [index, index - 1];
            for (const offset of neighborOffsets) {
                const candidateId = this._getDeleteIdAt(offset);
                if (!candidateId) {
                    continue;
                }
                const change = this._ledger.get(candidateId);
                if (
                    change &&
                    change.type === 'delete' &&
                    change.status === 'pending' &&
                    (change.user?.id ?? null) === this.options.user.id
                ) {
                    this._markDeleteContinuance(candidateId);
                    return change;
                }
            }
            return null;
        }

        _markDeleteContinuance(changeId) {
            if (!changeId) {
                this._deleteContinuance = null;
                return;
            }
            const range = this.findChangeRange(changeId);
            const anchorIndex = range ? range.index : 0;
            this._deleteContinuance = {
                id: changeId,
                index: anchorIndex,
                expires: Date.now() + this._continuanceWindow,
            };
        }

        _lookupAdjacentDelete(index) {
            const id = this._getDeleteIdAt(index);
            return id ? this._ledger.get(id) || null : null;
        }

        _getDeleteIdAt(position) {
            if (typeof position !== 'number' || position < 0) {
                return null;
            }
            const length = this.quill.getLength?.() ?? null;
            if (length !== null && position >= length) {
                return null;
            }
            const format = this.quill.getFormat(position, 1)?.[this._blotName];
            if (!this._isOwnedPendingDelete(format)) {
                return null;
            }
            return format?.[this.attrNames.id] || null;
        }

        _attrsFromChange(change) {
            if (!change) {
                return null;
            }
            return {
                [this.attrNames.id]: change.id,
                [this.attrNames.type]: change.type,
                [this.attrNames.status]: change.status,
                [this.attrNames.userId]: change.user?.id ?? this.options.user.id,
                [this.attrNames.userName]: change.user?.name ?? this.options.user.name,
                [this.attrNames.timestamp]: change.createdAt,
            };
        }

        _normalizeChangeFormatting(changeId) {
            if (!changeId) {
                return;
            }
            const change = this._ledger.get(changeId);
            if (!change) {
                return;
            }
            const range = this.findChangeRange(changeId);
            if (!range || range.length <= 0) {
                return;
            }
            const attrs = this._attrsFromChange(change);
            if (!attrs) {
                return;
            }
            this.quill.formatText(range.index, range.length, { [this._blotName]: attrs }, this.QuillRef.sources.SILENT);
        }

        _mergeNeighborDeletes(initialId) {
            let currentId = initialId;
            let changed = false;
            while (currentId) {
                const range = this.findChangeRange(currentId);
                if (!range) {
                    break;
                }
                const leftId = this._getDeleteIdAt(range.index - 1);
                if (leftId && leftId !== currentId) {
                    currentId = this._mergeDeletePair(leftId, currentId);
                    changed = true;
                    continue;
                }
                const rightId = this._getDeleteIdAt(range.index + range.length);
                if (rightId && rightId !== currentId) {
                    currentId = this._mergeDeletePair(currentId, rightId);
                    changed = true;
                    continue;
                }
                break;
            }
            if (changed) {
                this._emit('ledger-change', this.getChanges());
            }
            return currentId;
        }

        _mergeDeletePair(targetId, sourceId) {
            if (!targetId || !sourceId || targetId === sourceId) {
                return targetId;
            }
            const targetChange = this._ledger.get(targetId);
            const sourceChange = this._ledger.get(sourceId);
            if (!targetChange || !sourceChange) {
                return targetId;
            }
            if ((targetChange.user?.id ?? null) !== (sourceChange.user?.id ?? null)) {
                return targetId;
            }
            const targetRange = this.findChangeRange(targetId);
            const sourceRange = this.findChangeRange(sourceId);
            if (!targetRange || !sourceRange) {
                return targetId;
            }
            const attrs = this._attrsFromChange(targetChange);
            if (attrs) {
                this.quill.formatText(sourceRange.index, sourceRange.length, { [this._blotName]: attrs }, this.QuillRef.sources.SILENT);
            }
            const combinedLength = targetRange.length + sourceRange.length;
            if (sourceRange.index < targetRange.index) {
                targetChange.preview = (sourceChange.preview || '') + (targetChange.preview || '');
                targetChange.length = combinedLength;
            } else {
                targetChange.preview = (targetChange.preview || '') + (sourceChange.preview || '');
                targetChange.length = combinedLength;
            }
            targetChange.updatedAt = new Date().toISOString();
            this._ledger.set(targetId, targetChange);
            this._ledger.delete(sourceId);
            if (this._deleteContinuance?.id === sourceId) {
                this._deleteContinuance.id = targetId;
            }
            this._normalizeChangeFormatting(targetId);
            return targetId;
        }

        _isOwnedPendingDelete(meta) {
            if (!meta) {
                return false;
            }
            if (meta?.[this.attrNames.type] !== 'delete') {
                return false;
            }
            if (meta?.[this.attrNames.status] !== 'pending') {
                return false;
            }
            return (meta?.[this.attrNames.userId] ?? null) === this.options.user.id;
        }
    }

    global.QuillLiteChangeTracker = QuillLiteChangeTracker;
})(typeof window !== 'undefined' ? window : this);
