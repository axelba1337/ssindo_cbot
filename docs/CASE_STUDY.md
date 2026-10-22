# Neev Assistant — portfolio case study

## Project status

Neev Assistant is an academic portfolio prototype based on the service and product context of CV Neev Solusindo. It demonstrates a deployable Laravel chatbot architecture, but it is not an official production deployment, customer engagement system, or source of independently verified business claims.

- Repository: [github.com/axelba1337/ssindo_cbot](https://github.com/axelba1337/ssindo_cbot)
- Live demo: **TBD — not deployed**
- Primary stack: Laravel 12, PHP 8.3, MySQL, Blade, JavaScript, Gemini, and vector similarity retrieval.

## Problem

A service company homepage must answer different kinds of questions without confusing services with physical inventory. Visitors may ask about CCTV installation, networking, custom software, product availability, office hours, or escalation to a person. A general language model alone can produce plausible but unsupported prices, stock, hours, and guarantees, while a rigid FAQ search struggles with natural follow-up questions.

## Solution

The prototype combines structured company data, retrieval-augmented generation, deterministic business routing, and a floating assistant. Product questions use the hardware catalog, service questions use active service records, and company-specific free-form questions retrieve limited relevant context before Gemini is called. When verified data is absent, the assistant expresses uncertainty and offers an admin/WhatsApp handoff instead of inventing facts.

## Architecture

```text
Blade homepage + floating JavaScript widget
                |
                v
      POST /api/chatbot/query
                |
       validation + rate limit
                |
       opaque conversation token
                |
                v
            QueryRouter
       /          |           \
product/service  RAG        general query
live DB answer    |              |
                  v              v
       semantic cosine +      Gemini
       lexical fallback          |
                  \______________/
                         |
             grounded response + optional
               validated WhatsApp handoff
```

Knowledge records reference their source table and source ID. Synchronization preserves valid vectors, removes records whose source was deleted or deactivated, and safely falls back to lexical matching when embeddings are missing or incompatible. Recent conversation logs provide bounded follow-up context.

## Key features delivered

### Phase 1 — homepage experience

- Neev-style service/product landing page with a responsive floating assistant.
- Existing official Neevwork redirect actions and contact links remain separate from chatbot actions.

### Phase 2 — contextual assistant entry points

- Reusable “Tanya Neev” actions for CCTV, networking, custom software, and IT maintenance.
- The widget opens and prefills a contextual question; users review it before pressing Send.
- Existing quick questions, conversation flow, mobile behavior, and WhatsApp escalation are preserved.

### Phase 3 — business knowledge accuracy

- Product catalog and service capability questions are routed to their correct data sources.
- SIMPUS, HRMS, POS, mobile, and custom applications are handled as software solutions, not physical stock.
- Semantic retrieval is combined with lexical fallback and relevance filtering.
- FAQ lifecycle synchronization removes stale knowledge without resetting master data or rebuilding every embedding.
- Conflict auditing flags business facts that need manual verification.

### Phase 4 — security

- Session-based admin login, explicit admin authorization, no public registration, CSRF-protected mutations, and JSON 401/403 responses.
- Cryptographically random chatbot tokens are stored as keyed hashes; numeric database IDs remain internal.
- Configurable public/admin/login throttles, strict input limits, trusted-proxy controls, safer DOM rendering, URL validation, and security headers.
- Interactive `admin:create` provisioning avoids hardcoded passwords.

### Phase 5 — deployment readiness

- Laravel boot order is compatible with configuration caching and explicit trusted proxy IP/CIDR values.
- Production environment, backup, migration, asset, permissions, HTTPS, and web-server guidance is documented.
- Demo limits remain configurable at 10 requests/minute and 60 requests/hour per client IP.
- No queue worker or scheduler is prescribed because the current code dispatches no jobs or scheduled tasks.

## RAG and fallback workflow

1. Validate the message and optional opaque session token.
2. Route deterministic product, service, contact, office-hour, and assistant intents to structured data.
3. For company-specific questions, request a query embedding and rank current knowledge by cosine similarity.
4. Retain lexical matching when vectors are absent or have incompatible dimensions.
5. Inject only relevant top context items into Gemini, together with bounded conversation history.
6. If Gemini is unavailable, answer from a relevant database match when possible; otherwise return a clear generic failure and optional admin escalation.
7. Escape rendered text and validate external/WhatsApp URLs before creating links.

## Screenshot placeholders

Add screenshots only after staging data and business details are approved.

| Suggested file | Capture |
| --- | --- |
| `docs/screenshots/01-homepage.png` | Homepage hero and service cards |
| `docs/screenshots/02-prefill.png` | “Tanya Neev” opening the widget with an unsent CCTV question |
| `docs/screenshots/03-rag-answer.png` | Grounded product/service answer and source behavior |
| `docs/screenshots/04-whatsapp.png` | Validated WhatsApp escalation button |
| `docs/screenshots/05-admin-faq.png` | Authenticated FAQ management screen |

## Suggested demo scenarios

1. Click CCTV “Tanya Neev”; confirm the contextual message is prefilled but not submitted.
2. Send “Saya membutuhkan instalasi jaringan kantor.” and show the active service answer.
3. Ask whether SIMPUS, HRMS, or POS are available and show that they are treated as software solutions.
4. Ask about a known hardware item and then ask a follow-up about its price or stock.
5. Ask for an unknown product/price and show uncertainty rather than fabrication.
6. Ask for office hours and WhatsApp contact in Indonesian and English.
7. Demonstrate that `/admin` requires login and `/api/admin/faq` returns 401 to a visitor.
8. Reuse a valid chat token for continuity, then show that a numeric session ID is rejected.
9. Demonstrate 429 behavior in staging with a temporarily lower configured limit, not by stressing a public service.

## Testing methodology

- Feature tests exercise homepage rendering, business routing, FAQ synchronization, admin authentication/authorization, CSRF, throttling, session isolation, proxy spoof resistance, security headers, and production-safe error responses.
- Unit tests cover Gemini request contracts, retry behavior, retrieval relevance, vector-dimension fallback, routing, and business conflict detection.
- Gemini and HTTP responses are mocked during automated tests; validation consumes no paid API quota.
- PHP syntax, Laravel/Artisan bootstrap, route middleware, configuration caching, JavaScript syntax, and Vite production builds are checked before handoff.
- Manual staging scenarios cover widget opening, prefilling, Send behavior, answer rendering, and WhatsApp escalation. Automated source/HTTP tests are not a substitute for final browser/device QA.

### Verified local result — 18 October 2026

- Laravel 12.35.1 booted successfully on PHP 8.3.28 after the trusted-proxy boot-order fix.
- The complete automated suite passed: **47 tests, 227 assertions**.
- Tests covered CCTV/networking/software answers, product availability, office hours, WhatsApp, FAQ synchronization, admin controls, CSRF, rate limiting, opaque session continuity, proxy spoof resistance, and production-safe error output.
- Configuration caching and cached Artisan route bootstrap completed successfully, then the cache was cleared for Laragon development.
- The Vite production asset build and scoped Laravel Pint checks completed successfully.
- Automated Gemini interactions were mocked; no paid Gemini request was made.
- Browser/device visual QA and a real hosted smoke test were not performed because no hosting/domain was approved.

## Challenges and engineering decisions

- Separating software capabilities from sellable hardware required deterministic routing before RAG.
- Keeping knowledge synchronized without unnecessary paid embeddings required source-aware, idempotent updates.
- Retrieval needed semantic ranking without allowing weak or unrelated context to override business data.
- Conversation continuity had to move from guessable database IDs to unguessable bearer tokens without changing the existing log schema.
- Proxy trust had to be activated after Laravel configuration boot so config caching works and forwarded IP headers cannot bypass rate limits.

## Limitations

- Business facts, office hours, contacts, and service claims still require approval from CV Neev Solusindo.
- This prototype has no approved production domain, hosting, SLA, load test, high-availability design, or disaster-recovery evidence.
- Gemini availability, model lifecycle, quotas, and cost remain external dependencies.
- The admin role is intentionally simple; MFA, password recovery, detailed audit trails, and granular permissions are not implemented.
- Existing embeddings are tied to their configured model/dimension and require a planned migration if that contract changes.
- Browser/device screenshots and accessibility testing must be completed against an approved staging deployment.

## Future improvements

- Add owner-approved content governance and conflict-resolution workflow.
- Add CI/CD with test, build, secret scanning, deployment approval, and rollback stages.
- Add MFA, password recovery, audit events, and retention controls for chatbot logs.
- Add an evaluation dataset for grounded-answer quality and bilingual consistency.
- Add privacy-aware monitoring, quota dashboards, and alerts for Gemini failures and rate-limit abuse.
- Move expensive synchronization to queued jobs only if workload measurements justify a worker.
- Publish a live demo only after hosting, domain, SSL, business-data approval, and security review are complete.
