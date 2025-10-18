# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin called "MHard Photobook for WordPress" - a professional photobook configurator that allows administrators to manage product option groups and options, create client invitations with unique links, collect client submissions, and send email confirmations. The plugin includes CSV import/export functionality for bulk operations.

**Main Plugin File:** `configurator-links.php`
**Text Domain:** `configurator-links`
**Database Prefix:** `configurator_` (with WP table prefix)

## Database Architecture

The plugin creates 4 custom database tables during activation:

1. **`{prefix}_configurator_groups`** - Option groups with single/multi selection types
2. **`{prefix}_configurator_options`** - Individual options belonging to groups (with optional images)
3. **`{prefix}_configurator_clients`** - Client records with unique tokens for access
4. **`{prefix}_configurator_submissions`** - Stores client form submissions with selections (JSON format)

Tables are created in `CL_Activator::activate()` (includes/class-activator.php:6) and conditionally dropped during uninstall based on the `remove_data_on_uninstall` setting.

## Core Class Structure

All classes use static methods and follow the pattern `CL_ClassName`:

- **CL_Activator** (class-activator.php) - Plugin activation/deactivation, database setup
- **CL_Groups** (class-groups.php) - CRUD for option groups, admin UI
- **CL_Options** (class-options.php) - CRUD for options, admin UI  
- **CL_Clients** (class-clients.php) - Client management, token generation, invite functionality
- **CL_Submissions** (class-submissions.php) - Store and retrieve client submissions
- **CL_Emails** (class-emails.php) - Email template system with placeholder replacement
- **CL_Shortcode** (class-shortcode.php) - Public form rendering via `[configurator_form]`
- **CL_Importer** (class-importer.php) - CSV import wizard with dry-run and field mapping
- **CL_Exporter** (class-exporter.php) - CSV export for groups and options
- **CL_REST** (class-rest.php) - REST API endpoints (if needed for AJAX)
- **CL_Admin_Menu** (class-admin-menu.php) - WordPress admin menu registration
- **CL_Helpers** (helpers.php) - Shared utilities (CSV, email parsing, HTML table generation, token generation)

Each class has an `init()` method called during `plugins_loaded` hook.

## Key Concepts

### Token-based Client Access
- Each client gets a unique token (32-char hex string)
- Public form access: `{public_page_url}?t={token}`
- Token uniqueness is enforced at database level
- Helper: `CL_Helpers::generate_token()` and `CL_Clients::ensure_token()`

### Email Template System
Email templates support these placeholders:
- `{client_name}` - Client's name
- `{unique_link}` - Full URL with token
- `{selections_table}` - HTML table of selections with images

Templates are stored in `cl_settings` option and processed by `CL_Emails` class.

### CSV Import/Export
- **Import:** Wizard interface with file upload, dry-run preview, automatic grouping of data
  - Combined format: Import groups and their options in one CSV file
  - Dry-run mode shows preview before actual import
  - Automatic validation with error/warning feedback
  - Groups are automatically created and options linked based on `group_name`
- **Export:** Direct download of groups or options as CSV (to be implemented)
- CSV utilities in `CL_Helpers::csv_read_uploaded_file()` and `CL_Helpers::csv_download()`
- Example file: `assets/examples/combined.csv`

**Combined CSV Format:**
Required columns:
- `group_name` - Name of the group (options with same name are grouped together)
- `group_type` - Type: "single" or "multi"
- `option_name` - Name of the option

Optional columns:
- `collection` - Collection name to organize groups (e.g., "DreambooksPRO", "Bold Collection 150")
- `group_description`, `group_sort_order`, `group_active`
- `option_description`, `option_image_url`, `option_sort_order`, `option_active`

Each row contains both group and option data. Rows with the same `group_name` are automatically grouped, with the group created only once. The `collection` field allows organizing groups into logical categories (e.g., different product lines or album types).

### Group Selection Types
- `single` - Radio button selection (one option only)
- `multi` - Checkbox selection (multiple options allowed)

Stored in `type` column of groups table and used to render appropriate form controls.

## WordPress Integration Points

### Admin Pages
All admin pages require `manage_options` capability:
- Main menu: "MHard Photobook"
- Subpages: Groups, Options, Clients, Submissions, Import, Export, Settings

### Shortcode
`[configurator_form]` - Renders the public client form
- Requires `?t={token}` query parameter
- Validates token and loads client record
- Displays all active groups and their active options
- Handles form submission via POST

### Settings
Plugin settings stored in `cl_settings` WordPress option:
- Sender name/email for outgoing emails
- Admin recipient emails (comma-separated)
- Public page ID (where shortcode is placed)
- Email templates (invite & submission)
- Uninstall data removal preference

Access via `CL_Helpers::get_settings()` and `CL_Helpers::update_settings()`.

## Development Workflow

### Testing the Plugin Locally
This plugin requires a WordPress environment. You'll need:
1. Local WordPress installation (e.g., Local by Flywheel, MAMP, Docker)
2. Symlink or copy plugin folder to `wp-content/plugins/configurator-links`
3. Activate via WordPress admin or WP-CLI: `wp plugin activate configurator-links`

### Database Changes
After modifying table schemas in `CL_Activator::activate()`:
- Deactivate and reactivate the plugin to trigger `dbDelta()`
- Or manually drop tables and reactivate (data will be lost)
- Tables use `dbDelta()` which is idempotent but particular about SQL formatting

### Adding New Fields
When adding database fields:
1. Update schema in `CL_Activator::activate()` 
2. Update corresponding CRUD methods (create, update) in the relevant class
3. Update admin form rendering
4. Update CSV import/export mappings if applicable
5. Handle sanitization and escaping appropriately

