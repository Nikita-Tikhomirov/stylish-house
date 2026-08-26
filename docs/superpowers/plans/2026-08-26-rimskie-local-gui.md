# Rimskie Local GUI Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a usable Windows-local graphical control panel for the existing resumable Roman-blind collector.

**Architecture:** A dependency-free Node HTTP server binds only to `127.0.0.1:43127`, reads validated run state from `G:\stylish-house-data\rimskie-imports`, and supervises the existing collector CLI with `spawn(..., { shell: false })`. A static Russian-language dashboard polls the local API and never talks to the donor directly.

**Tech Stack:** Node.js ES modules, built-in `http`, existing RunStore/ControlFile safety layer, vanilla HTML/CSS/JavaScript, Node test runner.

**Spec:** `docs/superpowers/specs/2026-08-26-rimskie-local-gui-design.md`

## Global Constraints

- Runtime data, images, checkpoints, and exports stay under `G:\stylish-house-data\rimskie-imports` and never use drive C:.
- A normal GUI start invokes the unlimited `start` mode across all 46 configured categories; it never applies dry-run limits.
- The GUI binds only to `127.0.0.1:43127` and is not deployed to the VPS.
- Commands use argument arrays and `shell: false`; user input cannot supply paths, donor URLs, or commands.
- Production database access and mutation are out of scope; a verified backup remains mandatory before future production import.
- Existing collector throttling, checkpoints, ownership locks, challenge handling, and terminal stop semantics remain authoritative.

---

### Task 1: Safe GUI service and process supervisor

**Files:**
- Create: `scripts/rimskie-import/gui/status-service.mjs`
- Create: `scripts/rimskie-import/gui/process-supervisor.mjs`
- Create: `tests/js/rimskie-import-gui-service.test.mjs`

**Interfaces:**
- Produces: `createStatusService({ dataRoot })`, `listRuns()`, `getRunSnapshot(runId)`, `listProducts(runId, page, perPage)`, `getImagePath(runId, externalId)`.
- Produces: `CollectorSupervisor({ cliPath, dataRoot, spawnProcess })`, `start()`, `resume(runId)`, `pause(runId)`, `stop(runId)`, `exportRun(runId)`, `openFolder(runId)`.

- [x] Write failing tests proving unsafe identifiers are rejected, normal starts omit all product/request caps, subprocesses use `shell: false`, run history survives service restart, and product/event snapshots come from persisted files.
- [x] Run `node --test tests/js/rimskie-import-gui-service.test.mjs` and confirm failure is caused by missing GUI modules.
- [x] Implement the minimal validated read model and supervisor around the existing CLI.
- [x] Run the targeted test and confirm all assertions pass.

### Task 2: Loopback HTTP API and authorization

**Files:**
- Create: `scripts/rimskie-import/gui/server.mjs`
- Create: `tests/js/rimskie-import-gui-server.test.mjs`

**Interfaces:**
- Consumes: status service and supervisor from Task 1.
- Produces: `createGuiServer({ host, port, dataRoot, token, statusService, supervisor })` with `listen()` and `close()`.

- [x] Write failing integration tests for loopback defaults, bootstrap/run endpoints, POST token plus same-origin enforcement, safe route parameters, full-start dispatch, pause/resume/stop/export actions, bounded image serving, and 404/405 responses.
- [x] Run `node --test tests/js/rimskie-import-gui-server.test.mjs` and confirm the missing server causes failure.
- [x] Implement request routing, JSON limits, security headers, static assets, validated image streaming, and Russian API errors.
- [x] Run both GUI test files and confirm they pass.

### Task 3: Russian dashboard and Windows launcher

**Files:**
- Create: `scripts/rimskie-import/gui/public/index.html`
- Create: `scripts/rimskie-import/gui/public/styles.css`
- Create: `scripts/rimskie-import/gui/public/app.js`
- Create: `scripts/rimskie-import/gui.mjs`
- Create: `start-rimskie-gui.cmd`
- Modify: `package.json`
- Modify: `README.md`

**Interfaces:**
- Consumes: Task 2 API.
- Produces: one-click local launch and dashboard controls for new full run, pause, resume, terminal stop with confirmation, folder opening, and export.

- [x] Add a failing browser-independent UI contract test that loads the real HTML/JS and proves required Russian actions, status regions, mobile viewport metadata, token bootstrap, and local-only polling are present as observable DOM/API behavior.
- [x] Run the UI contract test and confirm it fails before the assets exist.
- [x] Generate a clean visual concept using the site-blue design direction, then implement the responsive dashboard without adding frontend dependencies.
- [x] Add the launcher, package command, and a plain-Russian README section explaining start, storage, resume, and export.
- [x] Run all GUI tests and `npm run test:shop`.

### Task 4: Visual and end-to-end verification

**Files:**
- Create: `docs/reports/screenshots/rimskie-gui-desktop-1440x900.png`
- Create: `docs/reports/screenshots/rimskie-gui-mobile-390x844.png`

**Interfaces:**
- Consumes: runnable dashboard from Task 3.
- Produces: visual evidence and final verified branch state.

- [x] Start the GUI with a temporary safe G:-root fixture and no donor collection.
- [x] Verify the dashboard at 1440×900 and 390×844, including button visibility, responsive layout, history selection, empty/error states, and console output.
- [x] Save screenshots, run `npm run build`, `npm run test:shop`, and `C:\Users\user\.codex\scripts\harness.cmd smoke`.
- [x] Check UTF-8 text and `git diff --check`, then stage only files that belong to this interface task.
