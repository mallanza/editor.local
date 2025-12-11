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

    const EMBED_PLACEHOLDER = '[embed]';
    const PREVIEW_MAX_LENGTH = 320;
    const LINEBREAK_MARKER = '\u23ce';

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
                if (!change || !change.id) {
                    return;
                }
                this._ledger.set(change.id, { ...change });
            });
            this._emit('ledger-change', this.getChanges());
        }

        findChangeRange(changeId) {
            if (!changeId) {
                return null;
            }
            const contents = this.quill.getContents();
            let cursor = 0;
            let start = null;
            let end = null;
            ensureArray(contents.ops).forEach((op) => {
                if (!Object.prototype.hasOwnProperty.call(op, 'insert')) {
                    cursor += typeof op.retain === 'number' ? op.retain : 0;
                    return;
                }
                const opLength = typeof op.insert === 'string' ? op.insert.length : 1;
                const changeMeta = op.attributes?.[this._blotName];
                if (changeMeta?.[this.attrNames.id] === changeId) {
                    if (start === null) {
                        start = cursor;
                    }
                    end = cursor + opLength;
                }
                cursor += opLength;
            });
            if (start === null) {
                return null;
            }
            const length = Math.max(0, (end ?? start) - start);
            return { index: start, length };
        }

        _bind() {
            this._boundTextChange = (delta, oldDelta, source) => {
                if (!this._trackingEnabled || source !== this.options.source) {
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
                if (typeof op.delete === 'number') {
                    this._processDelete(this._lastCursor, op.delete, oldDelta);
                }
            });
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
            this.quill.formatText(index, length, { [this._blotName]: attrs }, this.QuillRef.sources.SILENT);
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
            if (this._isStructuralDelete(residualDelta)) {
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
            ensureArray(residualDelta.ops).forEach((op) => {
                if (!Object.prototype.hasOwnProperty.call(op, 'insert')) {
                    return;
                }
                const preservedAttrs = op.attributes ? { ...op.attributes } : {};
                if (Object.prototype.hasOwnProperty.call(preservedAttrs, this._blotName)) {
                    delete preservedAttrs[this._blotName];
                }
                const mergedAttrs = { ...preservedAttrs, [this._blotName]: attrs };
                insertionDelta = insertionDelta.insert(op.insert, mergedAttrs);
            });
            this.quill.updateContents(insertionDelta, this.QuillRef.sources.SILENT);
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
            const range = this.findChangeRange(changeId);
            if (range) {
                if (change.type === 'insert') {
                    if (action === 'accept') {
                        this._resetFormatting(range);
                    } else {
                        this.quill.deleteText(range.index, range.length, this.QuillRef.sources.SILENT);
                    }
                } else if (change.type === 'delete') {
                    if (action === 'accept') {
                        this.quill.deleteText(range.index, range.length, this.QuillRef.sources.SILENT);
                    } else {
                        this._resetFormatting(range);
                    }
                }
            }
            const resolution = action === 'accept' ? 'accepted' : 'rejected';
            change.status = resolution;
            change.resolvedAt = new Date().toISOString();
            change.resolvedBy = { ...this.options.user };
            this._upsertChange(change, 'resolve');
            return change;
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
            return Object.keys(attrs).some((attrName) => /^(table|ql-table)/i.test(attrName) || attrName.includes('table-'));
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
            return value;
        }

        _clearDataset(node) {
            if (!node) {
                return;
            }
            Object.values(this.attrNames).forEach((attrName) => {
                node.removeAttribute(attrName);
            });
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
