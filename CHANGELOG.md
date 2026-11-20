# Changelog

## 2.1.0 (branch `fee/one-public-keynote-fee-2026-11-08`)

Added
- Booking page and home page built around the question "What is your audience's main problem?" (five problems), with one form, one booking address (`BookingFacts::form_url()`), and Theme Options "Booking Page Facts" (fees, terms, counts, reel, form ID).
- The demo reel plays from a YouTube link or an MP4 file. A YouTube reel loads nothing until the dialog opens, and the sound stops on close.
- The schema feed (`crispy_seo_speaker_profile`) gives the plugin the Person description, affiliation, topics, credentials, booking URL and email, and the reel.
- Home footer: Blog, Speaking, Newsletter, About, Contact, LinkedIn, Trust Insights, RSS, and the privacy page. The old footer also linked a Terms page; no Terms page exists, so the link is not carried over.
- Tests for the booking, home, schema feed, packaging, layout, and removed-claims rules (about 800 tests).
- `bin/package.sh` builds the release zip. `.github/workflows/release.yml` calls it and checks the tag against `style.css`.

Changed
- The front page prints one pattern, `crispytheme/home-page`. The older home patterns stay registered; the front page does not use them. Theme Options for the old home sections say so.
- The theme prints its own schema only when the plugin does not define `CRISPY_SEO_SCHEMA_GRAPH` (plugin 1.1.0 and later).
- Number options keep blank (default returns) and drop commas, spaces, and a dollar sign. A decimal point is kept and the fraction dropped.
- The hero eyebrow defaults to empty. The unsourced events, countries, and satisfaction stats default to empty.
- The default booking address is the booking form, not `/contact-me/`.
- The problem list has five problems; governance is not a talk topic (Penn, 2026-11-13).
- Build and CI: `composer.lock` and `package-lock.json` are tracked, so CI, a laptop, and the release build install the same versions. The workflows run on PHP 8.4 (the locked Symfony 8 packages need it, and production runs it). A tracked `phpunit.xml.dist` lists the Unit, Integration, and Architecture suites; `composer test` runs Unit and Integration, then Architecture as a second command. The release job fetches the full history for its changelog step.
- Dev tools updated for security advisories (Pest 4.7, PHPUnit, PHP_CodeSniffer, WPCS, Symfony Process). None ships in the release zip.

Removed
- The "View Speaker Bureau" button on the booking band.
- Home audience routing links (three ICP links) and the old home hero image, newsletter band, stat strip, and client-logo marquee (replaced by the sections above).

Fixed (the 2.0.3 and 2.0.4 hotfix that ran on the live site, and later)
- No commit or tag holds 2.0.3 or 2.0.4. The live site ran 2.0.4: a layout fix (eight templates use the default layout so the CSS grid applies; `--crispy-sidebar-width` and `--crispy-max-width` use `-base` variables so the admin width options work) and a repoint of the bureau link. This release contains all of it.
- The dark-mode choice survives a reload (one storage key, with a fallback to the old key).
- Phones do not scroll sideways (embeds, tables, and code are bounded).
- The release workflow copied folders that did not exist; it now calls `bin/package.sh`.

Local tags `v2.0.0`, `v2.0.1`, `v2.0.2` (54b1e41, 46d966f, af96763 at the time of this entry) were never pushed. They are removed with this release, because each would run an older release workflow that copies the wrong folders.
