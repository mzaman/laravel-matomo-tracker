
# LaravelMatomoTracker

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]

## About

The `mzaman\laravel-matomo-tracker` Laravel package is a wrapper for the `piwik\piwik-php-tracker`. The Piwik PHP tracker allows server-side tracking with Matomo.

## Compatibility

This package is compatible with Laravel 9.x and later. Ensure that your Laravel version meets the required version before proceeding with the installation.

## Installation

### Via Composer

Require the `mzaman/laravel-matomo-tracker` package in your `composer.json` and update your dependencies:

```bash
composer require mzaman/laravel-matomo-tracker
```

### Publish the Config File (Optional)

Run `php artisan vendor:publish` to publish the config file if needed.

```bash
php artisan vendor:publish
```

### Update Your `.env`

Add the following variables to your `.env` file and configure them to fit your environment:

```bash
MATOMO_URL="https://your.matomo-install.com"  # The URL of your Matomo server
MATOMO_SITE_ID=1  # Your Matomo Site ID
MATOMO_AUTH_TOKEN="00112233445566778899aabbccddeeff"  # Your Matomo Authentication Token
MATOMO_QUEUE="matomotracker"  # The name of the queue to use for tracking (default is 'matomotracker')
MATOMO_QUEUE_CONNECTION="default"  # The queue connection to use (default is 'default')
```

## Usage

You can use the facade to track.

``` bash
LaravelMatomoTracker::doTrackPageView('Page Title')
```

For tracking other events, such as downloads or outlinks, you can use:

```php
LaravelMatomoTracker::doTrackDownload($actionUrl);
LaravelMatomoTracker::doTrackOutlink($actionUrl);
```

### Queue Setup

To enable queued tracking, you need to configure your queue settings in `config/queue.php` for the specified `MATOMO_QUEUE_CONNECTION`.

For example, to use the `database` queue driver, your `.env` file should look like this:

```bash
QUEUE_CONNECTION=database
```

Make sure to run the necessary migrations to set up your database queue:

```bash
php artisan queue:table
php artisan migrate
```

### Queue Functions

For queuing, you can use these functions:

```php
LaravelMatomoTracker::queuePageView(string $documentTitle);
LaravelMatomoTracker::queueEvent(string $category, string $action, $name = false, $value = false);
LaravelMatomoTracker::queueContentImpression(string $contentName, string $contentPiece = 'Unknown', $contentTarget = false);
LaravelMatomoTracker::queueContentInteraction(string $interaction, string $contentName, string $contentPiece = 'Unknown', $contentTarget = false);
LaravelMatomoTracker::queueSiteSearch(string $keyword, string $category = '',  $countResults = false);
LaravelMatomoTracker::queueGoal($idGoal, $revenue = 0.0);
LaravelMatomoTracker::queueDownload(string $actionUrl);
LaravelMatomoTracker::queueOutlink(string $actionUrl);
LaravelMatomoTracker::queueEcommerceCartUpdate(float $grandTotal);
LaravelMatomoTracker::queueEcommerceOrder(float $orderId, float $grandTotal, float $subTotal = 0.0, float $tax = 0.0, float $shipping = 0.0,  float $discount = 0.0);
LaravelMatomoTracker::queueBulkTrack();
```

### Troubleshooting

If you're experiencing issues with the package, ensure that:

1. You have correctly set up the `.env` variables for `MATOMO_URL`, `MATOMO_SITE_ID`, and `MATOMO_AUTH_TOKEN`.
2. Your queue system is configured correctly. Check the Laravel queue documentation for more details: [Laravel Queue Docs](https://laravel.com/docs/9.x/queues).
3. You are using the correct version of `matomo/matomo-php-tracker` that is compatible with your Matomo installation.
4. Check the `storage/logs/laravel.log` file for any related errors or issues.

## Tracking

### Basic Functionality

You can also use the following methods to simplify tracking actions:

```php
// Instead of using 
LaravelMatomoTracker::doTrackAction($actionUrl, 'download'); // or
LaravelMatomoTracker::doTrackAction($actionUrl, 'link');

// You can use these simplified methods:
LaravelMatomoTracker::doTrackDownload($actionUrl);
LaravelMatomoTracker::doTrackOutlink($actionUrl);
```

### Advanced Tracking

Here are additional methods for tracking specific events and actions:

```php
LaravelMatomoTracker::setCustomDimension(int $id, string $value);
LaravelMatomoTracker::setCustomDimensions([]); // bulk insert of custom dimensions
LaravelMatomoTracker::setCustomVariables([]);  // bulk insert of custom variables
```

For more advanced Matomo tracking capabilities, refer to the [Matomo PHP Tracker API Documentation](https://developer.matomo.org/api-reference/PHP-Piwik-Tracker).

## Change Log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Contributing

Please see [contributing.md](contributing.md) for details and a to-do list.

## Security

If you discover any security-related issues, please email masud.zmn@gmail.com instead of using the issue tracker.

## Credits

- [All Contributors][link-contributors]

## License

BSD-3-Clause. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/mzaman/laravel-matomo-tracker.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/mzaman/laravel-matomo-tracker.svg?style=flat-square
[link-packagist]: https://packagist.org/packages/mzaman/laravel-matomo-tracker
[link-downloads]: https://packagist.org/packages/mzaman/laravel-matomo-tracker
[link-author]: https://github.com/mzaman
[link-contributors]: ../../contributors
