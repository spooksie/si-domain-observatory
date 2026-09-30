# QQuantum.ai .si Domain Observatory

Explore dictionary words, modern service names, names and nicknames, and **Domain hacks** where the domain and suffix spell a complete word: `las.si → lassi`, `sen.si → sensi`.

Branded with the QQuantum.ai repository's original wordmark and Bureau palette. [Visit QQuantum.ai](https://qquantum.ai/).

## Shareable website

[Open the observatory](https://spooksie.github.io/si-domain-observatory/).

The GitHub Pages version is served from `docs/`. It displays a dated registrar snapshot, including unavailable names. Available is the default. Choose one theme from the dropdown, or toggle the theme buttons to combine several categories. Each theme shows its available count; the count display can be switched off. All themes clears the category selection. Use Available / All names to switch availability views. Registrar links let you verify availability before checkout. Bought and saved flags, plus your added suggestions, stay in your browser's local storage; they are personal, and clearing browser data removes them. Download Markdown exports your current tracking flags. No account or database is needed.

## Local live viewer

The PHP viewer at `http://sidomains.test/` keeps data and tracking flags in `data/domains.json`, regenerates `DOMAINS.md`, and supports live Domenca checks. Run with Herd, or `php -S localhost:8787 router.php`. Live endpoints can impose limits; failures remain unconfirmed rather than being labelled available.

The public snapshot in `docs/domains.json` can seed a new local install: create `data/` and copy it to `data/domains.json`. The repository excludes local flags, raw checking evidence and logs. Local and browser tracking are separate.

After updating local checks, run `python3 scripts/build-pages.py` to refresh the public export. The exporter resets bought and saved flags for visitors. Commit `docs/` and push to `main`; the Pages workflow publishes it.

## Availability and pricing

Each checked result includes the registrar source and UTC timestamp. Availability is not a reservation and can change until registration completes. A result from one registrar is not independent confirmation at both. Prices reflect that registrar's returned first-period EUR amount including VAT; checkout and renewals may differ. Personal names, coined names and borrowed-language domain hacks are labelled separately from English dictionary terms. Suggestions do not establish trademark clearance.

`.si` is Slovenia's country-code domain. Super intelligence is a branding interpretation.

## Two- and three-letter names

The **2–3 letter names** theme includes every alphabetic domain label of that length in the catalogue, even when its original theme is a personal name or domain hack. Existing themes and personal flags are preserved. The new candidate search covers all 676 two-letter combinations and a curated set of three-letter letter combinations, vowel-led names, words and acronyms; it is not an exhaustive search of all 17,576 three-letter combinations. Letter count excludes `.si`. Availability comes from the same dated registrar checker.
