<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Private Rimskie catalog collection

The collector creates a private, non-publishing import bundle. It does not modify the public catalog or publish donor content. Node.js 22+ and an installed Chrome, Edge, or Chromium browser are required; `playwright-core` does not download a browser.

Runtime data must use an explicit absolute writable root outside Windows drive `C:`. On this workstation use `G:\stylish-house-data\rimskie-imports`. You can pass it on every command or set `RIMSKIE_IMPORT_DATA_ROOT`. The CLI rejects relative paths, unwritable directories, Windows device namespaces, every spelling of drive `C:`, unsafe/reserved path identifiers, pre-existing symbolic links/junctions, and unexpected nested hard links. It recursively checks every existing run/profile descendant before work starts and rechecks writable parents and leaf files immediately before atomic writes. Temporary files use unpredictable names and exclusive creation. If a crash leaves an exact collector-generated config-create or lock-claim hard-link alias after publication, preflight removes only that redundant alias after verifying its regular-file type, token where applicable, and filesystem identity against the canonical target; foreign or mismatched aliases remain rejected.

```powershell
$env:RIMSKIE_IMPORT_DATA_ROOT = 'G:\stylish-house-data\rimskie-imports'
node scripts/rimskie-import/cli.mjs start --run 2026-08-25 --data-root 'G:\stylish-house-data\rimskie-imports' --chrome 'C:\Program Files\Google\Chrome\Application\chrome.exe'
node scripts/rimskie-import/cli.mjs status --run 2026-08-25 --data-root 'G:\stylish-house-data\rimskie-imports'
node scripts/rimskie-import/cli.mjs status --run 2026-08-25 --data-root 'G:\stylish-house-data\rimskie-imports' --json
node scripts/rimskie-import/cli.mjs pause --run 2026-08-25 --data-root 'G:\stylish-house-data\rimskie-imports'
node scripts/rimskie-import/cli.mjs resume --run 2026-08-25 --data-root 'G:\stylish-house-data\rimskie-imports' --chrome 'C:\Program Files\Google\Chrome\Application\chrome.exe'
node scripts/rimskie-import/cli.mjs stop --run 2026-08-25 --data-root 'G:\stylish-house-data\rimskie-imports'
node scripts/rimskie-import/cli.mjs export --run 2026-08-25 --data-root 'G:\stylish-house-data\rimskie-imports'
node scripts/rimskie-import/cli.mjs dry-run --run 2026-08-25-smoke --data-root 'G:\stylish-house-data\rimskie-imports' --max-requests 3 --max-products 1 --chrome 'C:\Program Files\Google\Chrome\Application\chrome.exe'
```

Each run is stored entirely below `<data-root>\<run-id>`: immutable `config.json`, `state.json`, the separate atomic `control.json`, `events.ndjson`, source and product JSON, actual first-photo WebP bytes in `images`, the persistent authenticated browser `profile`, and `export.json`. No runtime files or profile data use project `storage/app`. `config.json` is created exactly once, snapshots all 46 sources and request/run limits, and is bound to state and export by a deterministic SHA-256 digest. Existing commands reject missing, malformed, digest-mismatched config/state, or state whose immutable source identity/order differs from config; mutable source progress remains resumable. Rolling request timestamps are checkpointed before transport access, so restarting the CLI cannot reset the hourly limit. Product HTML is saved as a draft before image access; resume also recovers that draft if the process died before its state checkpoint. Image completion is checkpointed immediately after its atomic rename. Resume validates structurally correct WebP bytes already on disk and skips donor access only when the file is valid.