### Email Template Placeholders
To add new placeholders:
1. Define placeholder pattern (e.g., `{new_field}`)
2. Update `CL_Emails` replacement logic
3. Document in settings page help text

### Security Considerations
- All admin forms use WordPress nonces (`wp_nonce_field` / `wp_verify_nonce`)
- Capability checks on all admin pages: `current_user_can('manage_options')`
- Public form validates token existence and client active status
- Data sanitized on input (`sanitize_text_field`, `sanitize_email`, `absint`, etc.)
- Output escaped appropriately (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`)

## File Organization

```
configurator-links/
├── configurator-links.php     # Main plugin file, bootstrap, constants
├── uninstall.php              # Uninstall handler (conditional data removal)
├── readme.txt                 # WordPress plugin readme
├── includes/                  # All PHP classes
│   ├── helpers.php            # Static utility functions
│   ├── class-activator.php    # Activation/deactivation
│   ├── class-admin-menu.php   # Admin menu registration
│   ├── class-groups.php       # Groups CRUD + admin UI
│   ├── class-options.php      # Options CRUD + admin UI
│   ├── class-clients.php      # Clients CRUD + admin UI + invite
│   ├── class-submissions.php  # Submissions CRUD + admin UI
│   ├── class-emails.php       # Email sending with templates
│   ├── class-shortcode.php    # Public form shortcode
│   ├── class-importer.php     # CSV import wizard
│   ├── class-exporter.php     # CSV export
│   └── class-rest.php         # REST API (optional/future)
├── assets/                    # CSS, JS, examples
│   ├── admin.css              # Admin styling
│   ├── admin.js               # Admin JS (media uploader, etc.)
│   ├── public.css             # Public form styling
│   └── examples/
│       ├── groups.csv         # Example groups CSV format
│       └── options.csv        # Example options CSV format
└── languages/                 # i18n translation files (if any)
```

## Deployment and Auto-Updates

This plugin uses GitHub-based automatic updates via the [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) library.

### Initial Setup

1. **Install Composer dependencies:**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

2. **Push to GitHub:**
   - Repository: https://github.com/MarcianoH/mhard-photobook-wp
   - Already configured in `configurator-links.php`
   - Commit and push all code

3. **For private repositories (optional):**
   - Generate a GitHub Personal Access Token (Settings → Developer settings → Personal access tokens)
   - Uncomment the `setAuthentication()` line in `configurator-links.php`
   - Add your token (use environment variable or WordPress option for security)

### Creating Updates

When you want to release an update:

1. **Update version number** in two places:
   - Plugin header in `configurator-links.php` (line 5: `Version: x.x.x`)
   - Constant `CL_PLUGIN_VERSION` in `configurator-links.php` (line 16)

2. **Commit changes:**
   ```bash
   git add .
   git commit -m "Release version x.x.x"
   git push origin main
   ```

3. **Create a GitHub release:**
   ```bash
   git tag -a v1.0.1 -m "Version 1.0.1 - Description of changes"
   git push origin v1.0.1
   ```
   - Or use GitHub's web interface: Releases → Create a new release
   - Tag version: `v1.0.1` (must start with 'v')
   - Release title: `Version 1.0.1`
   - Description: Changelog/release notes

### How Updates Work

- WordPress checks for updates every 12 hours automatically
- The update checker queries your GitHub repository for new releases
- If a newer version is found, it appears in the WordPress admin (Plugins page)
- Users can click "Update now" for one-click installation
- The plugin tracks the `main` branch by default (configurable in code)

### Production Deployment

**Option A: With Composer (Recommended)**
1. Run `composer install --no-dev` on production
2. Upload entire plugin folder including `vendor/` directory
3. Activate plugin

**Option B: Pre-build package**
1. Run `composer install --no-dev` locally
2. Create a ZIP of the entire plugin folder (including `vendor/`)
3. Upload ZIP via WordPress admin: Plugins → Add New → Upload Plugin
4. Activate plugin

### Version Control Best Practices

- Never commit `vendor/` to git (already in `.gitignore`)
- Always run `composer install` in production
- Tag releases semantically: `v1.0.0`, `v1.1.0`, `v2.0.0`
- Keep changelog in release notes
- Test updates on staging before production release

### Updating Production Sites

Once configured, production sites will:
1. Automatically detect new releases from GitHub
2. Show update notification in WordPress admin
3. Allow one-click updates (like official WordPress.org plugins)
4. Download and extract the release automatically

No manual file uploads needed after initial setup!

## Common Pitfalls

### dbDelta Quirks
WordPress's `dbDelta()` is sensitive to SQL formatting:
- Exactly two spaces between `PRIMARY KEY` and column name
- One space after comma in column definitions
- Must use KEY not INDEX for indexes
- Always test schema changes by deactivating/reactivating

### Token Uniqueness
`CL_Clients::ensure_token()` will retry up to 10 times if token collision occurs. This is unlikely with 32-char hex but handled defensively.

### CSV Import Field Mapping
The import wizard requires exact column name matching. The combined CSV format uses prefixed columns (`group_*` and `option_*`) to distinguish between group and option fields. When changing database fields, update:
1. Column name validation in `CL_Importer::parse_csv()`
2. The example CSV file at `assets/examples/combined.csv`
3. Documentation in the upload step UI

### Image Storage
Options can store both `image_id` (WordPress attachment ID) and `image_url` (direct URL). The admin interface uses WordPress Media Library (`wp_enqueue_media()`), but CSV import only supports `image_url`.
