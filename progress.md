# Kevin Cho Tailoring — Project Progress

**Site:** https://kevincho.com
**Business:** Custom tailoring & ready-to-wear ecommerce, Cameroon
**Currency:** FCFA (XAF)
**Plugin:** `kevincho-tailoring-manager` v1.3.0
**Last Updated:** 2026-02-20

---

## COMPLETED FEATURES

### 1. Custom Tailoring Plugin Core (v1.0.0)
**Status:** COMPLETE
**Date:** 2026-02

Built a full WordPress/WooCommerce plugin with:
- **Customer Measurements** — 20+ body measurement fields (chest, waist, hips, sleeve, inseam, etc.) stored per customer
- **Measurement Fields** — Gender-aware (male/female/child), with min/max validation and units
- **Walk-in Customers** — Admin form to register in-store customers with phone number, optional email (auto-generates placeholder email)
- **Custom Order Statuses** — Confirmed → In Progress → Ready for Pickup → Delivered (in addition to standard WooCommerce statuses)
- **In-Store Orders** — Create orders directly from admin for walk-in customers
- **Consultation Booking System** — Customers book consultations (15,000 FCFA), with date/time, confirmation, completion, cancellation flows
- **Consultation Reminders** — Hourly cron job sends reminder day before appointment
- **Garment Personalization** — Custom options during checkout (fabric, style, etc.) with price adjustment
- **Fabric Management** — Admin interface for managing available fabrics
- **Notification Log** — Database table logging all WhatsApp/SMS notifications sent

### 2. MTN MoMo Payment Gateway
**Status:** WAITING — Awaiting MTN "Go Live" approval
**Date:** 2026-02-14

- Installed "Payment Gateway for MTN MoMo on WooCommerce" plugin
- Configured with sandbox credentials
- **Sandbox testing passed:**
  - Token generation: Working
  - Request-to-pay (success): STATUS = CREATED
  - Request-to-pay (fail): STATUS = FAILED / APPROVAL_REJECTED
- User submitted "Go Live" request on MTN Developer Portal
- **Blocked on:** MTN approval for production access
- **When approved:** Update plugin to production environment, swap credentials, test with real MoMo number

### 3. Store Management Plugin Stack
**Status:** COMPLETE
**Date:** 2026-02-14

Installed and configured 5 plugins:

| Plugin | Purpose | Status |
|--------|---------|--------|
| FluentCRM | Email marketing, CRM, automation | Configured, 5 contacts imported |
| ATUM Inventory Manager | Fabric stock tracking | Installed, Stock Central ready |
| Cost of Goods for WooCommerce | Profit tracking per order | Installed, "Cost" field on products |
| PDF Invoices & Packing Slips | Auto-generate PDF invoices | Configured (A4, branded, auto-attach to emails) |
| Admin Menu Editor | Reorganize WP admin sidebar | Installed |

