# MEKOSOFT LMS — Brand guidelines

This guide documents the visual identity implemented by the `theme_meko` child theme. Moodle theme directory: `theme/meko`; parent theme: `theme_boost`.

## Brand foundations

MEKO-LMS is the digital learning platform in the MEKOSOFT ecosystem. Its interface should feel clear, trustworthy, and practical for higher education and professional training. Use a restrained layout, strong page hierarchy, and consistent navigation. Keep course and learning information more prominent than decoration.

## Color palette

| Token | Hex | Role |
|---|---|---|
| MEKO Royal Navy | `#1E2F97` | Primary actions, links, active navigation, and brand emphasis |
| MEKO Deep Navy | `#162566` | Hover states and dark primary surfaces |
| MEKO Warm Orange | `#F57C00` | Small highlights, active indicators, and action accents |
| MEKO Sage | `#6F8A4F` | Supporting brand accent |
| Success Green | `#16A34A` | Success and completed learning states |
| Dark Slate | `#0F172A` | Footer and high-emphasis dark surfaces |
| Canvas | `#F8FAFC` | Main page background |
| Body text | `#1E293B` | Default text |

Use the SCSS variables in [`scss/preset/default.scss`](./scss/preset/default.scss) rather than introducing new color values. Keep surfaces solid. Orange is an accent, not a color for long text; use navy or white text based on the background and maintain readable contrast.

## Logo and typography

- Use the horizontal MEKOSOFT LMS logo from `pix/logo.svg` (or `pix/logo.png` where SVG is unsupported) on light backgrounds.
- Use `pix/logo_white.svg` / `pix/logo_white.png` on dark backgrounds.
- Use the MEKO favicon assets in `pix/` for browser and application icons.
- Preserve the logo proportions and clear space. Do not recolor, skew, stretch, or recreate it with text.
- Use **Be Vietnam Pro** for headings and body text. The theme falls back to the system sans-serif stack if the web font is unavailable.
- Use the official brand assets in templates via Moodle's theme image helpers rather than hardcoding an asset URL.

## Interface rules

- **Navigation:** white surface, navy text, clear active state, orange active indicator. Keep primary navigation short and based on real Moodle routes.
- **Actions:** use navy for primary actions and orange sparingly for emphasis. Provide visible hover and keyboard-focus states.
- **Cards and content:** use white surfaces, restrained borders, consistent spacing, and rounded corners from the preset.
- **Footer:** use the dark slate surface and white logo.
- **Responsive behavior:** keep essential course and account routes reachable at narrow widths; do not rely on hover-only interactions.
- **Accessibility:** use semantic headings and navigation landmarks, meaningful logo alt text, keyboard-operable controls, and sufficient text/background contrast.

## Main navigation sitemap

The front-page menu is rendered from the route list in [`layout/frontpage.php`](./layout/frontpage.php). Entries are shown according to the current user's access:

| Menu item | Moodle route | Visibility |
|---|---|---|
| Home | `/` | Everyone |
| Course catalog | `/course/index.php` | Everyone |
| My courses | `/my/courses.php` | Signed-in users |
| Competency frameworks | `/admin/tool/lp/competencyframeworks.php?pagecontextid={system-context-id}` | Signed-in users with `moodle/competency:competencyview`, when competencies are enabled |
| Site administration | `/admin/search.php` | Site administrators |

The competency link includes Moodle's required system context parameter and is omitted for users without access. Internal Moodle pages continue to use Boost's standard primary navigation; the theme adds the course catalog to that navigation for signed-in users.

## Install and activate

1. Copy or clone this repository into Moodle as `moodle/theme/meko` (the theme component is `theme_meko`).
2. Ensure the parent theme Boost is installed and enabled.
3. Run Moodle's upgrade and purge caches:

   ```sh
   php admin/cli/upgrade.php --non-interactive
   php admin/cli/purge_caches.php
   ```

4. Select **MEKOSOFT LMS** at **Site administration → Appearance → Themes → Theme selector**. For a CLI-only local setup, set the site `theme` config value to `meko`.
5. In production, keep theme designer mode disabled. Enable it only during local theme development, then purge caches when finishing.

For the repository workflow and container deployment steps, see [`README.md`](./README.md).

## Change control

Update this guide when the logo, palette, typography, navigation routes, or visual rules change. Keep the SCSS preset, logo assets, language strings, and menu visibility behavior aligned with this document.
