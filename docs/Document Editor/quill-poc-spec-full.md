# Quill Proof-of-Concept Editor (Laravel 12)  
## Track Changes • Comments • Redline / Clean • Save Button  
## Implementation Plan for VS Code / Codex

---

# Quill Editor Proof-of-Concept – Plain English Specification

This Proof-of-Concept (PoC) implements a standalone rich-text editor page located at **/quill**. It is intentionally isolated and is **not integrated** into any existing system components. The purpose is to validate the feasibility of using Quill to support tracked changes, inline comments, and view modes before integrating these capabilities into the larger MapGRC platform.

The PoC represents **one shared document**. All users load and edit the same stored document. There is **no realtime collaboration**, no WebSockets, and no user-to-user live updates. All changes are persisted only when the user manually clicks Save.

The PoC must provide the following capabilities:

## Rich Text Editing
- Quill editor with Snow theme.
- Standard formatting tools: bold, italic, underline, strikethrough, headers, lists, links, blockquote, and code block.
- Full **table functionality**, including inserting tables and adding/removing rows and columns.

## Manual Save Workflow
- A visible Save button above the editor.
- Clicking Save sends the full Quill Delta to the server.
- Server stores the Delta in a `quill_documents` table.
- A “Last saved X minutes ago” indicator updates after each successful save.
- Autosave is intentionally excluded from the PoC.

## Commenting System
- User selects text and chooses **Add Comment**.
- A modal or panel appears to enter comment text.
- Each comment is anchored using the Quill selection index and length.
- Comments cannot be deleted; they can only change status:
  - **Active**
  - **Resolved**
  - **Closed**
- A comments panel lists all comments with author, timestamp, and status.
- Clicking a comment scrolls the editor to the associated text.
- UI includes filters to show/hide comments by status.

## Track Changes
- All user edits must be tracked using Quill custom attributes:
  - Insertions are highlighted and attributed to the author with timestamp.
  - Deletions remain visible as strikethrough text rather than being removed.
- Each tracked change carries:
  - Unique change ID
  - Change type (insert or delete)
  - Author name and ID
  - Timestamp
- Hovering tracked text can show a tooltip with author and timestamp.

## Accept / Reject Change Actions
- When the cursor is inside a tracked change, an inline bubble shows **Accept** and **Reject**.
- Accepting an insertion keeps the text and removes tracking markers.
- Rejecting an insertion removes the text entirely.
- Accepting a deletion removes the deleted text from the document.
- Rejecting a deletion restores the original text.
- After action, the editor re-renders with updated content.

## View Modes: Redline and Clean
- **Redline view** (default):
  - Shows all tracked changes (insertions, deletions).
  - Shows comments normally.
  - Allows accept/reject actions.
- **Clean view**:
  - Hides all tracked-change indicators.
  - Removes deleted text.
  - Shows only the clean “accepted” view of the document.
  - Does not allow accept/reject actions.
- A toggle button switches between Redline and Clean views.

## Technology Requirements
- Use **Blade + AlpineJS** for the UI state and toggles.
- Use plain AJAX (or fetch) for saving, loading, comment actions, and accept/reject actions.
- Do **not** embed the Quill editor inside a Livewire component.
- Routes, controllers, and migrations must be minimal and PoC-focused.

This PoC should be developed in clear phases so it can be fed into Codex step-by-step. Each phase introduces one isolated feature: migrations, basic editor, Save, comments, tracked changes, accept/reject workflow, and view modes.


## OVERVIEW

This PoC creates a standalone Quill editor page at:

`GET /quill`


This document contains step-by-step implementation instructions in small, digestible chunks for Codex.

Use Blade + Quill v2 + Alpine where needed

Noever use migrate:fresh


---

# ============================
#  PHASE 1 — BASIC SETUP
# ============================

## 1. Create database tables

Create 3 tables:

