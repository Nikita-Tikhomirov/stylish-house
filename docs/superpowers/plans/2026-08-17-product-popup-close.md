# Product Popup Close Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add one compact blue close control inside the white product popup and make close-button and overlay clicks work for both initial and AJAX-regenerated cards.

**Architecture:** Keep visual ownership in the popup component and `front-components.css`, using an empty `data-modal-close` button with exactly two pseudo-elements. Keep interaction ownership in the shared delegated modal handler, which closes only for a close control or a direct click on the modal overlay.

**Tech Stack:** Laravel Blade, plain CSS, browser JavaScript, Node test runner, PHPUnit, Vite.

## Global Constraints

- Use the site blue `#0989ff`.
- Do not add `span`, text glyph, SVG, or icon-font content to the close button.
- Desktop: `36px` hit area, `18px × 2px` strokes, `10px` top/right inset.
- Up to `575px`: `40px` hit area, `18px` strokes, `8px` top/right inset.
- Close on button click and direct overlay click; never close on a click inside the white popup.
- Preserve behavior before and after AJAX filtering.
- Make a verified database backup before production-code edits and a production-file backup before deployment.

---

### Task 1: Shared close interaction

**Files:**
- Modify: `tests/js/modal.test.mjs`
- Modify: `resources/js/modal.js`

**Interfaces:**
- Consumes: modal DOM structure `.modal > .modal__container > popup` and close selector `[data-modal-close], .modal__close`.
- Produces: `initModalCloseDelegation(documentRef, schedule)`, supporting close-control and direct-overlay clicks while ignoring popup-content clicks.

- [x] **Step 1: Write failing interaction tests**

Add literal fake-DOM cases that dispatch:

```js
const overlayEvent = { target: modal };
const contentEvent = { target: popupContent };
```

Assert that the overlay event removes the modal, restores the popup and scroll state, while the content event produces no fade, removal, scroll change, or prevented event.

- [x] **Step 2: Run the tests and verify RED**

Run:

```powershell
node --test tests/js/modal.test.mjs
```

Expected: the new overlay case fails because the current delegated handler only resolves a close control.

- [x] **Step 3: Implement the minimal delegated routing**

In `resources/js/modal.js`, resolve the close request with this contract:

```js
const closeControl = event.target?.closest?.(MODAL_CLOSE_SELECTOR);
const clickedOverlay = event.target?.classList?.contains?.('modal') === true;
const modal = clickedOverlay ? event.target : closeControl?.closest?.('.modal');

if (!modal) {
    return;
}
```

Reuse the existing fade, popup restoration, modal removal, and scroll restoration path unchanged.

- [x] **Step 4: Run the focused test and verify GREEN**

Run:

```powershell
node --test tests/js/modal.test.mjs
```

Expected: all close-button, overlay, and inside-content cases pass.

---

### Task 2: Isolated blue popup close control

**Files:**
- Modify: `tests/Unit/AuditContentMarkupTest.php`
- Modify: `resources/views/components/front/popups.blade.php`
- Modify: `resources/css/front-components.css`

**Interfaces:**
- Consumes: shared `[data-modal-close]` interaction contract from Task 1.
- Produces: one empty `.prodPopup__close[data-modal-close]` button inside `#popupProd`, with no `.modal__close` class and no child markup.

- [x] **Step 1: Write the failing markup/style contract test**

Replace the previous no-inner-button assertion with assertions that require exactly this empty control:

```html
<button class="prodPopup__close" type="button" data-modal-close aria-label="Закрыть"></button>
```

Also assert the popup button does not contain `modal__close`, `<span`, `&times;`, or an SVG.

- [x] **Step 2: Run the focused PHP test and verify RED**

Run the existing portable PHP executable against:

```powershell
vendor/bin/phpunit tests/Unit/AuditContentMarkupTest.php --filter product_popup
```

Expected: failure because the blue inner close control is absent.

- [x] **Step 3: Add minimal Blade markup**

Add the empty button as the first child of `#popupProd`. Do not add `.modal__close`; this prevents collision with the legacy global fixed white-cross styles.

- [x] **Step 4: Add isolated CSS**

Add:

```css
.modal:has(#popupProd) > .modal__container > .modal__close { display: none; }
#popupProd { position: relative; }
```

Style `.prodPopup__close` as a transparent, absolutely positioned button. Draw the blue X only with `::before` and `::after`, centered with `translate(-50%, -50%) rotate(45deg/-45deg)`. Add the specified desktop/mobile dimensions, darker-blue hover strokes, and a keyboard-only focus outline.

- [x] **Step 5: Run focused tests and verify GREEN**

Run:

```powershell
node --test tests/js/modal.test.mjs
phpunit tests/Unit/AuditContentMarkupTest.php --filter product_popup
```

Expected: both suites pass.

---

### Task 3: Full verification and delivery

**Files:**
- Verify: `resources/js/modal.js`
- Verify: `resources/views/components/front/popups.blade.php`
- Verify: `resources/css/front-components.css`
- Verify: `public/build/manifest.json` and generated assets

**Interfaces:**
- Consumes: completed visual and interaction contract from Tasks 1–2.
- Produces: tested production assets and an atomic production deployment with rollback files.

- [x] **Step 1: Run complete relevant automation**

```powershell
npm run test:shop
phpunit tests/Unit/AuditContentMarkupTest.php
node node_modules/vite/bin/vite.js build
C:\Users\user\.codex\scripts\harness.cmd smoke
git diff --check
```

- [ ] **Step 2: Verify rendered desktop behavior**

On the production-like rendered page, confirm one visible blue control inside the white popup. Exercise button, overlay, and popup-content clicks for an initial card and an AJAX-filtered card. Confirm modal count, popup restoration, and scroll restoration after each close.

- [ ] **Step 3: Verify rendered mobile behavior**

At `390 × 844`, repeat the same initial/AJAX interaction matrix and capture screenshots proving the cross is not clipped, oversized, or overlapping meaningful content.

- [ ] **Step 4: Review and commit implementation**

Request an independent diff review. Fix every Critical or Important finding, rerun verification, then commit:

```powershell
git add -A
git commit -m "fix: add reliable product popup close control"
```

- [ ] **Step 5: Back up production files and deploy atomically**

Back up every changed source file plus `public/build`, validate the backup manifest, upload immutable assets before switching the manifest, then upload source files atomically. Verify local/remote SHA-256 equality.

- [ ] **Step 6: Repeat production browser checks**

Repeat desktop and mobile initial/AJAX button, overlay, and inside-content cases. Require zero relevant console errors before reporting completion.
