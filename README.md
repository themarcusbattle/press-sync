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

### v0.6.1

- Update README for updated Dashboard and installation.
- Reintroduce SPL autoloader for non-composer setups.

### v0.6.0

- Merge in major functional updates for Bulk Sync.

### v0.5.0

- Overhauled the dashboard for better UX

### v0.4.5

- Update lookup for post parent to be able to ignore `post_type`.

- v0.4.1 - Fixed the WP Coding Standards / PHPCS errors
- v0.4.0 - Replaced CMB2 Support with native WP Options
- v0.3.0 - Added WP Options migrations to the plugin.
- v0.2.0 - Addition of CLI support.
- v0.1.0 - The initial commit and development of Press Sync.

## Installation

### Using `composer`

If you have `composer` on your system, simply run `composer dump-autoload` to generate an autoloader classmap.

### Non-composer

The plugin still works without composer and will register an autoloader using `spl_autoload_register`.

## Usage

### WordPress Admin

Press Sync can be found in the WordPress admin under *Tools -> Press Sync*. There are two tabs for
configuring Press Sync - the _Sync_ tab and the _Settings_ tab.

#### Press Sync Dashboard

The _Credentials_ tab is where you'll conifgure your Press Sync installation to connect to another WordPress site.

- *Press Sync Key* - You should make this key unique and complex, and only share it with the other site that will be
  connecting to this site. It is *strongly recommended* that you connect to sites over SSL to avoid your key being
  transmitted in plaintext.
- *Remote Domain* - The remote domain of the site you are connecting to. This site should have Press Sync installed and
  configured.
- *Remote URL Arguments* - This is an *advanced setting* that you can use to supply additional arguments to the request
  URL. You should format this string as an HTTP GET query string, starting with a `?` (question mark).
  See https://en.wikipedia.org/wiki/Query_string for more details.
- *Remote Press Sync Key* - The Press Sync key configured in the _Settings_ tab of the *remote site*'s Press Sync
  configuration.

Once configured to connect to a remote Press Sync site, you can configure your sync job on the _Bulk Sync_ tab. Options
on that tab include.

- *Sync Method* - Determine whether you're _Pushing_ content to a remote site or _Pulling_ content from a remote site.
  Currently the only method available here is "Push".
- *Objects to Sync* - This list allows you to pick what type of content to Sync. WordPress built-ins like Post and Page
  are supported, as well as Custom Post Types.
- *WP Options to Sync* - If your _Objects to Sync_ is set to "Options", this field is used as a comma-separated *whitelist* of
  options to sync. Only the options specified in this field will be Pushed to the remote site.
- *Duplicate Action* - Choose what action Press Sync should take when a duplicate record is found on the receiving
  side. When *Sync* is the selected action, non-synced duplicates will receive a Press Sync meta key to allow them to
  be synced in the future.
- *Force Update* - By default, Press Sync only updates content that was modified more recently than it's synced
  counterpart. If this option is ste to "Yes", content will always be synced regardless of modified date.
- *Ignore Comments* - Whether or not Comments should be synced with posts.

### Command Line

Press Sync also includes the ability to sync content via the command line using [WP-CLI](http://wp-cli.org/). With
Press Sync enabled and _WP-CLI_ installed, you can see a list of basic commands:

```
$ wp press-sync
usage: wp press-sync media --remote_domain=<remote_domain> --remote_press_sync_key=<remote_press_sync_key> [--local_folder=<local_folder>]
   or: wp press-sync options --remote_domain=<remote_domain> --remote_press_sync_key=<remote_press_sync_key> [--options=<options>] [--local_folder=<local_folder>]
   or: wp press-sync pages --remote_domain=<remote_domain> --remote_press_sync_key=<remote_press_sync_key> [--local_folder=<local_folder>]
   or: wp press-sync posts --remote_domain=<remote_domain> --remote_press_sync_key=<remote_press_sync_key> [--local_folder=<local_folder>]
   or: wp press-sync users --remote_domain=<remote_domain> --remote_press_sync_key=<remote_press_sync_key> [--local_folder=<local_folder>]
```

Currently, Press Sync's CLI support includes Posts, Pages, Users, Options, and Media.

#### Common Arguments

All Press Sync CLI commands use the following required parameters:

- `--remote-domain` - The remote site you are connecting to.
- `--remote_press_sync_key` - The remote site's Press Sync Key, used to authenticate the connection.
- `--local_folder` - This option allows you to use JSON files instead of local WordPress data to push to the remote
  site. More on this below.

#### Command-Specific Arguments

Some commands take optional parameters.

- `wp press-sync options`
  - `--options` - A comma-separated list of option fields to sync.

#### Importing Local JSON

The `--local_folder` folder option allows you to specify a folder with JSON data to push to the remote site instead of
using the hosting WordPress site's data. This is useful for importing data from systems that aren't necessarily
WordPress, but that can export their data in an easy-to-use form.

Structurally, your JSON files should be laid out like this:

```
+--local_folder
|
| ./posts/
| ./posts/YYYY
| ./posts/YYYY/slugged-title.json
| ./attachments.json
| ./users.json
| ./options.json
```

For Posts (and post-like objects such as Pages or CPTs), the JSON files should be located in a folder called
`posts/YYYY/`, where `YYYY` is the four-digit year for the post.

All other types supported by CLI should be in the root of the folder specified in `--local_folder` as such:

- Media - `attachments.json`
- Users - `users.json`
- Options - `options.json`
