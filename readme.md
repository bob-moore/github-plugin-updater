# GitHub WP Updater

Composer package for wiring GitHub-hosted WordPress plugin updates into the native WordPress update UI.

This package is built for plugins. You can initialize it from a plugin or from theme/framework code, but the `root_file` must always point at the target plugin's main file.

## What It Does

- Checks a GitHub repository for a newer plugin version.
- Reads plugin metadata from the remote main plugin file.
- Loads release download URLs from GitHub releases.
- Injects update information into WordPress' normal plugin update flow.
- Loads plugin information sections from a remote `readme.md` file.

## Installation

```bash
composer require bmd/github-wp-updater
```

## Quick Start

Minimal plugin bootstrap:

```php
<?php

use Bmd\GithubWpUpdater\Main;

require_once __DIR__ . '/vendor/autoload.php';

$updater = new Main(
    __FILE__,
    [
        'github.user' => 'your-github-user-or-org',
        'github.repo' => 'your-plugin-repository',
        'github.branch' => 'main',
    ]
);

$updater->mount();
```

`mount()` is required. Constructing `Main` only prepares config and services. It does not register the update hooks by itself.

## Using It In A Plugin

The normal plugin setup is to instantiate the updater from the plugin's main file and pass `__FILE__` as the root file.

```php
<?php
/**
 * Plugin Name: Example Plugin
 * Plugin URI:  https://github.com/acme/example-plugin
 * Version:     1.2.3
 * Requires at least: 6.5
 * Requires PHP: 8.2
 * Tested up to: 6.6
 */

use Bmd\GithubWpUpdater\Main;

require_once __DIR__ . '/vendor/autoload.php';

( new Main(
    __FILE__,
    [
        'github.user' => 'acme',
        'github.repo' => 'example-plugin',
        'github.branch' => 'main',
        'plugin.banners' => [
            'low' => trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/banner-772x250.jpg',
            'high' => trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/banner-1544x500.jpg',
        ],
        'plugin.icons' => [
            'default' => trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/icon-256x256.jpg',
        ],
    ]
) )->mount();
```

`plugin.version` is auto-discovered from the target plugin's main file header.
You do not need to set it in constructor config.

## Using It From A Theme Or Shared Framework

If your theme, starter kit, framework, or build system is responsible for bootstrapping plugins, that is fine. The important constraint is that the updater still needs the plugin's real main file.

Use this pattern when the bootstrap code is not inside the plugin itself:

```php
<?php

use Bmd\GithubWpUpdater\Main;

require_once get_theme_file_path( 'vendor/autoload.php' );

$plugin_root_file = WP_PLUGIN_DIR . '/example-plugin/example-plugin.php';

( new Main(
    $plugin_root_file,
    [
        'github.user' => 'acme',
        'github.repo' => 'example-plugin',
        'github.branch' => 'main',
    ]
) )->mount();
```

Important:

- Do not pass the theme file path unless the target being updated is actually that file.
- This package updates plugins, not themes.
- If you initialize it outside the plugin, use the plugin main file as `root_file`.

## Required Repository Conventions

For the updater to work reliably, your GitHub repository should follow these conventions:

### 1. Main Plugin File In Repo Root

The package fetches the remote plugin file using the local plugin file name. If your installed plugin root file is `my-plugin.php`, the repository should expose that same file on the configured branch.

### 2. Version Header In Plugin File

The remote plugin file should contain standard WordPress headers near the top, including:

- `Plugin URI`
- `Version`
- `Requires at least`
- `Tested up to`
- `Requires PHP`

### 3. GitHub Release Tag Matches Plugin Version

When the remote plugin file reports version `1.2.3`, this package requests the GitHub release tagged `1.2.3`.

### 4. Release Contains A Zip Asset

The updater scans the release assets and uses the first asset whose name contains `zip`.

Practical recommendation:

- Attach a plugin zip to every release.
- Use predictable names like `example-plugin.zip` or `example-plugin-1.2.3.zip`.

### 5. Optional `readme.md` In Repo Root

The plugin info modal requests `readme.md` from the repository root and parses top-level `# Heading` sections into WordPress plugin info sections.

Use a structure like:

```md
# Description
...

# Installation
...

# Changelog
...
```

## Configuration Reference

Pass configuration as the second argument to `new Main( $root_file, $config )`.

### Required GitHub Keys

- `github.user`
  GitHub username or organization.
