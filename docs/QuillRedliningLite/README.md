# Quill Redlining Lite (Quill2) — Implementation Notes

This document describes the **currently implemented** Quill2 “Redlining Lite” editor: how tracking works, how images/comments/print work, and how data is persisted.

> Scope: This is the *Quill2* demo page served by `QuillLiteController` and backed by the `quill_lite_documents` table. The application currently persists **a single document** (the first/only row).

---

## 1) Entry points (Routes)

Defined in `routes/web.php`:

- `GET /quill2` → `QuillLiteController@show` (`quill2.show`)
  - Renders `resources/views/quill2.blade.php`.
  - Provides initial payload: Delta, changes ledger, comments, and optional hydrated HTML.

- `POST /quill2/save` → `QuillLiteController@save` (`quill2.save`)
  - Persists `{ delta, changes, comments, text, html }` into the DB.

- `DELETE /quill2` → `QuillLiteController@destroy` (`quill2.destroy`)
  - Clears persisted state by deleting rows from `quill_lite_documents`.

- `POST /images/upload` → `ImageUploadController` (`images.upload`)
  - Middleware: `auth` + `throttle:30,1`.
  - Used by the Quill2 “Insert Image” flow.

---

## 2) Database / persistence

### 2.1 Table: `quill_lite_documents`

Created/modified by migrations:

- `database/migrations/2025_11_28_010100_create_quill_lite_documents_table.php`
- `database/migrations/2025_11_28_011200_add_html_to_quill_lite_documents.php`
- `database/migrations/2025_11_28_020500_add_comments_to_quill_lite_documents.php`

Columns (current):

- `id` (PK)
- `delta` (JSON, nullable)
- `changes` (JSON, nullable)
- `comments` (JSON, nullable)
- `text` (TEXT, nullable)
- `html` (LONGTEXT, nullable)
- `created_at`, `updated_at`

### 2.2 Model: `App\Models\QuillLiteDocument`

- File: `app/Models/QuillLiteDocument.php`
- Fillable: `delta`, `changes`, `comments`, `text`, `html`
- Casts: `delta`, `changes`, `comments` as `array` (Eloquent JSON-to-array), `html` as `string`

### 2.3 Save semantics

Backend save behavior (in `QuillLiteController@save`):

- Validates payload:
  - `delta: nullable|array`
  - `changes: nullable|array`
  - `comments: nullable|array`
  - `text: nullable|string`
  - `html: nullable|string`
- Loads the first document row (`QuillLiteDocument::query()->first()`), or creates one.
- Overwrites the stored values with the incoming snapshot.

### 2.4 Load / seed semantics

Backend load behavior (in `QuillLiteController@show`):

- Reads the first document row.
- Provides:
  - `initialDelta`: persisted delta if valid, else a simple seeded delta containing the seed text.
  - `initialChanges`: persisted change ledger array (or `[]`).
  - `initialComments`: persisted comments array (or `[]`).
  - `initialHtml`: persisted HTML (optional).
  - `quillUser`: current authenticated user mapped to `{ id, name, email }`.

---

## 3) Frontend (Blade page) layout and responsibilities

### 3.1 Main UI: `resources/views/quill2.blade.php`

This single Blade view contains:

- Editor and side panel UI (changes/comments activity)
- All client-side logic for:
  - Track-changes “ledger” UI + accept/reject
  - Redline vs Clean Draft view styles
  - Comments (anchoring + disconnected handling)
  - Image insert/crop/upload + image resize/alignment persistence
  - Print preview and print
  - Paste sanitization
  - Performance throttling (debounced decoration)

### 3.2 Boot sequence

Key steps:

1. `initQuillLite()` waits for:
   - `window.Quill`
   - `window.QuillLiteChangeTracker`
   - Optional: `quill-table-better` readiness (bounded wait)

2. Registers some Quill formats:
   - Font whitelist: `serif`, `monospace`.

3. Registers a custom image format (see “Images” section) to persist width/height/alignment and to preserve track-change attributes when hydrating HTML.

