# Github WP Plugin Updater



## Installation

Install the [composer package]:

    composer require marked-effect/github-plugin-updater

## Example

```php
    new GHPluginUpdater\Main( __FILE__, [
        'github.user' => 'bob-moore',
        'github.repo' => 'simple-menu-block',
        'github.branch' => 'main',
        'config.banners' => [
            'low' => trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/banner-772x250.jpg',
            'high' => trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/banner-1544x500.jpg',
        ],
        'config.icons' => [
            'default' => trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/icon.png',
        ]
    ] );
```