**Custom Admin Branding** deployed — admin sidebar, buttons, inputs all use brand colors (brown #402417, gold #c9a96e).

### 4. Store Manager Portal (SPA)
**Status:** COMPLETE
**Date:** 2026-02-14
**URL:** https://kevincho.com/store-manager/

Full single-page application replacing WordPress admin for daily store operations:

| Section | Features |
|---------|----------|
| Dashboard | Customer count, orders today, pending orders, revenue, recent orders, upcoming consultations |
| Orders | Filter by status, search, change status, order detail with items + measurements |
| Customers | Card layout, search, add walk-in, view/edit measurements |
| Consultations | Status tabs, complete/cancel/resend actions |
| Products | Grid/table view, search, filters, bulk actions, full product editor (6 tabs), drag-drop image upload, quick add, analytics |
| Send Email | Compose to all or specific customer (wp_mail or FluentCRM) |
| Invoices | List orders with PDF download button |
| Notifications | WhatsApp/SMS notification log |

- 21 AJAX endpoints
- Vanilla JS, no framework
- Mobile-responsive
- Custom login screen for non-authenticated users
- Brand colors throughout

### 5. Multi-Currency + International Payments
**Status:** COMPLETE
**Date:** 2026-02-14

- Auto-detects visitor country via IP → shows prices in local currency
- Manual currency switcher (floating button)
- Supported: USD, EUR, GBP, XAF, CAD, NGN
- Exchange rates fetched daily, cached in WP transient
- Payment gateway routing: XAF → shows MoMo; other currencies → PayPal only

**PayPal Payments** — Connected via OAuth, supports PayPal, Apple Pay, Google Pay, credit/debit cards.

### 6. Frontend Branding + Categories + Navigation + About Us
**Status:** COMPLETE
**Date:** 2026-02-16

**Branding CSS** — All WooCommerce/Vasia theme elements overridden with brand colors (buttons, links, prices, sale badges, My Account nav, star ratings, notices, pagination, forms).

**Product Categories Restructured:**
- Men → Suits, Agbada, Kaftan, Dashiki, Shirts, T-Shirts, Trousers, Shoes, Accessories
- Women → Dresses, Skirts, Blouses, Accessories
- Kids → Boys, Girls
- Custom Wear (standalone bespoke service)

**Navigation Menu** — JS-injected hamburger menu with nested accordion (Men/Women/Kids expandable with subcategories). Links to: Home, Shop, Custom Wear, Consultation, About Us, Profile, Contact Us.

**About Us Page** (ID 5633) — Brand story page with hero, Our Story, What We Offer cards, Our Craftsmanship section, CTAs.

### 7. Frontend Enhancements
**Status:** COMPLETE
**Date:** 2026-02-16

- Product search bar (JS-injected above shop grid)
- Social share buttons (Facebook, WhatsApp, X, Email, Copy Link)
- Newsletter signup (floating bottom bar, FluentCRM integration)
- Quick View button enabled
- YITH Wishlist plugin installed
- Product reviews enabled
- Size attribute (XS–XXXL) and Color attribute created
- Shipping zones: Cameroon (flat 2,000 XAF, free >50,000, store pickup) + International (flat 15,000 XAF)
- WooCommerce email templates branded

### 8. Homepage
**Status:** COMPLETE
**Date:** 2026-02-16

- Custom homepage (Page ID 5660) with full-width hero
- Delivered via mu-plugin (`kc-fix8.php`) using output buffer injection to bypass 10Web optimizer
- White logo, scroll animation (logo grows/fades), mini header after hero
- Brand colors throughout

### 9. WhatsApp + SMS Notification System
**Status:** PARTIALLY COMPLETE (code deployed, APIs not yet connected)
**Date:** 2026-02-20
**Plugin Version:** 1.3.0

Built a unified notification dispatcher that sends WhatsApp AND SMS messages for all store events:

**New Files:**
- `includes/notifications/class-kctm-notification-dispatcher.php` — Central hub routing to WhatsApp + SMS channels
- `includes/notifications/class-kctm-sms-api.php` — Africa's Talking SMS API client
- `includes/notifications/class-kctm-notification-hooks.php` — Hooks into all WooCommerce + KCTM events

**17 Notification Events:**

| Group | Events |
|-------|--------|
| Orders | New order, Processing, Confirmed, In Progress, Ready for Pickup, Delivered, Completed, Cancelled, Refunded, Customer Note, Tracking Update |
| Customers | New Account, Walk-in Created |
| Consultations | Confirmed, Reminder, Cancelled |

**Settings Page** — 3-tab interface:
1. WhatsApp tab — Meta Business Cloud API credentials (access token, phone number ID, test button, setup guide)
2. SMS tab — Africa's Talking credentials (username, API key, sender ID, sandbox/production, test button, setup guide)
3. Notification Channels tab — Per-event toggle grid (WhatsApp checkbox + SMS checkbox for each of the 17 events)

**Message Templates** — English and French bilingual messages with dynamic placeholders ({customer_name}, {order_id}, {order_total}, etc.)

---

## IN PROGRESS / PENDING

### WhatsApp Business Cloud API Setup
**Status:** COMPLETE
**Date:** 2026-03-23

- Meta Developer App: "Kevincho Notification" (App ID: 908945581500515)
- System User: "KevinCho API" (ID: 61577498160957) with Admin role
- Permanent access token generated (never expires)
- Phone Number ID: 1089965890858650 (Meta test number: +1 555 633 1171)
- Business Account ID: 25248828858126675
- Connection test: PASSED
- WhatsApp enabled for all 13 notification events
- **Setup**: Real business number (+237 679 48 76 32) stays on WhatsApp Business app for customer chat; test number used for automated notifications via Cloud API

### Yoast SEO
**Status:** COMPLETE
**Date:** 2026-03-23

- Yoast SEO v27.1.1 installed and activated
- Site representation: Organization "Kevin Cho Tailoring"
- Custom SEO titles and meta descriptions for all pages, products, and categories (via Yoast filter hooks in plugin)
- XML Sitemap live at /sitemap_index.xml (8 sub-sitemaps)
- Schema.org markup (Organization, BreadcrumbList, WebPage)
- OpenGraph titles/descriptions for social sharing
- Store Manager + My Account pages set to noindex

### SMS Provider Setup
**Status:** BLOCKED — Africa's Talking account stuck in verification loop
**Priority:** MEDIUM

- Account created at africastalking.com (username: bacha, app: kevincho)
- API keys generated but all return 401 (authentication invalid)
- Account shows "not fully active yet" — requires API interaction to verify, but API rejects all keys
- **Options:**
  - Contact Africa's Talking support (support@africastalking.com) to resolve verification
  - Switch to Twilio (global, works in Cameroon, free $15 trial credit)
  - Switch to Termii or Infobip (Africa-focused alternatives)
- SMS credentials saved in plugin settings, will work once valid API key is obtained

### MTN MoMo Go Live
**Status:** WAITING on MTN
**Priority:** HIGH

- Sandbox fully tested and working
- "Go Live" request submitted on MTN Developer Portal
- When approved: update plugin to production mode, swap credentials, test with real number

### Suit Configurator
**Status:** DEFERRED
**Priority:** LOW

- Built with multiply-blend compositing (grayscale suit + fabric color overlay)
- Working at https://kevincho.com/design-your-suit/
- Visual quality not satisfactory — user said "we will come back to it"
- Explored buying third-party plugins (CodeInterest $249, CodeZel $149) — both risky

---

## SITE ARCHITECTURE

### WordPress Setup
- **Hosting:** SiteGround
- **WordPress:** 6.9.1
- **Theme:** Vasia (with child theme)
- **Page Builder:** Elementor
- **Caching/Optimizer:** 10Web Speed Optimizer (complicates CSS delivery)
- **CDN:** CloudFlare

### Key Pages
| Page | URL | ID |
|------|-----|----|
| Home | / | 5660 |
| Shop | /shop-ready-made/ | — |
| Custom Wear | /custom-wear/ | — |
| Consultation | /consultation/ | 5308 |
| About Us | /about-us/ | 5633 |
| Contact Us | /contact-us/ | 5277 |
| Profile (My Account) | /profile/ | 14 |
| Design Your Suit | /design-your-suit/ | — |
| Store Manager Portal | /store-manager/ | 5599 |
| Cart | /cart/ | — |
| Checkout | /checkout/ | — |

### Plugin Files (v1.3.0)
```
kevincho-tailoring-manager/
├── kevincho-tailoring-manager.php          # Main plugin file
├── includes/
│   ├── class-kctm-loader.php               # Bootstrap, hooks, asset loading
│   ├── class-kctm-activator.php            # DB table creation on activate
│   ├── class-kctm-deactivator.php          # Cleanup on deactivate
│   ├── class-kctm-currency.php             # Multi-currency + geolocation
│   ├── class-kctm-frontend-enhancements.php # Search, share, newsletter
│   ├── admin/
│   │   ├── class-kctm-admin-settings.php   # 3-tab settings (WhatsApp/SMS/Channels)
│   │   ├── class-kctm-admin-walkin.php     # Walk-in customer form
│   │   ├── class-kctm-admin-dashboard.php  # Admin dashboard
│   │   └── ...
│   ├── notifications/
│   │   ├── class-kctm-notification-dispatcher.php  # Central notification hub
│   │   ├── class-kctm-notification-hooks.php       # WooCommerce event hooks
│   │   ├── class-kctm-notification-log.php         # DB logging
│   │   ├── class-kctm-sms-api.php                  # Africa's Talking client
│   │   ├── class-kctm-whatsapp-api.php             # Meta WhatsApp API client
│   │   └── class-kctm-whatsapp-notifications.php   # Legacy handler (guarded)
│   ├── orders/
│   │   ├── class-kctm-order-statuses.php   # Custom order statuses
│   │   ├── class-kctm-order-meta.php       # Order creation hooks
│   │   └── ...
│   ├── consultations/
│   │   ├── class-kctm-consultation-booking.php  # Booking + payment hooks
│   │   ├── class-kctm-consultation-cron.php     # Reminder cron job
│   │   └── ...
│   ├── measurements/
│   │   ├── class-kctm-measurement-fields.php    # Field definitions
│   │   ├── class-kctm-measurement-storage.php   # Save/load measurements
│   │   └── ...
│   ├── personalization/
│   │   └── class-kctm-personalization-storage.php # Cart price adjustments
│   └── portal/
│       ├── class-kctm-portal.php            # SPA routing + auth
│       └── class-kctm-portal-ajax.php       # 21 AJAX endpoints
├── templates/
│   ├── portal/portal-app.php                # Full SPA template
│   ├── admin/                               # Admin page templates
│   └── personalization/                     # Suit configurator template
├── assets/
│   ├── css/
│   │   ├── kctm-admin-branding.css          # Admin brand colors
│   │   ├── kctm-wc-branding.css             # Frontend brand colors
│   │   ├── kctm-portal.css                  # Store Manager Portal styles
│   │   ├── kctm-frontend.css                # Measurement forms etc.
│   │   └── kctm-suit-configurator.css       # Suit configurator
│   ├── js/
│   │   ├── kctm-portal.js                   # Store Manager Portal SPA
│   │   ├── kctm-frontend-enhancements.js    # Newsletter, share, search
│   │   ├── kctm-suit-configurator.js        # Suit configurator
│   │   └── ...
│   └── images/                              # Suit shading/mask PNGs
└── languages/                               # Translation files
```

### Brand Colors
| Color | Hex | Usage |
|-------|-----|-------|
| Dark Brown | #402417 | Primary — sidebar, buttons, headers |
| Luxury Gold | #c9a96e | Accent — hover, active states, highlights |
| Light Cream | #fef9e7 | Background — table hover, subtle fills |
| Gold Hover | #b8944f | Hover state for gold elements |
| Brown Light | #5a3828 | Secondary brown |

### Payment Gateways
| Gateway | Status | Currencies |
|---------|--------|------------|
| PayPal (+ Apple Pay, Google Pay, Cards) | LIVE | USD, EUR, GBP, CAD, NGN |
| MTN MoMo | SANDBOX (awaiting Go Live) | XAF only |

### Third-Party Accounts
| Service | Account | Status |
|---------|---------|--------|
| MTN MoMo Developer | momodeveloper.mtn.com | Go Live pending |
| Africa's Talking | bacha / kevincho app | Account not verified (401) |
| PayPal Business | pv2acha@gmail.com | Connected + Live |
| Meta Business (WhatsApp) | Not yet created | Pending setup |

---

## FUTURE IDEAS / BACKLOG
- Women's product line (categories ready, need products)
- Kids' product line (categories ready, need products)
- Suit configurator v2 (better visual quality)
- Loyalty/rewards program
- Appointment scheduling (beyond consultations)
- Multi-language (French) frontend
- Mobile app or PWA