---

## 4) Track changes engine (QuillLiteChangeTracker)

### 4.1 File

- `public/js/quill-lite-change-tracker.js`

### 4.2 Core concepts

- Each tracked change is assigned an ID (attribute prefix default: `q2`).
- The tracker maintains an in-memory **ledger** (`Map`) of changes and emits events when it changes.
- Changes are applied by writing attributes onto content in the Quill document.

### 4.3 Public API (commonly used)

- `enableTracking()` / `disableTracking()`
- `setCurrentUser(user)`
- `snapshot()`
  - Returns `{ text, delta, changes }` (changes sorted ascending).
- `loadChanges(changes)`
  - Replaces in-memory ledger from DB (or hydrated DOM) and emits `ledger-change`.
- `getChanges({ sort })`, `getChange(id)`
- `acceptChange(id)`, `rejectChange(id)`, plus `acceptAll()` / `rejectAll()`
- `findChangeRange(id)`
  - Computes the Quill index/length of a change by scanning the Delta.
- `withBatchChange(type, fn)`
  - Creates a batch ID to group related edits into a single change ID over a short time window.

### 4.4 How tracking hooks into Quill

The tracker attaches:

- `quill.on('text-change', ...)`
  - On user edits, it examines the delta and records insert/delete operations.

- A root `keydown` listener
  - Captures whether deletes are forward/backward (`Delete` vs `Backspace`) so the caret can be restored in an intuitive position after reinserting a redlined deletion.

### 4.5 Insert tracking

- For inserted text: tracker applies a `q2-change`-style attribute to the inserted range, and inserts/extends a ledger record.
- For embeds (images, etc.): tracker treats embeds as a length-1 insert and records a change with a preview placeholder (currently `"[embed]"`).

### 4.6 Delete tracking (redline deletions)

When the user deletes content:

- The tracker slices the removed segment from the **old delta**, then reinserts it with “delete” tracking attributes.
- **Important embed behavior**: embeds cannot reliably carry object-valued attrs inside inserted delta ops, so the tracker:
  1) reinserts the embed with its preserved attributes (but without the tracker attribute), then
  2) applies the tracker attribute with `formatText()` for that embed range.

This is the key mechanism that prevents deleted images/embeds from “disappearing” instead of being redlined.

### 4.7 Structural deletes (tables)

Some deletes are treated as structural (especially for table tool integrations) and are intentionally not redlined to avoid corrupting table structure.

- The tracker detects structured deletes via attribute names (e.g., `table`, `list`, `header`, etc.)
- Additionally, table-related embeds are treated as “table-ish” and filtered from normal embed insert/delete flows.
- Non-table embeds (e.g., images) are NOT treated as table structure and are redlined normally.

---

## 5) Redline vs Clean Draft view

The UI supports at least two presentation modes:

- **Redline**
  - Pending inserts and deletes are visibly marked.
  - Deleted content remains visible but styled as a deletion.

- **Clean Draft**
  - A “clean” view hides deletion visuals and removes insert styling.
  - This is implemented as a view-mode styling transformation (and some cleanup/pruning of presentational artifacts), not as destructive data loss.

The print preview mirrors these modes (see Print section).

---

## 6) Images (insert, crop, upload, resize, align, persistence)

### 6.1 Client flow (high level)

- Toolbar button triggers an image modal.
- CropperJS is **lazy-loaded** only when needed (script injected dynamically).
- User selects an image, crops it client-side, then the cropped blob is uploaded to `/images/upload`.
- Backend returns `{ location: "<public url>" }`.
- Client inserts an image embed at the current selection.

### 6.2 Backend upload hardening

`ImageUploadController` implements:

- Request validation:
  - Required file, max 5MB
  - MIME allowlist: `image/jpeg`, `image/png`, `image/webp`
- Content verification:
  - Reads bytes and validates via `getimagesizefromstring()` + `imagecreatefromstring()`
- Dimension guardrails:
  - width/height must be > 0
  - width/height <= 12000
  - total pixels <= 50,000,000