Collection is strictly sequential (`concurrency: 1`). New runs randomize HTML waits between 2–4 minutes, image waits between 1–2 minutes, and enforce a rolling budget of 20 requests per hour. A guarded simple-challenge click has its own 10–20 second wait. The immutable values saved in each run's `config.json` are used on every resume. A legacy run without the guarded-click limit remains readable by offline commands, but network start/resume rejects it and requires a new run ID. The first typed network error, timeout, `403`, or `429` response durably pauses the run without an automatic retry; an ordinary restart cannot clear that pause, and only an explicit `resume` permits one new attempt. Pause and stop are rechecked after every delay, before durable request reservation, and again immediately before transport access. Protective pauses, challenge pauses, and every `error` require an explicit `resume`. Dry-run request/product limits are total durable run caps, not per-process allowances; the defaults are three requests and one product. A URL is considered complete only after its parsed artifact/checkpoint is durable; a process death after receiving HTML but before that write may repeat the URL, while its earlier request reservation still remains counted.

The browser is always visible and uses the run-local persistent profile with service workers blocked. It launches offline, installs BrowserContext HTTP and WebSocket interception, closes every routed WebSocket, and only then enables connectivity. Category, product, and image URL contexts are separate: queued URLs must use HTTPS on the exact `rimskie.com` origin, match the exact approved path/source boundary, and contain no encoded separator or ambiguous dot segment. HTML redirects, WebSockets, duplicate exact-URL requests, scripts, unrelated XHR/fetches and images, analytics, and every off-origin request are aborted; one durable reservation authorizes exactly one network request. Every HTML attempt disarms routing and stops page loading in cleanup. Challenge detection never broadens routing or enables challenge assets: an approved retry click can authorize only the exact same page document and is counted as a separate durable request. Downloaded first photos require a successful 2xx response, `image/webp` Content-Type, a `.webp` destination, an exact RIFF length, and a valid `VP8 `, `VP8L`, or `VP8X` chunk before any file or completion marker is written. Any 2xx HTML-shaped image response is treated as a challenge regardless of its declared MIME type; `403` and `429` always take priority and stop immediately.

If an HTML page shows exactly one visible simple button named `Попробовать снова`, `Повторить`, or `Try again`, the collector waits 10–20 seconds and clicks it once. The exact URL is checkpointed before the wait, so a crash or resume cannot produce a second automatic click for that URL. If the button is absent, the challenge remains, full-CAPTCHA controls are present, the challenge came from an image request, or the response is `403`/`429`, the run durably pauses, closes the browser exactly once, and exits. A persisted pause never auto-resumes after restart. `pause` and `stop` use a serialized, stop-dominant atomic `control.json`, which is checked before every donor request, so they cannot race `state.json` checkpoints or lose a concurrent stop. Collector owner, lock, and reclaim records include the OS process start fingerprint as well as the PID and caller identity; live PID reuse is treated as stale only when the current start fingerprint differs, and unverifiable live identities fail closed. A stopped run cannot be started or resumed. `status`, `pause`, `stop`, `resume`, and `export` require an existing initialized run and never create one; non-donor commands lazily avoid loading or opening the browser transport. Export holds the control lock across the live-owner check and complete serialization. It is allowed only for a completed, non-live, non-empty run whose immutable source order, membership, complete product fields, exact donor URLs, and local WebP integrity checks all pass. The manifest includes coherent counts plus each first image's byte length and SHA-256 digest so later replacement is detectable.

### Collector operator runbook

Use a new unique run ID for each fresh collection. `start` creates the immutable run configuration and begins sequential collection; do not reuse an older run rejected as an aggressive profile.

```powershell
node scripts/rimskie-import/cli.mjs start --run 2026-08-26-full --data-root 'G:\stylish-house-data\rimskie-imports' --chrome 'C:\Program Files\Google\Chrome\Application\chrome.exe'
node scripts/rimskie-import/cli.mjs status --run 2026-08-26-full --data-root 'G:\stylish-house-data\rimskie-imports'
```

Use `pause` for a temporary operator stop and `resume` to continue the same checkpoint. Use `stop` only to terminate the run permanently; a stopped run cannot resume. If `status` reports `challenge`, `http_403`, `http_429`, `network`, or `timeout`, inspect the visible donor state and resume only after normal access is available again. Do not repeatedly restart a protected URL: the request history and the one-click marker remain durable in `state.json`.

