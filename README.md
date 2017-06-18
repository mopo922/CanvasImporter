# CanvasImporter
Command line tool to import another blog's posts into Laravel [Canvas](https://github.com/cnvs/canvas).

## Installation

### Canvas

Add this to your project's `composer.json` file:

```javascript
    // ...
    "require": {
        // ...
        "mopo922/canvas-importer": "dev-master",
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

Run `composer update`.

After the import is complete, you can remove these lines and run `composer update`
again if you don't plan on using the importer any more.

### WordPress

When importing a WordPress blog, you'll need to install this Basic Authentication
plugin to allow the importer to talk to the WordPress API using your admin username & password:

https://github.com/WP-API/Basic-Auth

1. Download the basic-auth.php file.
2. "Zip" it.
3. Upload it using the Add Plugin UI in your WordPress back-end.
4. Activate the plugin.

*IMPORTANT:* You should deactivate this plugin as soon as the import is complete,
as it is not recommended for production environments.

## Usage

From your project's root directory, run `php artisan canvas:import`. The importer
will take care of the rest, prompting you for the information it needs to complete
the task. Have the URL of your old blog handy, along with the admin username & password.

*Pro Tip:* If you're using a VM, like Vagrant or Laravel Homestead, make sure you're
on the server, not on the host machine, when running `canvas:import`.