### Table: `quill_documents`
- `id` (PK)
- `title` (string, nullable)
- `content_delta` (longtext JSON)
- `base_delta` (longtext JSON, nullable) — for clean view
- `version` (int, default 1)
- `created_at`, `updated_at`

### Table: `quill_comments`
- `id` (PK)
- `document_id` (FK)
- `user_id`
- `user_name`
- `anchor_index` (int)
- `anchor_length` (int)
- `body` (text)
- `status` (enum: `active`, `resolved`, `closed`)
- `created_at`, `updated_at`

### Table: `quill_changes`
- `id` (PK)
- `document_id` (FK)
- `change_uuid` (string, unique)
- `user_id`
- `user_name`
- `change_type` (enum: `insert`, `delete`)
- `status` (enum: `pending`, `accepted`, `rejected`)
- `delta` (json)
- `created_at`, `updated_at`

---

## 2. Create route definitions

Add to `routes/web.php`:

- `GET  /quill` – show editor
- `POST /quill/save` – save full document
- `POST /quill/comments` – create comment
- `PATCH /quill/comments/{id}` – update comment status
- `GET  /quill/comments` – list comments (with filters)
- `POST /quill/changes` – (optional) register new changes
- `POST /quill/changes/{uuid}/accept` – accept change
- `POST /quill/changes/{uuid}/reject` – reject change

---

## 3. Create controllers

Create controllers with these responsibilities:

### `QuillPoCController`
- `show()` — load/create document, pass delta + comments to Blade.
- `save()` — save full delta and increment version.

### `QuillCommentController`
- `store()` — add comment.
- `update()` — change comment status.
- `index()` — list comments with optional status filters.

### `QuillChangeController`
- `store()` — register new change (optional for PoC).
- `accept()` — accept a change.
- `reject()` — reject a change.

---

## 4. Create Blade view: `resources/views/quill.blade.php`

The view should contain:

- A top toolbar area with:
  - Save button
  - Redline/Clean toggle
  - “Add comment” button
  - Comment filter controls
- The Quill toolbar container.
- The Quill editor container.
- A right-hand (or bottom) panel for:
  - Comments list
  - (Optional) Changes list.

At this phase, the containers can be static HTML with no JS logic yet.

---

# ============================
#  PHASE 2 — BASIC EDITOR + SAVE
# ============================

## 1. Install Quill + Table support

In the Blade template for `/quill`:

- Include:
  - Quill core JS.
  - Quill Snow CSS (or similar theme).
  - A Quill table plugin such as `quill-better-table` (or another preferred table module).

Initialize Quill in a script block:

- Use a toolbar configuration that supports:
  - Bold, italic, underline, strike-through.
  - Headers (H1, H2, H3).
  - Ordered and unordered lists.
  - Indent / outdent.
  - Links.
  - Blockquote.
  - Code block (optional).
  - Table insertion and table editing tools.

Ensure Quill is initialized with:

```js
var quill = new Quill('#editor', {
  theme: 'snow',
  modules: {
    toolbar: '#toolbar',
    table: true // or specific config as required by the chosen table module
  }
});
```

---

## 2. Load initial document content

On page load:

- Backend passes `content_delta` from `quill_documents` to the view as JSON.
- If `content_delta` is null:
  - Initialize editor with an empty Delta.
- If not null:
  - Use `quill.setContents(parsedDelta)` after initialization.

---

## 3. Create Save button flow

In the Blade view:

- Create a “Save” button.
- On click:
  1. Extract the full Delta via `quill.getContents()`.
  2. Send an AJAX POST (`fetch` or Axios) to `/quill/save` with:
     - `document_id`
     - `content_delta` (stringified JSON)
     - Current `version` if you include it in the payload.
  3. On backend:
     - Decode JSON.
     - Update `quill_documents.content_delta`.
     - Increment `version`.
  4. Return JSON with:
     - `status: "ok"`
     - `saved_at` timestamp
     - Updated `version`.
  5. On success:
     - Update a “Last saved X seconds ago” indicator in the UI.

