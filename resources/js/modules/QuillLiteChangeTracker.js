const DEFAULT_OPTIONS = {
    attrPrefix: 'q2',
    autoTrack: true,
    source: null,
    user: {
        id: 'anonymous',
        name: 'Anonymous User',
    },
};

const EMBED_PLACEHOLDER = '[embed]';
const PREVIEW_MAX_LENGTH = 320;
const LINEBREAK_MARKER = '\u23ce';

const ensureArray = (value) => (Array.isArray(value) ? value : []);

export default class QuillLiteChangeTracker {
    constructor(quill, options = {}) {
        if (!quill) {
            throw new Error('QuillLiteChangeTracker requires a Quill instance');
        }
        if (typeof window === 'undefined' || !window.Quill) {
            throw new Error('QuillLiteChangeTracker requires window.Quill to be loaded');
        }

        this.quill = quill;
        this.QuillRef = window.Quill;
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
        this._ledger = new Map();
        this._listeners = new Map();
        this._trackingEnabled = Boolean(this.options.autoTrack);
        this._lastCursor = 0;
        this._deleteContinuance = null;

        this._registerAttributes();
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
            if (op.attributes?.[this.attrNames.id] === changeId) {
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
                return;
            }
        });
    }

    _processInsert(index, text) {
        if (!text) {
            return;
        }
        const id = this._generateId();
        const length = text.length;
        const timestamp = new Date().toISOString();
        const attrs = this._composeAttrs({
            id,
            type: 'insert',
            status: 'pending',
            timestamp,
        });
        this.quill.formatText(index, length, attrs, this.QuillRef.sources.SILENT);
        const change = {
            id,
            type: 'insert',
            status: 'pending',
            preview: this._stringPreview(text),
            createdAt: timestamp,
            updatedAt: timestamp,
            length,
            user: { ...this.options.user },
        };
        this._upsertChange(change, 'insert');
    }

    _processDelete(index, length, oldDelta) {
        if (length <= 0) {
            return;
        }
        const removed = oldDelta.slice(index, index + length);
        const { residualDelta, adjustedChangeIds } = this._splitRemovedDelta(removed);
        if (adjustedChangeIds.size) {
            adjustedChangeIds.forEach((changeId) => this._refreshInsertChange(changeId));
        }
        const deltaLength = this._measureDeltaLength(residualDelta);
        if (deltaLength <= 0) {
            return;
        }
        const preview = this._previewFromDelta(residualDelta);
        const continuingChange = this._lookupContinuingDelete(index);
        const adjacentChange = this._lookupAdjacentDelete(index);
        const targetChange = continuingChange || adjacentChange;
        const id = targetChange?.id ?? this._generateId();
        const timestamp = new Date().toISOString();
        const changeTimestamp = targetChange?.createdAt ?? timestamp;
        const attrs = this._composeAttrs({
            id,
            type: 'delete',
            status: 'pending',
            timestamp: changeTimestamp,
        });
        let insertionDelta = new this.Delta().retain(index);
        ensureArray(residualDelta.ops).forEach((op) => {
            if (Object.prototype.hasOwnProperty.call(op, 'insert')) {
                insertionDelta = insertionDelta.insert(op.insert, attrs);
            }
        });
        this.quill.updateContents(insertionDelta, this.QuillRef.sources.SILENT);
        this.quill.setSelection(index, 0, this.QuillRef.sources.SILENT);
        if (targetChange) {
            targetChange.length += deltaLength;
            targetChange.preview = (targetChange.preview || '') + preview;
            targetChange.updatedAt = timestamp;
            this._ledger.set(id, targetChange);
            this._emit('ledger-change', this.getChanges());
            this._emit('change', { reason: 'delete-extend', change: targetChange });
        } else {
            const change = {
                id,
                type: 'delete',
                status: 'pending',
                preview,
                createdAt: changeTimestamp,
                updatedAt: timestamp,
                length: deltaLength,
                user: { ...this.options.user },
            };
            this._upsertChange(change, 'delete');
        }
        this._deleteContinuance = {
            id,
            index,
            expires: Number.POSITIVE_INFINITY,
        };
        const mergedId = this._mergeNeighborDeletes(id);
        if (this._deleteContinuance?.id === id && mergedId !== id) {
            this._deleteContinuance.id = mergedId;
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
        const resetAttrs = {
            [this.attrNames.id]: false,
            [this.attrNames.type]: false,
            [this.attrNames.status]: false,
            [this.attrNames.userId]: false,
            [this.attrNames.userName]: false,
            [this.attrNames.timestamp]: false,
        };
        this.quill.formatText(range.index, range.length, resetAttrs, this.QuillRef.sources.SILENT);
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

    _registerAttributes() {
        const registry = (typeof window !== 'undefined' && window.__quillLiteAttrRegistry) || new Set();
        if (typeof window !== 'undefined' && !window.__quillLiteAttrRegistry) {
            window.__quillLiteAttrRegistry = registry;
        }
        Object.values(this.attrNames).forEach((name) => {
            if (registry.has(name)) {
                return;
            }
            const attr = new this.Parchment.Attributor.Attribute(name, `data-${name}`, {
                scope: this.Parchment.Scope.INLINE,
            });
            this.QuillRef.register(attr, true);
            registry.add(name);
        });
    }

    _buildAttrNames(prefix = 'q2') {
        return {
            id: `${prefix}-change-id`,
            type: `${prefix}-change-type`,
            status: `${prefix}-change-status`,
            userId: `${prefix}-user-id`,
            userName: `${prefix}-user-name`,
            timestamp: `${prefix}-timestamp`,
        };
    }

    _composeAttrs({ id, type, status, timestamp }) {
        return {
            [this.attrNames.id]: id,
            [this.attrNames.type]: type,
            [this.attrNames.status]: status,
            [this.attrNames.userId]: this.options.user.id,
            [this.attrNames.userName]: this.options.user.name,
            [this.attrNames.timestamp]: timestamp,
        };
    }

    _generateId() {
        if (typeof window !== 'undefined' && window.crypto?.randomUUID) {
            return window.crypto.randomUUID();
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
                return;
            }
            if (Object.prototype.hasOwnProperty.call(op, 'insert')) {
                parts.push(EMBED_PLACEHOLDER);
            }
        });
        return this._stringPreview(parts.join(''));
    }

    _lookupContinuingDelete(index) {
        const continuance = this._deleteContinuance;
        if (!continuance || continuance.index !== index) {
            return null;
        }
        const change = this._ledger.get(continuance.id);
        if (!change || change.type !== 'delete' || change.status !== 'pending') {
            return null;
        }
        if ((change.user?.id ?? null) !== this.options.user.id) {
            return null;
        }
        return change;
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
        const format = this.quill.getFormat(position, 1) || {};
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
            this.quill.formatText(sourceRange.index, sourceRange.length, attrs, this.QuillRef.sources.SILENT);
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

    _splitRemovedDelta(delta) {
        const residualOps = [];
        const adjustedChangeIds = new Set();
        ensureArray(delta?.ops).forEach((op) => {
            if (!Object.prototype.hasOwnProperty.call(op, 'insert')) {
                residualOps.push(op);
                return;
            }
            const attrs = op.attributes || null;
            if (this._isOwnedPendingInsert(attrs)) {
                const changeId = attrs?.[this.attrNames.id];
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

    _isOwnedPendingInsert(attrs) {
        if (!attrs) {
            return false;
        }
        if (attrs?.[this.attrNames.type] !== 'insert') {
            return false;
        }
        if (attrs?.[this.attrNames.status] !== 'pending') {
            return false;
        }
        return (attrs?.[this.attrNames.userId] ?? null) === this.options.user.id;
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
        this._emit('ledger-change', this.getChanges());
        this._emit('change', { reason, change });
    }
}