```powershell
node scripts/rimskie-import/cli.mjs pause --run 2026-08-26-full --data-root 'G:\stylish-house-data\rimskie-imports'
node scripts/rimskie-import/cli.mjs resume --run 2026-08-26-full --data-root 'G:\stylish-house-data\rimskie-imports' --chrome 'C:\Program Files\Google\Chrome\Application\chrome.exe'
node scripts/rimskie-import/cli.mjs stop --run 2026-08-26-full --data-root 'G:\stylish-house-data\rimskie-imports'
```

After `status` becomes `completed`, create the checked export. Products, source prices, memberships, and downloaded first-photo WebP files remain under `G:\stylish-house-data\rimskie-imports\<run-id>`; nothing in the collector corpus is stored on drive C.

```powershell
node scripts/rimskie-import/cli.mjs export --run 2026-08-26-full --data-root 'G:\stylish-house-data\rimskie-imports'
```

Residual filesystem threat model: the collector prevents pre-existing/static path traversal, reserved names, drive-`C:` escapes, symbolic links, junctions, reparse points, and unexpected hard links, and it revalidates immediately before writes. A hostile local process that can concurrently replace an already validated writable directory ancestor remains outside this portable Node/Chrome design because Node does not expose a cross-platform held-directory-handle/no-share-delete write API and Chrome profile writes cannot be routed through one. Normal operation remains rooted only at the validated `G:\stylish-house-data\rimskie-imports` path.

## Private catalog staging

An exported collector package can be validated without writing anything:

```powershell
php artisan catalog-import:ingest 'G:\stylish-house-data\rimskie-imports\<run-id>\export.json' --dry-run
```

After a successful dry run, omit `--dry-run` to create a private staging run. The command validates the complete manifest and every local WebP before database or storage writes, copies verified images to the private Laravel `local` disk, and leaves all product and landing drafts in `needs_review`. It does not publish products, enable calculator pricing, or expose the donor price. Repeating the identical package is a verified no-op that preserves manual rewrite and review fields; a changed digest or damaged staging facts/image is rejected for operator review.

## Controlled catalog publication and rollback

Publication is disabled by default. Configure the private backup destination and enable the gate only for the controlled release window:

The built-in verified database backup uses POSIX ownership and mode checks and therefore runs only on Linux/macOS. On Windows it fails closed before creating a lock, dump, or archive because PHP mode bits do not prove that `Users` or `Everyone` lack ACL access. The backup, publication, and rollback commands remain unavailable on Windows until an ACL-aware hardener is implemented; creating an external backup does not bypass that guard. Run this release workflow on the Linux VPS.

```bash
export CATALOG_IMPORT_BACKUP_PATH=/var/backups/stylish-house/catalog-import
export RIMSKIE_IMPORT_PUBLICATION_ENABLED=true
php artisan catalog:backup --run=<run-id>
php artisan catalog-import:preflight <run-id>
php artisan catalog-import:publish <run-id>
```

`catalog:backup` creates a fresh private dump, independently verifies its compressed and raw hashes, and only then records the artifact metadata on the unpublished run. It does not acknowledge warnings or publish catalog rows.

If the exact reviewed warning set needs approval, publish with both `--acknowledge-warnings` and `--acknowledged-by=<operator>`. The acknowledgement is not written until after a fresh database backup has been created and independently verified. Publication accepts only the complete unbounded 46-source package, approved rewrites and memberships, exact local WebP hashes, and the existing visible `story/rimskieshtory` catalog roots. It keeps donor identity and price fields private, disables calculator pricing for imported products, and generates the sitemap only after the catalog transaction commits.

```bash
php artisan catalog-import:publish <run-id> --acknowledge-warnings --acknowledged-by=operator
php artisan catalog-import:rollback <run-id>
```

Rollback creates and verifies a separate fresh database backup before recording its durable media journal or changing catalog data. Repeated publication and rollback commands verify recorded backup artifacts, immutable row/pivot/media snapshots, and ownership before doing anything. Conflicts, changed files, junctions, or uncertain commit state fail closed and preserve recovery evidence in private storage; retry the same rollback command after resolving the reported diagnostic.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
