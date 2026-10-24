# Migration Checklist — Base44 → PHP/MySQL (v1.0.0)

| # | Original (Base44) | Status | Implementation |
|---|---|---|---|
| 1 | Home: Hero (typewriter, search, stats, deployment feed) | ✅ | `pages/home.php` + `app.js` typewriter; feed from `deployment_feed` |
| 2 | Home: Orbit section (14 brand nodes, rings, rotation) | ✅ | CSS/JS orbit, same brands & colors |
| 3 | Home: Software Matrix (category tabs, search, region filter, monthly/yearly toggle, mobile accordion) | ✅ | Server-side region filter + client-side category/search |
| 4 | Product card + Order modal (WhatsApp deep link, book → Order record, admin & customer emails) | ✅ | `partials/order_modal.php`, `POST /order` (JSON), `Mailer` |
| 5 | ERP Bridge (ErpSolution cards, detail links) | ✅ | `/solutions/{slug}` static landings + `/erp/{id}` DB detail |
| 6 | Project Archive (horizontal scroller) + Projects page | ✅ | |
| 7 | Cyber Security section + page with Security Assessment form | ✅ | `POST /cyber-security/assessment` |
| 8 | Services section + Services page + Certifications | ✅ | |
| 9 | Testimonials | ✅ | |
| 10 | Global Connect (region tabs, live local time, offices) | ✅ | Local time rendered server-side + JS tick |
| 11 | About page (PageBlock-editable copy, values, offices) | ✅ | `PageBlock::get()` with original defaults |
| 12 | Contact page (form → ContactMessage, WhatsApp, email) | ✅ | `POST /contact` |
| 13 | Track Order (`trackOrder` function) | ✅ | `POST /track-order`, same limited fields |
| 14 | Live Chat (Core.InvokeLLM with catalog context) | ✅ | `POST /api/chat`: OpenAI-compatible API when key set, otherwise catalog-based answers |
| 15 | 4 regions (UK/UAE/IN/PK) currency conversion, IP detection | ✅ | `Region.php`; cookie prefs; CF-IPCountry/Accept-Language/JS-timezone detection |
| 16 | 4 languages (en/ar/ur/hi) incl. RTL | ✅ | `lang/*.php` ported verbatim from `i18n.js` |
| 17 | Admin gate (shared password) | ✅ Upgraded | Per-user accounts, bcrypt, throttling, password reset, sessions, CSRF |
| 18 | Admin CRUD for 13 entities (`adminCrud` + EntityCrud.jsx) | ✅ | Schema-driven CRUD, search, status filters, pagination, CSV export, image upload, activity log |
| 19 | Site settings (name, logo, hero title/tagline) | ✅ | + admin email & WhatsApp number |
| 20 | Base44 auth pages (Login/Register/Forgot/Reset/OAuthConsent) | ✅ Replaced | Admin login / forgot / reset (public registration not needed by the site) |
| 21 | Images hosted on media.base44.com | ✅ | 14 images downloaded to `uploads/migrated/`, URLs rewritten in seed |
| 22 | Data: all 13 entities, every record | ✅ | 179 rows; Base44 ids preserved in `legacy_id` |

Known differences: Google Favicon service is still used for orbit brand logos (falls back to an icon); currency rates are static as in the original (`Region.php`).