- `github.repo`
  Repository name.
- `github.branch`
  Branch used for reading the remote plugin file and `readme.md`. Defaults to `main`.

### Optional Presentation Keys

- `plugin.banners`
  Array of plugin banner URLs.

Example:

```php
'plugin.banners' => [
    'low' => 'https://example.com/path/to/banner-772x250.jpg',
    'high' => 'https://example.com/path/to/banner-1544x500.jpg',
]
```

- `plugin.icons`
  Array of plugin icon URLs.

Example:

```php
'plugin.icons' => [
    'default' => 'https://example.com/path/to/icon-256x256.jpg',
]
```

### Asset Precedence

Asset values are resolved in this order:

1. Bundled updater defaults from `src/assets/`
2. Plugin-side asset files discovered at runtime
3. Explicit constructor config values

If you set an asset key directly in constructor config, it is treated as the
highest-priority value.

### Automatically Derived Keys

These are normally inferred from the plugin root file and do not need to be set manually unless you are doing something unusual:

- `plugin.dir`
- `plugin.url`
- `plugin.package`
- `plugin.file`
- `plugin.slug`
- `plugin.version` (from the plugin file `Version` header)

## Hooks And Extension Points

The package exposes one main filter namespace based on `plugin.package`.

### `{config.package}_config`

Filters the updater config before it is committed to the framework service
container. Use this to add or override presentation metadata such as icons,
banners, or any other config key.

Example:

```php
add_filter( 'example_plugin_config', function ( $config ) {
    $config['plugin.icons']['default'] = 'https://cdn.example.com/icon-256x256.jpg';
    $config['plugin.banners']['high'] = 'https://cdn.example.com/banner-1544x500.jpg';

    return $config;
}, 20 );
```

### `{package}_update_response`

Filters the update response before it is returned to WordPress.

Example:

```php
add_filter( 'example_plugin_update_response', function ( $response ) {
    $response['tested'] = '6.6';
    return $response;
} );
```

### `{package}_default_plugin_headers`

Filters the fallback plugin headers returned when the remote plugin file request fails.

Example:

```php
add_filter( 'example_plugin_default_plugin_headers', function ( $headers ) {
    $headers['version'] = '1.2.3';
    return $headers;
} );
```

## Advanced Service Access

If you need to access a registered service after mounting, you can resolve it through `Main::locateService()`.

```php
<?php

use Bmd\GithubWpUpdater\Main;
use Bmd\GithubWpUpdater\Services\RemoteRequest;

$service = Main::locateService( RemoteRequest::class );
```

Use fully qualified class names when possible.

## Behavior Notes

- Updates are only offered when the remote version is higher than the installed version.
- The remote `Requires at least` and `Requires PHP` headers are enforced before showing an update.
- Release metadata comes from GitHub releases, not tags alone.
- The package caches GitHub responses using WordPress object cache functions.

## Troubleshooting

### No update appears

Check all of the following:

- The remote plugin file version is higher than the installed plugin version.
- The release tag matches the plugin version exactly.
- The release has a zip asset attached.
- `github.user`, `github.repo`, and `github.branch` are correct.
- The plugin root file passed to `Main` is the real plugin main file.

### Plugin info modal is missing sections

Check:

- `readme.md` exists in the repository root.
- The file uses top-level `# Heading` sections.
- The branch configured in `github.branch` contains that file.

### I am mounting this from theme code and nothing works

The usual problem is the wrong `root_file`. Pass the plugin main file path, not the theme file path.

## LLM Implementation Checklist

If you are using this package from generated code, the safe default implementation checklist is:

1. Require Composer autoload.
2. Instantiate `Bmd\GithubWpUpdater\Main` with the plugin main file.
3. Provide `github.user`, `github.repo`, and optionally `github.branch`.
4. Call `->mount()` exactly once during bootstrap.
5. Ensure the GitHub repo has a matching release tag and a zip asset.
6. Keep the plugin headers in the main plugin file accurate.

Minimal LLM-safe template:

```php
<?php

use Bmd\GithubWpUpdater\Main;

require_once __DIR__ . '/vendor/autoload.php';

( new Main(
    __FILE__,
    [
        'github.user' => 'your-org',
        'github.repo' => 'your-plugin-repo',
        'github.branch' => 'main',
    ]
) )->mount();
```

## Development

Useful commands:

```bash
composer phpunit
composer phpstan
composer phpsniff
```