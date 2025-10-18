=== MHard Photobook for WordPress ===
Contributors: mhard
Tags: photobook, configurator, options, clients, csv, email
Requires at least: 5.8
Tested up to: 6.6
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional photobook configurator for managing product options, client invitations, and order submissions. Includes CSV import/export functionality.

== Description ==
MHard Photobook for WordPress lets admins:
- Manage option groups (single/multi) and options (with images, sorting, active).
- Create clients and send unique token links.
- Collect submissions from the public form and email confirmations to client and admin.
- Import/Export via CSV (wizard with dry-run and mapping).

Shortcode: [configurator_form]

== Installation ==
1. Upload the `configurator-links` folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Go to MHard Photobook > Settings to set sender email, templates, and the public page.
4. Add the shortcode [configurator_form] to the selected page.

== CSV Formats ==
Example files are in `assets/examples/`.

Groups CSV (UTF-8, comma):
name,description,type,sort_order,active
Papier,Keuze papiergewicht,single,10,1
Omslag,Hardcover of softcover,single,20,1
Extra's,Extra opties,multi,30,1

Options CSV (UTF-8, comma):
group_name,name,description,image_url,sort_order,active
Papier,90g,Standaard,https://example.com/90g.jpg,10,1
Papier,120g,Zwaarder papier,https://example.com/120g.jpg,20,1
Omslag,Hardcover,Sterke omslag,https://example.com/hard.jpg,10,1
Omslag,Softcover,Lichte omslag,https://example.com/soft.jpg,20,1
Extra's,Matte laminaat,Afwerking,https://example.com/matte.jpg,10,1

== Shortcode ==
Use `[configurator_form]`. The public link should include a `?t=<token>` query parameter generated per client in Admin > Clients.

== Emails ==
Templates support placeholders:
- {client_name}
- {unique_link}
- {selections_table}

== Capabilities & Security ==
- Admin pages require `manage_options`.
- Nonces used on forms.
- Data sanitized and escaped.

== Uninstall ==
If enabled in settings, all plugin tables and options are removed on uninstall.

== Changelog ==
= 1.0.0 =
- Initial release.
