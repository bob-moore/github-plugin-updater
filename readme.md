# Github WP Plugin Updater



## Installation

Install the [composer package]:

    composer require bmd/github-wp-updater

## Example

```php
    new Bmd\GithubWpUpdater\Main( __FILE__, [
        'github.user' => 'bob-moore',
        'github.repo' => 'simple-menu-block',
        'github.branch' => 'main',
        'plugin.banners' => [
            'low' => trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/banner-772x250.jpg',
            'high' => trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/banner-1544x500.jpg',
        ],
        'plugin.icons' => [
            'default' => trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/icon.png',
        ]
    ] );
```