For PoC:
- No autosave is required.
- Only manual Save via button.

---

# ============================
#  PHASE 3 — COMMENTS SYSTEM
# ============================

## 1. Highlight selected text for comment

Add “Add Comment” button functionality:

1. User highlights text in the Quill editor.
2. User clicks “Add Comment” button.
3. In JS:
   - Call `quill.getSelection()` to get `{ index, length }`.
   - If no selection or `length === 0`:
     - Show a small alert or message: “Select text to comment on.”
4. Show a modal (or side panel input) that allows entering comment text (`body`).
5. On submitting the comment form:
   - POST to `/quill/comments` with:
     - `document_id`
     - `anchor_index`
     - `anchor_length`
     - `body`
6. Backend:
   - Creates `QuillComment` with:
     - `status = 'active'`
     - `user_id`, `user_name` from `Auth::user()`
   - Returns comment data (id, status, timestamps, etc.).
7. Frontend:
   - Apply Quill format to that range:

```js
quill.formatText(index, length, {
  'comment-id': createdComment.id
});
```

   - Add the new comment entry to the comments panel.

---

## 2. Comment attribute in Quill

Define a custom inline format attribute:

- Name: `comment-id`.
- The presence of `comment-id` on text means that text has at least one comment anchored there.

Visually:

- Style via CSS:
  - e.g. subtle background or dotted underline.

---

## 3. Comments panel UI

In the right-hand side panel:

- Display comments in a list.
- For each comment, show:
  - Comment text (`body`).
  - Author (`user_name`).
  - Timestamp (`created_at`).
  - Status badge (Active / Resolved / Closed).
- Clicking a comment:
  - Scrolls the Quill editor to the associated anchor range (`anchor_index`).
  - You can use `quill.setSelection(anchor_index, anchor_length, 'api')` and temporarily highlight.

---

## 4. Comment status changes (no delete)

Each comment can have these statuses:

- `active`
- `resolved`
- `closed`

Rules:

- Comments cannot be deleted in this PoC.
- Status can be changed back and forth.

UI:

- Provide a dropdown or small buttons on each comment entry:
  - “Set Active”
  - “Resolve”
  - “Close”

Flow:

- When user changes status:
  - Send PATCH request to `/quill/comments/{id}` with the new `status`.
  - Backend updates and returns the updated comment.
  - Update the UI accordingly.

---

## 5. Comment filters

Provide checkboxes or a multi-select for comment filtering in the UI:

- “Show Active”
- “Show Resolved”
- “Show Closed”

Behavior:

- If filtering is done client-side:
  - Keep all comments loaded in memory and hide/show based on selected filters.
- Alternatively, support server-side filtering via:
  - `GET /quill/comments?status[]=active&status[]=resolved`

For PoC:
- Client-side filtering is sufficient and simpler.

---

# ============================
#  PHASE 4 — TRACK CHANGES
# ============================

## 1. Define custom Quill formats for track changes

We need to track metadata on individual text operations.

Define inline format attributes:

- `tc-change-id` (string/uuid)
- `tc-change-type` (`insert` | `delete`)
- `tc-author-id` (int/string)
- `tc-author-name` (string)
- `tc-timestamp` (string ISO or epoch)

These attributes will be attached to inserted or “deleted” text.

---

## 2. Detect and mark insertions

In the Quill `text-change` handler:

```js
quill.on('text-change', function(delta, oldDelta, source) {
  if (source !== 'user') return;

  // Inspect delta.ops to find insert operations
});
```

For each user-generated **insert** operation:

- Generate a `change_uuid` (e.g. via a small UUID function).
- Attach attributes:

```js
{
  insert: 'text',
  attributes: {
    'tc-change-id': change_uuid,
    'tc-change-type': 'insert',
    'tc-author-id': currentUserId,
    'tc-author-name': currentUserName,
    'tc-timestamp': currentTimestamp
  }
}
```

