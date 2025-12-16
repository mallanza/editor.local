# Redlining Frontend Test Cases (Quill2)

Purpose
- Provide a comprehensive manual UI test matrix for redlining (track changes) in the Quill2 editor.
- Catch regressions in: persistence (save/refresh), accept/decline semantics, tables, images, paste/drop, and undo/redo.

Scope
- Front-end behavior and UX correctness.
- Includes server-roundtrip scenarios (save/refresh) because they manifest as UI issues.

Out of scope
- Backend unit tests, security/perf testing, load testing.
- **Format-change redlines** (formatting-only edits to existing text like bold/italic/underline) are not tracked as redlines in this implementation.

---

## Test Environment

Pre-reqs
- Run the app locally (editor.local).
- Use a Chromium browser (Edge/Chrome). If you support Firefox/Safari, rerun the Smoke Suite there.

Reset between runs
- Use a new document or clear the document to reduce cross-test contamination.
- Prefer hard refresh between major scenario groups.

Data/Actors
- If you have multiple users available, run “Multi-user color/attribution” scenarios with at least 2 users.
- Otherwise, simulate multiple users by seeding changes created by different users (if supported) or by editing metadata in stored snapshots.

Definitions
- **Pending change**: tracked change with status `pending`.
- **Resolved change**: accepted/declined.
- **Tracked wrapper**: DOM element carrying `data-q2-change-*` attributes.

---

## Smoke Suite (run on every edit-related change)

S-01: Insert text redlines
1. Enable track changes.
2. Type “Hello”.
Expected
- Text shows insert marker (underline + insert color).
- Activity feed increments.
- “Accept selected” becomes enabled when cursor is inside insert.

S-02: Delete text redlines
1. With track changes on, select a word and press Backspace.
Expected
- Deleted text remains visible in redline (strikethrough), hidden in Clean Draft.

S-03: Accept current change
1. Place cursor inside one pending change.
2. Click “Accept selected change”.
Expected
- Insert becomes normal text (no insert marker) in Redline.
- Delete disappears (or becomes normal depending on your acceptance semantics).
- Activity/ledger updates; buttons update.

S-04: Accept all + refresh
1. Create 2–3 changes.
2. Click “Accept all”.
3. Refresh.
Expected
- No pending redlines remain.
- Clean Draft shows clean text.
- No “ghost” pending entries remain.

S-05: Undo safety after refresh
1. Refresh the page.
2. Press Ctrl+Z.
Expected
- The document does NOT clear or revert to empty.

S-06: Image insert persistence
1. Insert image (modal or paste).
2. Save and refresh.
Expected
- Image persists with stable URL.
- If inserted while tracking, insert marker/pill behavior remains correct.

S-07: Table insert persistence
1. Insert a table, type inside cells.
2. Save and refresh.
Expected
- Table persists (not flattened).

S-08: Toggle Redline/Clean Draft
1. With mixed inserts/deletes, toggle view mode.
Expected
- Clean Draft hides deletions and shows insert text as normal.
- Redline shows both.

---

## Detailed Test Matrix

Each test has: **ID**, **Goal**, **Steps**, **Expected**.

### A. Core Typing + Formatting

A-01: Insert across multiple paragraphs
- Steps: Type paragraph 1, Enter, type paragraph 2.
- Expected: Both paragraphs keep correct structure after save/refresh.

A-02: Delete across paragraph boundary
- Steps: Place caret at start of paragraph 2, Backspace into paragraph 1.
- Expected: Delete tracked correctly; no block corruption; accept/decline restores expected structure.

A-03: Mixed insert/delete in same line
- Steps: Insert a phrase; delete part of it; insert again.
- Expected: Change segmentation is sensible; accept/decline resolves only the intended range.

A-04: Bold/italic/underline formatting inside insert
- Steps: Create an insert; apply bold/italic/underline.
- Expected: Formatting applies; insert marker (border-bottom) still visible; user underline remains distinct.

A-05: Formatting on existing text while tracking
- Steps: Select existing text, toggle bold.
- Expected: Formatting applies normally, but this is **not** recorded as a tracked change (out of scope for redlining).

A-06: Undo/redo while tracking (text)
- Steps: Type; undo; redo.
- Expected: Undo/redo does not create bogus new changes; ledger matches visible redlines.

