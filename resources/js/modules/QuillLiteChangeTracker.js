// IMPORTANT:
// Single source of truth for Quill2 tracking is the global implementation defined in
// public/js/quill-lite-change-tracker.js (loaded once via resources/js/app.js).
//
// This module remains only as a thin proxy to avoid future drift if someone imports it.

export default class QuillLiteChangeTrackerProxy {
    constructor(...args) {
        if (typeof window === 'undefined' || !window.QuillLiteChangeTracker) {
            throw new Error('QuillLiteChangeTracker is provided by public/js/quill-lite-change-tracker.js');
        }
        // Return the real instance from the authoritative global implementation.
        // eslint-disable-next-line no-constructor-return
        return new window.QuillLiteChangeTracker(...args);
    }
}