- This can be done by:
  - Transforming the delta before applying it, or
  - Applying a second format step right after insertion for the specific range.

Visual styling in Redline mode:

- Inserted text should be highlighted (background color or underline).

Optional for PoC:

- You may skip persisting `quill_changes` rows on each character typed and rely solely on the delta attributes.

---

## 3. Detect and mark deletions (Word-style)

Instead of removing text outright:

- When a deletion occurs (e.g. backspace, delete key), detect the range that would normally be removed.
- Capture the text and its location before removal.
- Instead of removing it:
  - Re-apply it with track-change attributes:

```js
{
  insert: 'deleted text',
  attributes: {
    'tc-change-id': change_uuid,
    'tc-change-type': 'delete',
    'tc-author-id': currentUserId,
    'tc-author-name': currentUserName,
    'tc-timestamp': currentTimestamp
  }
}
```

- Style deleted text as:
  - Strikethrough.
  - More muted color.

This simulates the typical redline deletion style.

---

## 4. Show tooltips on hover (optional but nice)

For both inserted and deleted text segments:

- On hover, show a tooltip with:
  - “Added by {author_name} on {timestamp}”
  - “Deleted by {author_name} on {timestamp}”

Implementation details:

- Use DOM inspection to detect spans with `data-*` attributes corresponding to `tc-*`.
- Attach a simple tooltip library or a custom tooltip component.

---

# ============================
#  PHASE 5 — ACCEPT / REJECT CHANGES
# ============================

## 1. Inline change controls

When the cursor is placed inside a text segment that has a `tc-change-id` attribute:

- Show a small floating bubble near the selection or at the top of the editor containing:
  - “Accept”
  - “Reject”

Implementation idea:

- On `selection-change`, check the formats at the selection index via `quill.getFormat()`.
- If `tc-change-id` exists, show the bubble.
- If not, hide the bubble.

---

## 2. Backend accept logic

Endpoint: `POST /quill/changes/{change_uuid}/accept`

Steps:

1. Load the `quill_documents.content_delta`.
2. Locate the portion of the Delta that has `tc-change-id = change_uuid`.
3. For **insert** changes:
   - Remove `tc-*` attributes from that text segment.
   - Leave the text itself unchanged.
4. For **delete** changes:
   - Remove the “deleted” text from the document entirely (it disappears).
5. Update `quill_changes.status` to `accepted` (if you are persisting changes).
6. Save the updated `content_delta`.

---

## 3. Backend reject logic

Endpoint: `POST /quill/changes/{change_uuid}/reject`

Steps:

1. Load `quill_documents.content_delta`.
2. Locate the segment with `tc-change-id = change_uuid`.
3. For **insert** changes:
   - Remove the inserted text entirely from the document.
4. For **delete** changes:
   - Restore the original text if you’re storing it separately.
   - In a simpler PoC scenario:
     - For delete segments, just remove `tc-*` attributes and the text remains (as if it was never marked deleted).
5. Update `quill_changes.status` to `rejected`.
6. Save updated `content_delta`.

---

## 4. Client-side behavior after accept/reject

When user clicks Accept or Reject in the bubble:

- Send POST to the appropriate endpoint with `change_uuid`.
- On success:
  - Simplest approach for PoC:
    - Re-fetch the latest `content_delta` from backend (`GET /quill` JSON endpoint or reuse the same controller).
    - Call `quill.setContents(newDelta)` to re-render.
  - This avoids complex client-side patching.

---

# ============================
#  PHASE 6 — REDLINE vs CLEAN VIEW
# ============================

## 1. View modes overview

There are two modes:

### Mode A: Redline
- Show all tracked changes with:
  - Insertions highlighted.
  - Deletions with strikethrough.
- Comments visible/normal.
- Accept/Reject controls enabled.

