# Quill Lite Plugin Parity

## Goals
- Mirror the ICE TinyMCE plugin surface (AddTitle, CopyPaste, SmartQuotes, Emdash) with Quill-friendly counterparts.
- Preserve metadata guarantees required by `QuillLiteChangeTracker` (batch IDs, user attributes, delete placeholders).
- Keep the surface area modular so individual plugins can be enabled/disabled per tenant.

## Plugin Mapping
### 1. Change Tooltip (ICE AddTitle)
- **Trigger**: whenever a blot with `data-q2-change-id` is rendered or hovered.
- **Implementation**:
  1. Extend the tracker listener (`change` event) to emit lightweight metadata `{id, user, createdAt, preview}`.
  2. Create a small DOM utility that attaches `title` attributes or a custom tooltip component to rendered change spans when Quill updates (`text-change` + `selection-change`).
  3. Respect clean-view mode by suspending tooltip injection while change markup is hidden.

### 2. Sanitized Paste (ICE CopyPaste)
- **Trigger**: Quill `editor.clipboard.addMatcher(Node.TEXT_NODE, …)`
- **Implementation**:
  1. Route clipboard HTML through the existing Laravel sanitizer, then run client-side rules (strip `<meta>`, `<style>`, office markup) that mirror `IceCopyPastePlugin`.
  2. Wrap the normalized delta application inside `tracker.withBatchChange('insert', () => quill.updateContents(delta))` so the entire paste keeps one change id.
  3. Rehydrate delete placeholders before sanitizing and restore them afterwards to avoid losing tracked deletions.

### 3. Smart Quotes (ICE SmartQuotes)
- **Trigger**: `text-change` events caused by printable characters.
- **Implementation**:
  1. Inspect the character(s) the user just inserted and run the ICE heuristics (context-aware apostrophes, double quotes).
  2. When a substitution is needed, wrap it inside `withBatchChange('insert', …)` so the adjusted glyph reuses the user’s active change.
  3. Respect no-track regions by skipping when the caret is inside a `data-q2-change-type="delete"` span or a component flagged with `data-no-track`.

### 4. Em Dash Autocomplete (ICE Emdash)
- **Trigger**: key combos `--`, `Ctrl + -`, or platform-specific dash shortcuts.
- **Implementation**:
  1. Listen to `keyboard` module bindings, detect dash sequences, and replace them with `` while preserving selection.
  2. Coalesce the replacement into the currently active insert change (uses `_lookupContinuingInsert`), falling back to a fresh change id if necessary.

## Sequencing
1. **Telemetry & Hooks** – expose tracker listeners for tooltip + plugin state (Week 1).
2. **Batch-safe Clipboard Pipeline** – implement sanitized paste with delete placeholders + batching (Week 1-2).
3. **Smart Quotes / Em Dash** – port heuristics and tie them into tracker continuance rules (Week 2).
4. **QA + Toggle Flags** – add config switches per plugin, document usage in `README.md` (Week 3).