A-07: Copy/cut/paste plain text within editor
- Steps: Copy text; paste in another place.
- Expected: Paste becomes insert changes if tracking enabled; no metadata leaks.

A-08: Cursor/caret visibility inside inserts
- Steps: Click inside inserted text and type.
- Expected: Caret is clearly visible (not behind marker/highlight).

A-09: Clean Draft does not show insert markers
- Steps: Toggle Clean Draft.
- Expected: Insert markers not visible; insert text color returns to normal.

A-10: Deletions hidden in Clean Draft
- Steps: Toggle Clean Draft.
- Expected: Deleted content removed from layout.

### B. Selection + Change Targeting

B-01: Click selects correct change
- Steps: Click inside an insert span.
- Expected: “Accept selected” targets that change.

B-02: Arrow navigation through inserts
- Steps: Use left/right arrow across insert boundary.
- Expected: Selection moves smoothly; change targeting updates.

B-03: Click near image does not trap caret
- Steps: Two images in adjacent paragraphs; click above/below; press Enter.
- Expected: Can create new paragraph before/after without deleting images.

B-04: Selecting an image enables image actions
- Steps: Click image; align; resize.
- Expected: Selection moves to embed; align/resize works; typing inserts after image (not replace).

B-05: Accept selected when selection is an image embed
- Steps: Create tracked image change; click image; accept selected.
- Expected: Resolves that image change.

### C. Paste + Clipboard

C-01: Atomic paste of rich HTML while tracking
- Steps: Copy formatted HTML from external source; paste.
- Expected: Paste results in a single batch insert change (atomic), not fragmented changes.

C-02: Paste as plain text
- Steps: Use “Paste as plain text” tool.
- Expected: No inline styles; inserted text is tracked.

C-03: Paste single image from clipboard
- Steps: Copy an image; paste.
- Expected: Crop/upload modal path works; image persists after save/refresh.

C-04: Paste multiple images
- Steps: Paste multiple images.
- Expected: Auto-upload pipeline; URLs stable; no missing images after refresh.

C-05: Clipboard sanitizer preserves change metadata
- Steps: Save/refresh document with redlines.
- Expected: `data-q2-*` metadata persists; redlines remain.

### D. Drag & Drop

D-01: Drag/drop image file into editor (tracking on)
- Steps: Drop a local image file.
- Expected: Upload occurs; inserted image uses stable URL; tracked appropriately.

D-02: Drag/drop image file into editor (tracking off)
- Steps: Disable tracking; drop.
- Expected: Image inserts without redline wrapper.

D-03: Drop multiple images
- Steps: Drop 2+ images.
- Expected: All upload; order stable; no disappear on refresh.

### E. Images: Resize, Align, Pills

E-01: Resize image persists
- Steps: Resize using handle; save/refresh.
- Expected: `data-q2-img-w/h` persists.

E-02: Align image left/center/right (tracking on)
- Steps: Click image; set align.
- Expected: Alignment applies immediately; persists after refresh.

E-03: Image pill anchored to image
- Steps: Insert/delete tracked image.
- Expected: Pill sticks to image frame; not offset to full line.

E-04: Two consecutive images: insert text between
- Steps: Two images back-to-back; click second; type.
- Expected: Text appears between/after as expected; images not replaced.

E-05: Delete image then undo
- Steps: Delete image; Ctrl+Z.
- Expected: Image restores without being recorded as a new pending insert change.

### F. Tables (quill-table-better)

F-01: Insert table then type in cells
- Expected: Table retains structure; edits are tracked.

F-02: Save/refresh with table
- Expected: Table rehydrates; no flattening.

F-03: Delete entire table while tracking
- Steps: Delete table (using table UI or selection delete).
- Expected: Deletion shown as table delete pill/frame; accept removes it correctly.

F-04: Accept table deletion
- Expected: Table fully removed; no empty committed table remains.

F-05: Undo after accepting table deletion
- Expected: Undo restores previous state without corrupting ledger/buttons.

### G. Lists

G-01: Insert list items
- Expected: Items tracked; list markers consistent.

G-02: Delete list item
- Expected: Deletion tracked; empty list items hidden correctly in Clean Draft.

G-03: Mixed list + paragraph
- Expected: Structure preserved through save/refresh.

### H. Accept/Decline Semantics

H-01: Accept all
- Steps: Create multiple changes; Accept all.
- Expected: No pending; ledger matches.