### Mode B: Clean
- Show document as if all pending changes were accepted:
  - All `tc-*` attributes removed.
  - All `tc-change-type='delete'` text hidden or removed from this view.
- Comments can optionally remain visible.
- Accept/Reject controls hidden.

---

## 2. Mode toggle UI

In the toolbar area, add a toggle:

- Could be:
  - A two-state button: “Redline / Clean”.
  - Or a dropdown: “View: Redline / Clean”.

Mode should initially default to **Redline**.

---

## 3. Implementation strategy for view modes

### Approach for PoC (recommended):

- Keep original `content_delta` unchanged in memory.
- To render **Clean** view:
  1. Clone the `content_delta` object.
  2. Process the cloned Delta:
     - Remove all operations that are purely deletions (`tc-change-type='delete'`).
     - For inserts that have `tc-*` attributes:
       - Keep the text, but drop `tc-*` attributes from the `attributes` object.
  3. Use this processed Delta to re-render Quill:
     - `quill.setContents(cleanDelta)`

- To switch back to **Redline**:
  - Use the original `content_delta`:
    - `quill.setContents(originalDelta)`

This simplifies logic because you don’t try to dynamically toggle attributes in-place.

---

# ============================
#  PHASE 7 — OPTIONAL IMPROVEMENTS
# ============================

The following are **future enhancements**, not mandatory for PoC:

1. **Version History**
   - Create a `quill_document_versions` table that stores snapshots of `content_delta` on each save.
   - Provide a simple UI to:
     - View previous versions.
     - Diff between two versions.

2. **Soft Locking**
   - Simple column such as `locked_by`, `locked_at` on `quill_documents`.
   - When a user opens `/quill`, attempt to lock.
   - Show a warning if another user has it locked.

3. **Reverb Integration**
   - Broadcast events for:
     - New comments.
     - Comment status changes.
     - Changes accepted/rejected.
   - Other open `/quill` tabs update UI in real time.

4. **Change Navigation**
   - Buttons:
     - “Next change”
     - “Previous change”
   - These iterate over segments with `tc-change-id`.

5. **Role-Based Permissions**
   - Add logic for:
     - Who can edit.
     - Who can comment.
     - Who can accept/reject.

---

# ============================
#  PHASE 8 — CODING ORDER CHECKLIST
# ============================

This is the recommended order to feed to Codex as separate prompts:

### STEP 1 — Migrations
- Create migrations for:
  - `quill_documents`
  - `quill_comments`
  - `quill_changes`

### STEP 2 — Models
- `QuillDocument`
- `QuillComment`
- `QuillChange`

### STEP 3 — Routes
- Add all `/quill`-related routes.

### STEP 4 — Controllers
- Implement stub methods with basic responses.

### STEP 5 — Blade Template
- Create `quill.blade.php` with:
  - Editor container.
  - Toolbar container.
  - Save button.
  - Comment panel placeholder.
  - Toggle placeholder.

### STEP 6 — Load Quill
- Initialize a basic Quill editor.
- Confirm typing works and Save button sends content.

### STEP 7 — Tables + Toolbar
- Add table module and full toolbar configuration.

### STEP 8 — Save/Load Delta
- Implement backend load and save of `content_delta`.

### STEP 9 — Comments System
- Implement comment creation, listing, and status change.
- Hook up UI and inline `comment-id`.

### STEP 10 — Track-Changes Attributes
- Define `tc-*` attributes.
- Wire up basic insertion tracking.

### STEP 11 — Delete Tracking
- Implement custom deletion handling to mark text as deleted.

### STEP 12 — Accept/Reject Backend + UI
- Implement endpoints and UI bubble for Accept / Reject.

### STEP 13 — Redline / Clean Toggle
- Implement view modes and Delta transformation.

### STEP 14 — UI Polish
- Improve layout, indicators, tooltips, and messages.

---

# END OF FILE
