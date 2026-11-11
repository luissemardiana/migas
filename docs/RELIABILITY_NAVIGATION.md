# Reliability navigation and pages

The shared header now groups the existing homepage navigation under **Training** and adds **Reliability Services**, **Reliability Technology**, and **Reliability Competency**.

## Content and routes

- `config/reliability-services.json`: 46 independently written Indonesian detail entries (26 services, 10 technology topics, 10 competency topics). Each has a unique slug, introduction, audience, scope, and intended outputs.
- `app/services.php`: category metadata, catalog access, image paths, and topic-specific inquiry links.
- `public/services.php?category=services|technology|competency`: three category catalogs.
- `public/service.php?slug=...`: distinct, linkable detail documents. Unknown slugs and categories return HTTP 404.
- `resources/views/components/reliability-nav.php`: isolated view scope so header iteration does not overwrite page content variables.

The reference site's taxonomy was requested by the user. Its prose, product brochures, logos, project history, certification claims, and images were not copied. New content describes discussion scopes without inventing availability, prices, schedules, credentials, official vendor relationships, or equipment ownership. Certification pathways explicitly require confirmation of the organizer and prerequisites.

Consultation actions open the existing official WhatsApp destination with the selected topic, or the existing official email address with a matching subject. They do not submit a training registration or create a database record. Existing training forms, database integrations, portal pages, home-return behavior, hero, and home-only footer remain in place.

## Interaction

- Desktop: pointer hover opens one panel; moving into the panel keeps it open. Buttons support click, Enter/Space, ArrowDown, Tab, Escape, and outside click.
- Below 1280px: existing hamburger opens a scrollable navigation panel with native expandable category groups.
- Reduced motion removes menu and new-page transitions.
- Four local optimized illustrations are documented in `public/assets/images/services/SOURCES.md`.

## Verification

Run `node tests/reliability-navigation.cjs` with Playwright installed and a local PHP server on port 8088. Set `MIGAS_CHECK_URL` for another target. Checks cover every detail URL, category membership, own content, image availability, 404 responses, hover/keyboard/touch navigation, and viewport widths 320–1440px. Browser screenshots are written to the existing temporary tooling directory.
