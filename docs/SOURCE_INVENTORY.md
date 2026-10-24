# Source Inventory — Quantum Nexis Base44 app → PHP/MySQL migration

Base44 app ID: 6a7240b50f7686103f060c79 ("Quantum Nexis Enterprise Solutions")

## Archive: icodic-core-sync.zip (225,576 bytes, 136 files)
Complete Base44 project export (frontend source + entity schemas + backend functions). No built `dist/`.

| Area | Files | Notes |
|---|---|---|
| Entities | base44/entities/*.jsonc (14) | 13 app entities + User (role admin/user) |
| Functions | base44/functions/adminCrud, trackOrder | Deno/TS. Password-gated CRUD + public order lookup |
| Pages | src/pages (15) | Home, About, Contact, Services, CyberSecurity, Projects, TrackOrder, ErpDetail, ErpSolutionLanding, Admin + Base44 auth boilerplate (Login, Register, ForgotPassword, ResetPassword, OAuthConsent) |
| Sections | src/components/sections (10) | Hero, Orbit, SoftwareMatrix, ProductCard, ErpBridge, ProjectArchive, CyberSecurity, ServicesSection, Testimonials, GlobalConnect, Certifications |
| Shared | Navbar, Footer, Layout, LiveChat, OrderModal, PageContent, AdminGate, admin/EntityCrud | |
| Libs | regions.js (4 regions, currencies, IP detection), i18n.js (en/ar/ur/hi), AppContext | |
| Styling | index.css + tailwind.config.js | Palette: primary 243 75% 59%, mint 158 64% 40%, cobalt 226 71% 55% |

## Data (record counts verified)
| Entity | Source | Rows | Notes |
|---|---|---|---|
| SoftwareProduct | CSV | 118 | brands: Other 44, Microsoft 33, Adobe 21, Autodesk 20. 4 have images, 7 featured. All regions `["UK","UAE","IN","PK"]` |
| Project | CSV | 17 | 4 with images, 4 featured |
| Service | CSV | 11 | |
| Testimonial | CSV | 5 | |
| Office | CSV | 4 | UK, UAE, IN, PK |
| DeploymentFeed | CSV | 7 | |
| Order | CSV | 2 | IC-LQBWMU (processing), IC-LQ29HW (completed) |
| SecurityAssessment | CSV | 1 | test record |
| SiteSetting | CSV | 1 | logo URL on media.base44.com, admin_password empty |
| ErpSolution | live app read | 5 | ERPNext, Odoo, Custom CRM, Custom ERP, MS Dynamics 365 (all with images) |
| CyberService | live app read | 6 | |
| ContactMessage | live app read | 2 | test records |
| PageBlock | live app read | 0 | feature exists, no data |

No duplicate CSVs were supplied; all IDs unique within each file.

## Base44-specific features and their replacements
| Base44 feature | Replacement |
|---|---|
| `base44.entities.*` SDK | MySQL tables + PDO repository |
| `adminCrud` function with shared password | Admin users table, password_hash, sessions, CSRF, login throttling |
| `trackOrder` function | `POST /track-order` controller (public, limited fields) |
| `Core.SendEmail` | Mailer (PHP mail() or SMTP with STARTTLS), configurable |
| `Core.UploadFile` | Local upload to `public_html/uploads` with MIME/extension validation |
| `Core.InvokeLLM` (LiveChat) | OpenAI-compatible API via cURL (optional key) with a catalog-based offline fallback |
| media.base44.com images | Downloaded into `public_html/uploads/migrated/`, URLs rewritten |
| ipapi.co region detection | Cookie-based prefs; JS timezone detection on first visit; server honours CF-IPCountry when present |
| Base44 auth pages (Login/Register/etc.) | Admin login, logout, forgot/reset password by email token |
| React Query / client rendering | Server-rendered PHP views + small vanilla JS |
