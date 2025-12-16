# UI Tests

This folder contains manual front-end test cases for the Quill2 editor redlining (track changes) UX.

- Primary spec: [Redlining Frontend Test Cases](./Redlining-Frontend-Test-Cases.md)

Conventions
- “Redline” view = tracked changes visible.
- “Clean Draft” view = accepted view of the draft (deletions hidden, insert markers removed).
- “Accept/Decline” = resolve a tracked change.

Suggested cadence
- Run the **Smoke Suite** on every significant editor change.
- Run the full matrix before releases.
