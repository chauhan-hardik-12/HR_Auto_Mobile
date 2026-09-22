# HR Auto Mobile

HR Auto Mobile is a PHP/MySQL vehicle service booking platform for cars and bikes. Customers can browse the vehicle catalog, view model-specific service packages, register or sign in, schedule a service, and track bookings. Administrators manage bookings, the vehicle catalog, service pricing, customer accounts, contact inquiries, and site content from an AdminLTE-based dashboard.

## Contents

- [Features](#features)
- [Application areas](#application-areas)
- [Booking workflow](#booking-workflow)
- [Technology stack](#technology-stack)
- [Requirements](#requirements)
- [Local setup with XAMPP](#local-setup-with-xampp)
- [Configuration](#configuration)
- [Project structure](#project-structure)
- [API reference](#api-reference)
- [Database model](#database-model)
- [Administration](#administration)
- [Security and operational notes](#security-and-operational-notes)
- [Troubleshooting](#troubleshooting)

## Features

### Customer website

- Responsive home page with configurable hero content, services, partners, testimonials, contact details, and statistics.
- Vehicle catalog browsing by vehicle type, brand, and model.
- Model-specific service packages with duration, price, and recommended-package flags.
- Customer registration and login using phone number or email.
- Authenticated booking form with customer details, preferred date/time, address, and notes.
- Customer dashboard showing booking history and booking/payment status.
- Contact form with server-side validation and CSRF protection.

### Administration

- Dashboard KPIs for bookings, revenue, registered customers, catalog records, and recent inquiries.
- Booking management and status filtering.
- Vehicle type, brand, and model management.
- Service package management and model-specific pricing.
- Customer user directory.
- Contact inquiry management.
- Site settings, partners, testimonials, and (for super administrators) admin account management.

## Application areas

| Area | Entry points | Purpose |
| --- | --- | --- |
| Public site | `index.php`, `about.php`, `services.php`, `brands.php`, `contact.php` | Marketing pages and catalog discovery |
| Customer authentication | `login.php`, `register.php`, `logout.php` | Customer account lifecycle |
| Customer account | `dashboard.php` | View profile information and bookings |
| Booking | `checkout.php` | Confirm and create a service booking |
| JSON APIs | `assets/api/` | Authentication checks and catalog loading |
| Admin panel | `admin/login.php`, `admin/index.php` | Protected operations and content management |

## Booking workflow

1. Open `services.php`.
2. Select a vehicle type.
3. Select a brand and then a model. Each selection loads the next list from `assets/api/catalog-api.php`.
4. Review the service packages available for the selected model.
5. Select a package. If the visitor is not authenticated, the page offers sign-in or registration.
6. Continue to `checkout.php` with the selected model, service, and amount.
7. Submit the booking. New bookings are stored with `status = 'pending'` and `payment_status = 'pending'`.
8. The customer is redirected to `dashboard.php`, where the booking appears in the booking history.
9. An administrator reviews and updates the booking from `admin/bookings.php`.

The current implementation records the booking and payment status; it does not integrate an online payment gateway.

## Technology stack

- PHP with server-rendered pages
- MySQL accessed through PDO
- HTML, CSS, and vanilla JavaScript
- Bootstrap/AdminLTE assets for the admin panel
- jQuery, DataTables, Chart.js, SweetAlert2, Toastr, and PDFMake bundled under `admin/plugins/`
- Boxicons loaded from the unpkg CDN on public pages
- Apache `.htaccess` rules for directory-listing and sensitive-file protection

## Requirements

- Apache with PHP support (XAMPP is the recommended local environment)
- PHP with PDO MySQL enabled
- MySQL or MariaDB
- A modern browser with JavaScript enabled

No Composer manifest, Node package manifest, or database dump is included in this repository. The application expects an existing MySQL database whose tables match the queries described in [Database model](#database-model).

## Local setup with XAMPP

1. Install and start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Place or clone the project under the Apache document root:

   ```text
   C:\xampp\htdocs\HR_Auto_Mobile
   ```

3. Create a MySQL database named `auto-mobile`.
4. Create and seed the tables listed in [Database model](#database-model). Add at least:
   - one active vehicle type;
   - one active brand belonging to that vehicle type;
   - one active model belonging to that brand;
   - one active service;
   - one active `model_services` row with a price;
   - one active admin account with a password generated using PHP `password_hash()`.
5. Confirm the connection values in `assets/config/db.php`:

   ```php
   $host = "localhost";
   $database = "auto-mobile";
   $username = "root";
   $password = "";
   ```

   Replace these values for non-default or production credentials. Do not commit production credentials.
6. Open the public site:

   ```text
   http://localhost/HR_Auto_Mobile/
   ```

7. Open the admin login:

   ```text
   http://localhost/HR_Auto_Mobile/admin/login.php
   ```

The authentication guard currently uses `/Hr_Auto_Mobile/` in its fallback redirect path. If the project is deployed under a different Apache URL path, update the redirect base path in `includes/auth.php` and `admin/includes/auth.php`.

## Configuration

### Database

The shared PDO connection is defined in `assets/config/db.php` and is reused by the public site, API endpoints, and admin panel. It enables exception mode and associative-array fetches.

### Site content

`includes/settings_loader.php` loads key/value records from `site_settings`. The helper `siteSetting()` escapes values for HTML output, while `siteSettingRaw()` returns an unescaped value for cases that require raw content.

Common settings used by the public pages include:

- `site_name`
- `site_tagline`
- `hero_title`
- `hero_btn_text`
- `hero_btn_link`
- `contact_phone`
- `contact_email`
- `contact_address`
- `business_hours`

These values can be managed from `admin/settings.php`.

### Static assets

- Public styles and scripts: `assets/css/` and `assets/js/`
- Public images: `assets/images/`
- Admin styles/scripts and vendor libraries: `admin/dist/` and `admin/plugins/`

## Project structure

```text
.
├── index.php                 # Public home page
├── about.php                 # About page
├── services.php              # Vehicle and service package selection
├── brands.php                # Public brand catalog
├── checkout.php              # Authenticated booking form
├── dashboard.php             # Customer booking dashboard
├── contact.php               # Contact form and contact details
├── login.php                 # Customer/admin login UI
├── register.php              # Customer registration UI
├── logout.php                # Customer logout
├── assets/
│   ├── api/                  # JSON authentication and catalog endpoints
│   ├── config/db.php         # PDO database connection
│   ├── css/style.css         # Public styles
│   ├── images/               # Public images and vehicle logos
│   └── js/                   # Public browser behavior
├── includes/
│   ├── auth.php              # Customer session guard
│   ├── header.php/footer.php # Shared public layout
│   └── settings_loader.php   # Site settings and output helpers
└── admin/
    ├── login.php             # Admin login
    ├── index.php             # Admin dashboard
    ├── bookings.php          # Booking operations
    ├── vehicle_types.php     # Vehicle type management
    ├── brands.php            # Brand management
    ├── models.php            # Model management
    ├── services.php          # Service package management
    ├── model_pricing.php     # Model-specific service pricing
    ├── users.php             # Customer management
    ├── messages.php          # Contact inquiries
    ├── settings.php          # Site settings
    ├── partners.php          # Partners/team content
    ├── testimonials.php      # Testimonials
    └── includes/              # Admin guard, layout, and shared helpers
```

## API reference

All endpoints return JSON with a `success` boolean. Catalog failures also include a `data` array.

### Authentication check

```text
GET /assets/api/auth-check.php
```

Example response:

```json
{
  "success": true,
  "logged_in": false,
  "user_id": null
}
```

### Authentication handler

```text
POST /assets/api/auth-handler.php?action=login
POST /assets/api/auth-handler.php?action=register
```

The endpoint accepts JSON request bodies or form data.

Login fields:

```json
{
  "identifier": "customer@example.com",
  "password": "your-password"
}
```

Registration fields:

```json
{
  "name": "Customer Name",
  "phone": "9876543210",
  "email": "customer@example.com",
  "password": "your-password"
}
```

Successful login creates either a customer session or an admin session and returns a redirect target.

### Catalog API

```text
GET /assets/api/catalog-api.php?action=types
GET /assets/api/catalog-api.php?action=brands&vehicle_type_id={id}
GET /assets/api/catalog-api.php?action=models&brand_id={id}
GET /assets/api/catalog-api.php?action=services&model_id={id}
```

The dependency order is intentional: types are loaded first, brands depend on a vehicle type, models depend on a brand, and services depend on a model.

Example service response item:

```json
{
  "model_service_id": 12,
  "model_id": 4,
  "service_id": 3,
  "service_name": "Full Service",
  "description": "Complete vehicle inspection and maintenance",
  "duration": "2 hours",
  "price": "2500.00",
  "is_recommended": 1,
  "status": 1
}
```

## Database model

The code expects these tables and relationships:

| Table | Role | Important relationships/columns |
| --- | --- | --- |
| `users` | Customer accounts | `id`, `name`, `phone`, `email`, `password`, `created_at` |
| `admins` | Admin accounts | `id`, `username`, `email`, `password`, `name`, `role`, `status` |
| `vehicle_types` | Top-level vehicle categories | `id`, `name`, `icon`, `status` |
| `brands` | Brands under a vehicle type | `id`, `vehicle_type_id`, `name`, `status` |
| `models` | Models under a brand | `id`, `brand_id`, `name`, `status` |
| `services` | Reusable service packages | `id`, `name`, `description`, `duration`, `status` |
| `model_services` | Model-specific price/package mapping | `id`, `model_id`, `service_id`, `duration`, `price`, `is_recommended`, `status` |
| `bookings` | Customer service appointments | `user_id`, `model_id`, `service_id`, customer contact fields, date/time, address, amount, status, payment status, notes, timestamps |
| `contact_messages` | Public contact submissions | `name`, `email`, `phone`, `message`, `status`, `created_at` |
| `site_settings` | Site-wide key/value configuration | `setting_key`, `setting_value` |
| `partners` | Public partner/team cards | `name`, `role`, `image`, `status`, `display_order` |
| `testimonials` | Public customer testimonials | customer/vehicle fields, `rating`, `comment`, `status` |

The main catalog relationship is:

```text
vehicle_types 1 ── * brands 1 ── * models 1 ── * model_services * ── 1 services
```

Bookings reference `users`, `models`, and `services`. The customer dashboard resolves the vehicle type and brand by traversing `bookings → models → brands → vehicle_types`.

Recommended booking status values used by the application are `pending`, `confirmed`, `completed`, and `cancelled`. Payment status values include `pending` and `paid`.

## Administration

Admin pages are protected by `admin/includes/auth.php`. The session stores the admin ID, username, name, email, and role. The `superadmin` role additionally exposes the Admin Accounts screen.

Typical operational sequence:

1. Add or activate vehicle types.
2. Add brands under each vehicle type.
3. Add models under each brand.
4. Add reusable service packages.
5. Use Model Pricing to associate services with models and set prices.
6. Review incoming bookings and update their status.
7. Maintain public settings, partners, and testimonials.
8. Review contact inquiries and mark them read as they are handled.

## Security and operational notes

- Customer and admin passwords are verified with `password_verify()` and created with `password_hash()`.
- Database operations use PDO prepared statements for user-supplied values.
- Customer and admin protected pages use session guards and no-cache headers.
- The contact form uses a session CSRF token, email validation, and a 10-digit phone validation rule.
- `.htaccess` disables directory listings and denies direct access to files with sensitive extensions.
- Set a non-empty database password and use HTTPS outside local development.
- Restrict database users to only the permissions required by the application.
- Replace the permissive `Access-Control-Allow-Origin: *` API policy with an explicit trusted origin before exposing the APIs publicly.
- Move database credentials out of source control for production deployments.

## Troubleshooting

### “Database connection failed”

Verify that MySQL is running, the `auto-mobile` database exists, and the values in `assets/config/db.php` match the local MySQL account.

### Vehicle lists are empty

Check that the relevant records exist and have `status = 1`. Brands must reference the selected vehicle type, models must reference the selected brand, and service packages must be mapped through `model_services`.

### Admin pages redirect back to login

Confirm that the admin account has `status = 1`, the password is a PHP password hash, and browser cookies are enabled. If the project is not hosted at `/Hr_Auto_Mobile`, update the base path in the authentication guards.

### A booking does not appear in the dashboard

Confirm that the booking was inserted with the authenticated customer’s `user_id` and that the database query can join the referenced model, brand, vehicle type, and service records.

## Manual verification checklist

After configuring a local environment, verify:

- The home page and all public navigation pages load.
- Customer registration creates an account and starts a session.
- Customer login works with both phone and email identifiers.
- Vehicle type, brand, model, and service package lists load in order.
- Unauthenticated visitors are prompted to sign in before booking.
- A booking is created with pending booking and payment statuses.
- The customer dashboard displays the new booking.
- Admin login and role-based admin navigation work.
- Admin changes to catalog data and site settings appear on the public site.
- Contact form validation rejects invalid input and valid submissions appear in Contact Inquiries.
