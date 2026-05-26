# Landing Page Redesign — BarangayBooks

**Date:** 2026-05-27
**Status:** Approved (verbal), pre-implementation
**Owner:** kcandelario09@gmail.com

## Goal

Replace the default Laravel scaffold at `/` with a professional, trustworthy SaaS landing page that converts Philippine Homeowners Associations into free-trial signups.

## Constraints

- Single Blade file: `resources/views/welcome.blade.php` (route already binds in `routes/web.php:13-15`).
- Tailwind via CDN (matches existing tenant layout in `resources/views/layouts/layout.blade.php`). No build step required for landing.
- Alpine.js already a project dep — used for FAQ accordion + mobile nav.
- Match established design system: primary `indigo-600`, status palette already in CLAUDE.md, peso symbol `&#8369;`, dark mode via `dark:` prefix + localStorage flag applied before paint.
- Auth routes already exist (`route('login')`, `route('register')` from Breeze). Privacy + Terms routes already exist (`route('privacy')`, `route('terms')`).
- No new dependencies. No image assets — the hero "dashboard preview" is rendered in pure HTML/CSS.
- Pre-launch: no testimonial section, no logo cloud, no live chat — these would read as fake.

## Brand & Conversion

- **Name:** BarangayBooks
- **Tagline (H1):** "Collect monthly dues without the group chat chaos."
- **Primary CTA:** `Start free trial` → `route('register')`
- **Secondary CTA:** `See how it works` (in-page anchor) and `Log in` (nav)
- **Trust microcopy:** "No credit card · Free during beta · Export your data anytime"

## Page Sections (top → bottom)

1. **Sticky nav** — Wordmark, anchor links (Features, How it works, Pricing, FAQ), Log in (ghost), Start free trial (solid). Mobile hamburger via Alpine.
2. **Hero** — Two-column on lg / stacked mobile. Left: eyebrow chip ("Built for Philippine HOAs"), H1, subhead, dual CTA, microcopy. Right: stylized mock dashboard card (contributions table with verified/pending badges, peso totals).
3. **Problem / Solution** — Three pain bullets ("Receipts buried in GC chats", "Manual ledgers, missing months", "No proof of who paid what") → one-line transition.
4. **Features grid** — 6 cards mapping to shipped capabilities:
   - Block + Lot resident lookup (rate-limited)
   - Photo-verified payments
   - PDF / Excel / CSV reports
   - Delinquency tracker (`monthly_due_amount`)
   - Audit log on every action
   - Multi-organization (one login, many HOAs)
5. **How it works** — 3 steps: ① Create org, ② Bulk-import households (CSV), ③ Start collecting & verifying.
6. **Trust strip** — Emerald accent band. Four items: Encrypted storage · Role-based access · Tamper-evident audit log · PH-hosted option.
7. **Pricing teaser** — Single centered card. "Free during beta" badge, inclusion list, CTA `Claim your free spot`. Sub-line: "Founding HOAs lock in launch pricing."
8. **FAQ** — Alpine accordion, 6 items. Data ownership, export, security, switching cost, onboarding speed, GCash (honest: "On the roadmap. CSV import today.").
9. **Final CTA band** — Indigo gradient, white text. `Start free trial` + `Talk to founder` (mailto).
10. **Footer** — Brand line, columns (Product, Company), copyright, Privacy/Terms links.

## Accessibility

- Semantic landmarks (`<header>`, `<main>`, `<section>`, `<footer>`).
- Single H1, hierarchical H2/H3.
- All interactive elements keyboard-reachable.
- AA contrast on both light/dark.
- `aria-expanded` + `aria-controls` on FAQ buttons.

## Out of Scope

- Testimonials, logo cloud, blog/resources, live chat, video, animated hero.
- Changes to existing tenant layout.
- New backend routes or controllers.
- Email-capture form (CTA goes straight to `/register`).

## Risk / Open Items

- Pricing tier copy may change once beta cohort lands — kept generic ("Free during beta") to defer commitment.
- GCash FAQ answers honestly to roadmap status to avoid overpromising.