H-02: Decline all
- Expected: Inserts removed; deletes restored.

H-03: Accept selected inside a larger change
- Expected: Correct change targeted; no partial mismatches.

H-04: Accept image insert/delete
- Expected: Correctly resolves; no selection bugs.

H-05: Accept all then undo
- Expected: Redlines return; Accept all becomes available again; subsequent accept works and persists.

### I. Undo/Redo Integration

I-01: Undo should not create new tracked changes
- Steps: Make a change; undo.
- Expected: Tracker doesn’t treat history operations as fresh inserts/deletes.

I-02: Undo/redo after accept/decline
- Expected: UI + ledger remain consistent; no “stuck” state.

I-03: Refresh then undo
- Expected: No full-document delete.

### J. View Mode + Persistence

J-01: Save/refresh roundtrip with mixed content
- Mix: paragraphs, blank paragraphs, images, table, list, inserts/deletes.
- Expected: No paragraph loss; redlines persist; tables/images rehydrate.

J-02: Clean Draft persistence
- Steps: Toggle Clean Draft; save; refresh.
- Expected: View state (if persisted) matches expectation; Clean Draft visuals correct.

### K. Multi-user / Attribution

K-01: Different author colors
- Steps: Create changes as user A and user B.
- Expected: Insert underline/border color differs; insert font color differs; tooltips show correct author.

K-02: Accept permissions (if implemented)
- Expected: Only allowed users can accept/decline.

---

## Gap-Finding Heuristics

When you find a bug, record:
- Steps to reproduce (exact keys/clicks)
- Whether tracking is enabled
- View mode (Redline/Clean Draft)
- Content type involved (text/table/image/list)
- Whether the issue appears only after save/refresh
- Whether undo/redo changes the outcome

High-risk areas (regression-prone)
- Undo/redo combined with accept/decline
- Tables (module readiness + delta normalization)
- Images (embed selection + upload persistence)
- HTML hydration + sanitizer interactions

---

## Suggested Automation (future)

If you later want automation, these scenarios map well to Playwright:
- Hydration + undo stack sanity
- Image insert/resize/align + refresh
- Table insert + refresh + accept delete
- Accept all + undo + accept all again

(Manual cases above are written to be directly convertible into Playwright tests.)

---

## Automation Status (Playwright)

Legend
- **Auto**: Implemented or stable to automate with DOM assertions.
- **Hybrid**: Automatable, but needs extra harness/fixtures (clipboard permissions, auth, deterministic upload stubs, or table/image helpers).
- **Manual-only**: Primarily visual/UX judgement or cross-user/permissions flows that are usually not worth automating first.

Implemented (Auto)
- Smoke Suite: S-01, S-02, S-03, S-04, S-05, S-08 are automated in [tests/e2e/quill2-smoke.spec.mjs](tests/e2e/quill2-smoke.spec.mjs)

Auto candidates (recommended next)
- A-01, A-02, A-03, A-04, A-06, A-07, A-09, A-10
- B-01, B-02, B-04, B-05
- H-01, H-02, H-03, H-04
- I-01, I-02, I-03
- J-01

Hybrid (needs harness/fixtures)
- B-03 (image caret “trap” is automatable but tends to be flaky without a deterministic image fixture)
- C-01, C-02, C-03, C-04 (clipboard + rich HTML/image paste helpers and permissions)
- C-05 (mostly covered via save/refresh tests, but full metadata audit is more involved)
- D-01, D-02, D-03 (drag/drop file injection)
- E-01..E-05 (image upload/persistence/resize/align/pills — best with deterministic upload stubs + auth)
- F-01..F-05 (table module UI is automatable, but needs stable table insertion/delete helpers)
- G-01..G-03 (list structure tests are automatable, but need stable selection helpers across browsers)
- J-02 (only if view state is truly persisted; otherwise it’s just a UI toggle test)
- H-05 (accept-all undo semantics need to be defined; otherwise automation is brittle)

Manual-only (keep manual unless you add heavier automation)
- A-08 (caret visibility “clearly visible” is visual; only feasible with screenshot/pixel assertions)
- E-03 (pill “anchored” is primarily geometric/visual; possible with bounding-box checks but high maintenance)
- K-01, K-02 (multi-user attribution + permissions require multi-session orchestration/fixtures)
