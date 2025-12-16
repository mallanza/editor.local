import { test, expect } from '@playwright/test';

async function gotoQuill2(page) {
  await page.goto('/quill2');
  await expect(page.locator('#quill2-editor .ql-editor')).toBeVisible();
  await page.waitForFunction(() => Boolean(window.quill2Debug?.quill));
}

async function hardResetEditorState(page) {
  await page.evaluate(() => {
    const q = window.quill2Debug?.quill;
    if (!q) return;
    // Reset content and undo stack without creating tracked changes.
    q.setText('\n', 'silent');
    q.history?.clear?.();
    // Reset tracker ledger to empty.
    window.quill2Tracker?.loadChanges?.([]);
    // Ensure cursor is in a sane place.
    q.setSelection?.(0, 0, 'silent');
  });
}

async function setTrackChanges(page, enabled) {
  const toggle = page.locator('#quill2-track-toggle');
  const desired = enabled ? 'true' : 'false';
  for (let i = 0; i < 3; i += 1) {
    const pressed = await toggle.getAttribute('aria-pressed');
    if (pressed === desired) return;
    await toggle.click();
  }
  await expect(toggle).toHaveAttribute('aria-pressed', desired);
}

async function focusEditor(page) {
  const editor = page.locator('#quill2-editor .ql-editor');
  await editor.click();
  return editor;
}

async function saveAndWait(page) {
  await page.locator('#quill2-save').click();
  await expect(page.locator('#quill2-status')).toContainText('Saved', { timeout: 20_000 });
}

test.beforeEach(async ({ page }) => {
  await gotoQuill2(page);
  await hardResetEditorState(page);
});

test('S-01 Insert text redlines', async ({ page }) => {
  await setTrackChanges(page, true);
  await focusEditor(page);
  await page.keyboard.type('Hello');

  const pendingInserts = page.locator(
    '#quill2-editor [data-q2-change-type="insert"][data-q2-change-status="pending"]'
  );
  await expect(page.locator('#quill2-editor')).toContainText('Hello');
  expect(await pendingInserts.count()).toBeGreaterThan(0);

  await expect(page.locator('#quill2-accept-current')).toBeEnabled();
});

test('S-02 Delete text redlines (existing text)', async ({ page }) => {
  // Create baseline text without tracking, then delete with tracking.
  await setTrackChanges(page, false);
  await focusEditor(page);
  await page.keyboard.type('Hello');

  await setTrackChanges(page, true);
  await page.evaluate(() => {
    window.quill2Debug?.quill?.setSelection?.(0, 5, 'user');
  });
  await page.keyboard.press('Backspace');

  const pendingDeletes = page.locator(
    '#quill2-editor [data-q2-change-type="delete"][data-q2-change-status="pending"]'
  );
  expect(await pendingDeletes.count()).toBeGreaterThan(0);
});

test('S-03 Accept current change', async ({ page }) => {
  await setTrackChanges(page, true);
  await focusEditor(page);
  await page.keyboard.type('Hello');

  await expect(page.locator('#quill2-accept-current')).toBeEnabled();
  await page.locator('#quill2-accept-current').click();

  await expect(page.locator('#quill2-editor [data-q2-change-status="pending"]')).toHaveCount(0);
  await expect(page.locator('#quill2-editor')).toContainText('Hello');
});

test('S-04 Accept all + refresh leaves no pending changes', async ({ page }) => {
  await setTrackChanges(page, true);
  await focusEditor(page);
  await page.keyboard.type('Hello');
  await page.keyboard.press('Enter');
  await page.keyboard.type('World');

  await expect(page.locator('#quill2-accept-all')).toBeEnabled();
  await page.locator('#quill2-accept-all').click();
  await expect(page.locator('#quill2-editor [data-q2-change-status="pending"]')).toHaveCount(0);

  await saveAndWait(page);
  await page.reload();
  await expect(page.locator('#quill2-editor .ql-editor')).toBeVisible();
  await expect(page.locator('#quill2-editor [data-q2-change-status="pending"]')).toHaveCount(0);
});

test('S-05 Undo safety after refresh (does not wipe document)', async ({ page }) => {
  // Baseline content saved, then refresh + Ctrl+Z should not clear.
  await setTrackChanges(page, false);
  await page.evaluate(() => {
    const q = window.quill2Debug?.quill;
    q?.setText?.('Baseline\n', 'user');
  });
  await saveAndWait(page);

  await page.reload();
  await expect(page.locator('#quill2-editor .ql-editor')).toBeVisible();

  await focusEditor(page);
  await page.keyboard.press('Control+Z');

  const length = await page.evaluate(() => window.quill2Debug?.quill?.getLength?.() ?? 0);
  expect(length).toBeGreaterThan(1);
  await expect(page.locator('#quill2-editor')).toContainText('Baseline');
});

test('S-08 Toggle Redline/Clean Draft hides deletions', async ({ page }) => {
  // Create a deletion on existing text.
  await setTrackChanges(page, false);
  await page.evaluate(() => {
    const q = window.quill2Debug?.quill;
    q?.setText?.('Hello\n', 'user');
  });

  await setTrackChanges(page, true);
  await page.evaluate(() => {
    window.quill2Debug?.quill?.setSelection?.(0, 5, 'user');
  });
  await page.keyboard.press('Backspace');

  const pendingDeletes = page.locator(
    '#quill2-editor [data-q2-change-type="delete"][data-q2-change-status="pending"]'
  );
  expect(await pendingDeletes.count()).toBeGreaterThan(0);

  await page.locator('#quill2-view-clean').click();
  await expect(page.locator('#quill2-editor')).toHaveAttribute('data-view-mode', 'clean');

  // In clean mode deletes should be visually hidden.
  await expect(pendingDeletes.first()).toBeHidden();

  await page.locator('#quill2-view-redline').click();
  await expect(page.locator('#quill2-editor')).not.toHaveAttribute('data-view-mode', 'clean');
  await expect(pendingDeletes.first()).toBeVisible();
});

test('I-01 Undo should not create new tracked changes', async ({ page }) => {
  // Baseline text (untracked)
  await setTrackChanges(page, false);
  await page.evaluate(() => {
    const q = window.quill2Debug?.quill;
    q?.setText?.('Baseline\n', 'silent');
    q?.history?.clear?.();
  });

  // Make a tracked insert, then undo it.
  await setTrackChanges(page, true);
  await focusEditor(page);
  await page.keyboard.type('X');
  const pendingBeforeUndo = await page.locator('#quill2-editor [data-q2-change-status="pending"]').count();
  expect(pendingBeforeUndo).toBeGreaterThan(0);

  await page.keyboard.press('Control+Z');

  await expect(page.locator('#quill2-editor [data-q2-change-status="pending"]')).toHaveCount(0);
  const text = await page.evaluate(() => window.quill2Debug?.quill?.getText?.() ?? '');
  expect(text).toContain('Baseline');
});