- Re-encode using GD to strip metadata/EXIF:
  - Outputs jpg/png/webp (webp depends on GD support)
- Stores to public disk:
  - `Storage::disk('public')->put('uploads/<random>.<ext>', ...)`
- Returns a URL under the public disk URL (commonly `/storage/uploads/...`).

### 6.3 Image resize + alignment persistence

The editor persists image presentation via Quill formats / DOM attributes:

- `data-q2-img-w` / `data-q2-img-h` (and inline style width/height)
- `data-q2-img-align` with simple block alignment behavior:
  - left: margin-right auto
  - center: margins auto
  - right: margin-left auto

A custom `Q2Image` format extends Quill’s image format so these attributes are:

- Applied on create
- Returned in `formats(node)`

This makes width/height/alignment survive:

- Saving/restoring via Delta
- HTML hydration
- Copy/paste of image nodes (via Quill clipboard matchers)

### 6.4 Track-changes with images

- Image inserts are tracked as embed inserts.
- Image deletes are tracked as embed deletes (reinserted with delete meta).
- Redline UI for images is implemented via tracker attributes living on the image DOM.

---

## 7) Comments

Comments are maintained client-side as an array (`commentLog`) and persisted in `quill_lite_documents.comments`.

Each comment carries (conceptually):

- `id`
- `body`
- `createdAt`
- `range: { index, length }`
- `disconnected: boolean` (persisted)

### 7.1 Disconnected comment behavior (important)

A comment is treated as disconnected when the originally-commented text range is effectively gone.

Implementation details:

- `isCommentConnected(comment)` returns false if:
  - `comment.disconnected` is true
  - range invalid/out of bounds
  - current text in the range normalizes to empty (whitespace/newlines/zero-width chars)

- `disconnectAbandonedComments()` runs:
  - On init
  - On every `text-change`
  - Before save

This prevents "old" comments from re-attaching to newly typed text after refresh.

---

## 8) Paste sanitization

The editor includes a paste handler that tries to reduce unsafe/undesirable HTML.

Key pieces:

- Reads clipboard HTML + plain text when available.
- Builds a “safe HTML” fragment from either:
  - sanitized HTML
  - or a plain-text-to-HTML conversion
- Inserts with `quill.clipboard.dangerouslyPasteHTML(...)` inside `tracker.withBatchChange('insert', ...)` so the paste is tracked as a single insert batch.

After insertion, the UI schedules decoration refreshes.

---

## 9) Print preview

- A toolbar “Print” button opens a modal with an iframe.
- The iframe uses a generated `srcdoc` that mirrors the editor DOM structure so the same redline CSS selectors apply.
- The print modal includes a “Print” button that triggers `iframe.contentWindow.print()`.
- The Quill “snow” border is overridden in the print CSS to avoid the default editor chrome.

---

## 10) Performance notes

To reduce typing lag, the UI debounces expensive passes that scan/annotate the document:

- Tooltip refresh
- Table/list decoration

Key helper:

- `scheduleDecorations()` (debounced ~150ms)

Called on:

- `quill.on('text-change', ...)`
- Some other user actions that previously triggered immediate full refresh

This keeps the UI responsive during rapid edits.

---

## 11) Known limitations / current design decisions

- Single-document persistence: the DB stores only the first row; no multi-document routing yet.
- Comments are range-based (index/length). Track-changes can shift indices; “disconnected” is used to prevent incorrect reattachment.
- Structural table deletes are intentionally not redlined to preserve table integrity.
- Uploads allow JPEG/PNG/WebP only.

---

## 12) Where to look in code (quick index)

- UI + client logic: `resources/views/quill2.blade.php`
- Track changes engine: `public/js/quill-lite-change-tracker.js`
- Persistence controller: `app/Http/Controllers/QuillLiteController.php`
- Image upload controller: `app/Http/Controllers/ImageUploadController.php`
- DB migrations: `database/migrations/*quill_lite_documents*`
- Model: `app/Models/QuillLiteDocument.php`
