# CanvasImporter
Command line tool to import another blog's posts into Laravel [Canvas](https://github.com/cnvs/canvas).

## Installation

Add this to your project's `composer.json` file:

```javascript
    // ...
    "require": {
        // ...
        "mopo922/canvas-importer": "^1.0",
        // ...
    },
    // ...
```

Add this to the `providers` array in `config/app.php`:

```php
'providers' => [
    // ...
    CanvasImporter\CanvasImporterServiceProvider::class,
]
```

Run `composer update`. That's it!

## Usage

From your project's root directory, run `php artisan canvas:import`. The importer
will take care of the rest, prompting you for the information it needs to complete
the task. Have the URL of your old blog handy, along with the admin username & password.
