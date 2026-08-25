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

Runtime data must use an explicit absolute writable root outside Windows drive `C:`. On this workstation use `G:\stylish-house-data\rimskie-imports`. You can pass it on every command or set `RIMSKIE_IMPORT_DATA_ROOT`. The CLI rejects relative paths, unwritable directories, Windows device namespaces, every spelling of drive `C:`, unsafe/reserved path identifiers, and paths whose existing junction or reparse target escapes the selected root. It recursively checks every existing run/profile descendant before work starts and rechecks writable parents and leaf files immediately before atomic writes. Temporary files use unpredictable names and exclusive creation.

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

Each run is stored entirely below `<data-root>\<run-id>`: immutable `config.json`, `state.json`, the separate atomic `control.json`, `events.ndjson`, source and product JSON, actual first-photo WebP bytes in `images`, the persistent authenticated browser `profile`, and `export.json`. No runtime files or profile data use project `storage/app`. `config.json` snapshots all 46 sources and request/run limits and cannot change on resume. Rolling request timestamps and consecutive-failure state are checkpointed before transport access or backoff, so restarting the CLI cannot reset either limit. Product HTML is saved as a draft before image access; resume also recovers that draft if the process died before its state checkpoint. Image completion is checkpointed immediately after its atomic rename. Resume validates structurally correct WebP bytes already on disk and skips donor access only when the file is valid.

Collection is strictly sequential (`concurrency: 1`). HTML waits are randomized between 20–40 seconds, image waits between 10–20 seconds, and the rolling budget is 120 requests per hour. Consecutive typed network errors, timeouts, `403`, and `429` responses share durable backoff delays of 2, 5, and 15 minutes; the third failure records an explicit-resume marker, pauses the run, and no fourth retry is automatic even if the process dies during backoff. A successful request resets the failure counter. Pause and stop are rechecked after every delay, before durable request reservation, and again immediately before transport access. Paused failure markers and every `error` require an explicit `resume`. A dry run defaults to three requests and one product when its limits are omitted. A URL is considered complete only after its parsed artifact/checkpoint is durable; a process death after receiving HTML but before that write may repeat the URL, while its earlier request reservation still remains in the rolling budget.

The browser is always visible and uses the run-local persistent profile. Queued parser and transport URLs must be HTTPS on the exact `rimskie.com` origin and inside approved catalog/product/image paths; redirects are validated again. During collection, routing allows only the exact counted main document or exact counted first-image request and aborts scripts, XHR/fetches, other images, analytics, and every off-origin request. After a visible challenge is detected, only same-origin challenge resources are allowed for the authorized manual interaction. Downloaded first photos require a successful 2xx response, `image/webp` Content-Type, a `.webp` destination, an exact RIFF length, and a valid `VP8 `, `VP8L`, or `VP8X` chunk before any file or completion marker is written. Any HTML-shaped image response is treated as a challenge regardless of its declared MIME type.

Complete authentication manually. If BotHunt or another visible challenge appears, the collector pauses immediately, performs no automatic challenge retry, and keeps that visible browser available. Only make a click when it is explicitly authorized, then run `resume`; the collector never automates or bypasses a challenge. `pause` and `stop` use a serialized, stop-dominant atomic `control.json`, which is checked before every donor request, so they cannot race `state.json` checkpoints or lose a concurrent stop. A live owner PID prevents a second collector for the same run, and a stopped run cannot be started or resumed. `status`, `pause`, `stop`, `resume`, and `export` require an existing run and never create one; non-donor commands lazily avoid loading or opening the browser transport. Export is allowed only for a completed, non-live, non-empty run whose source, membership, product, and local WebP integrity checks all pass.

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
