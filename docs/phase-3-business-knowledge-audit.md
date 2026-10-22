# Phase 3 Business Knowledge Audit

Audit basis: current local master tables and `resources/views/welcome.blade.php` on 2026-10-10.

## Safe correction applied

- `faq_qa:45` denied mobile development, while active `services:3` explicitly offers web/mobile Software Development and the homepage presents custom software products. Migration `2026_10_10_000001` replaces only the known contradictory answer and invalidates its stale embedding.
- Orphaned FAQ knowledge for deleted source IDs `5`, `6`, `26`, `39`, `40`, `41`, and `42` is safe to prune. No master business record is deleted.

## Manual business verification required

- Pricing: `faq_qa:13` states CCTV installation starts at Rp350,000 per point, while `faq_qa:35` says per-point pricing cannot be generalized. Confirm the approved public pricing policy.
- Survey commitments: `faq_qa:36` and `faq_qa:37` promise scheduling within one business day and estimates within 24 hours. `faq_qa:38` says surveys are usually free. Confirm these operational guarantees.
- Email: primary database contact `company_contacts:2` is `support@neevwork.com`; the homepage displays `admin@neevwork.com`. Confirm which is public/primary.
- Address: `company_contacts:3` contains a regional service-area description, while the homepage shows a full Kauman street address. Confirm the canonical contact address and service-area wording.

## Consistent information

- WhatsApp `+62 813-3511-7054` is consistent between the database and homepage.
- Office hours `09:00-17:00 WIB` are consistent between `office_hours:1` and the active office-hour FAQs.
