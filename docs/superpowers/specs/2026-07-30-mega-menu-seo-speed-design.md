# Mega Menu, Product Meta Descriptions, and Performance Design

## Goal

Improve catalog navigation and internal linking without changing SEO-created page content or existing meta tags beyond the explicitly requested product description fix. The result must be editable from the admin panel, responsive, accessible, cached, and visually verified on desktop and mobile.

## Selected Approach

Use a dedicated tree-based navigation model rather than deriving the entire header from `show_in_menu` flags.

Alternatives considered:

1. Continue auto-generating the menu from categories and products. This is simple but cannot express editorial groups such as "By purpose", "Popular", or SEO landing pages.
2. Store the whole menu as one JSON setting. This is fast to build but difficult to validate, reorder safely, query, and migrate.
3. Use normalized menu and menu-item records with a constrained tree. This is selected because it supports visual editing, entity-backed links, custom internal links, ordering, validation, and caching.

## Data Model

### `navigation_menus`

- `id`
- `name`
- `slug` (unique, e.g. `header-catalog`)
- timestamps

### `navigation_items`

- `id`
- `navigation_menu_id`
- `parent_id` (nullable)
- `label`
- `node_type`: `tab`, `section`, or `link`
- `source_type`: `category`, `subcategory`, `page`, or `custom`
- `source_id` (nullable)
- `url` (nullable, custom internal links only)
- `placement`: `mega`, `quick`, or `utility`
- `position`
- `is_active`
- timestamps

The editor enforces a maximum three-level hierarchy: tab -> section -> link. Quick links are root links displayed beside the catalog trigger. Entity-backed links resolve their current slug at render time; custom links must be relative or point to the current host.

## Admin Editor

Add an admin page "Меню сайта" with:

- a live tree of tabs, sections, and links;
- add controls for existing categories, subcategories, content pages, and custom internal links;
- drag-and-drop ordering using native browser drag events;
- inline label override, active toggle, and delete action;
- a desktop/mobile preview panel;
- server-side validation of hierarchy, source records, placement, and URLs;
- one transactional save followed by menu cache invalidation.

The editor will use the existing admin visual system and will not introduce a new frontend framework or dependency.

## Frontend Menu

### Desktop

- Keep the current header identity and customer actions.
- Replace the nested product tree with a large catalog overlay.
- Left rail: primary tabs such as Жалюзи, Шторы, Рольставни, Ворота.
- Main area: editorial columns made from sections and links.
- Add a local "Найти раздел" field that filters visible menu links without a backend request.
- Keep a restrained measurement CTA in the rail.
- Support Escape, outside click, focus return, keyboard tab switching, and body scroll lock.

### Mobile

- Use a full-width drawer below the compact header.
- Render tabs as accordions with sections and links underneath.
- Keep targets at least 44px high and prevent horizontal overflow.
- Do not reuse the donor's compressed desktop panel on mobile.

The menu will contain no category images. This avoids unnecessary image downloads and keeps the navigation fast.

## Initial Menu Content

Build the initial tree from current production categories, subcategories, content pages, and SEO landing pages. Group links by user intent rather than listing every product. Existing `show_in_menu` and `show_in_catalog` flags remain available for legacy editing but no longer define the full mega-menu structure.

## Product Meta Description

Add a dedicated formatter used by every product template:

- strip HTML and normalize whitespace;
- if the existing description already contains the product title, preserve it;
- otherwise produce `Product title — existing description`, lowercasing the first description letter where appropriate;
- trim on a word boundary to a search-friendly maximum length while keeping the product title at the beginning;
- fall back to a short product-specific sentence when the description is empty.

This fixes duplicate product descriptions without bulk rewriting stored SEO text.

## Performance Work

1. Replace repeated category/subcategory/product menu queries in controllers with one cached navigation service and view composer.
2. Cache the normalized menu payload and invalidate it only after menu edits or linked entity updates.
3. Load Yandex Maps only when the map approaches the viewport; preserve the existing full-width map once loaded.
4. Remove duplicate Google Font imports and use one preconnected stylesheet request.
5. Ensure below-the-fold images use lazy loading and async decoding; preserve eager loading only for the first visible hero image.
6. Avoid loading menu images and full product collections for navigation.
7. Rebuild optimized Vite assets and clear Laravel view/config caches after deployment.

## Safety and Rollback

A verified production backup was created before implementation:

- database dump with 31 tables;
- application code archive;
- gzip/tar readability checks;
- SHA-256 checksums.

Database changes are additive. The legacy flag-based menu data remains untouched, allowing the old header implementation to be restored independently if needed.

## Verification

- Feature tests for menu CRUD, validation, URL resolution, cache invalidation, and authorization.
- Unit tests for product meta-description formatting.
- Existing PHP and JavaScript tests.
- Production-safe migration and route checks.
- Desktop visual QA at 1920x1080.
- Mobile visual QA at 390x844 and 360x800.
- Keyboard and focus testing for the menu.
- Console and network-error review.
- Lighthouse before/after comparison, especially LCP, TBT, transferred bytes, and Yandex Maps scripting.
