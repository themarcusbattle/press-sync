# Press Sync by Marcus Battle

An easy and straightforward way to syncrchonize users, posts and more between multiple WordPress sites. Activate the plugin on both sites that you want to sync content with and you immediately will have a connection to push content to.

Uses the WP-API. Custom Post Type Support. Currently supports push only. Alpha Version. Use at own risk.

For support, email marcus@marcusbattle.com

## How to Install/Use

1) Install the plugin on both servers you want to synchronize
2) On your target server, create a "PressSync Key" to allow the WordPress site to receive data
3) On your push server, create a connection to the target server using the "PressSync Key"
4) Select the type of content you want to push, click "Save"
5) Press "Sync" to synchronize the data. Done!

## Supports syncing the following data:
- WP Users, WP Posts, WP Media and WP Comments
- Custom Post Types
- Featured Images
- Categories, Tags and Custom Taxonomies
- Post & User Meta
- Posts 2 Posts Relationships

## Changelog.

- v0.1.0 - The initial commit and development of Press Sync.
- v0.2.0 - Addition of CLI support.
- v0.3.0 - Added WP Options migrations to the plugin.
- v0.4.0 - Replaced CMB2 Support with native WP Options
- v0.4.1 - Fixed the WP Coding Standards / PHPCS errors
- v0.5.0 - Overhauled the dashboard for better UX