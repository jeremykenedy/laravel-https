# Laravel HTTPS Checker

[![Latest Stable Version](https://poser.pugx.org/jeremykenedy/laravel-https/v/stable)](https://packagist.org/packages/jeremykenedy/laravel-https)
[![Total Downloads](https://poser.pugx.org/jeremykenedy/laravel-https/downloads)](https://packagist.org/packages/jeremykenedy/laravel-https)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

- [About](#about)
- [Features](#features)
- [Requirements](#requirements)
- [Installation Instructions](#installation-instructions)
- [Configuration](#configuration)
    - [Environment File](#environment-file)
- [Usage](#usage)
    - [From Route File](#from-route-file)
        - [Route Group Example](#route-group-example)
        - [Individual Route Examples](#individual-route-examples)
    - [From Controller File](#from-controller-file)
        - [Controller File Example](#controller-file-example)
- [Screenshots](#screenshots)
- [File Tree](#file-tree)
- [Opening an Issue](#opening-an-issue)
- [License](#license)

### About

Laravel Https is middleware to check for Secure HTTP requests.
Laravel Https has can check for HTTPS and throw an error or automatically redirect to HTTPS.

### Features

| laravel-https Features  |
| :------------ |
|`forceHTTPS` middlware to check if URL is HTTPS and redirect to HTTPS if not.|
|`checkHTTPS` middlware to check if URL is HTTPS and throw an error if not.|
|Each middleware can be used in individual controllers constructor|
|Each middleware can be used as a middleware on individual routes|
|Each middleware can be used as a middleware route group|
|Returns HTML for HTTP requests|
|Returns JSON for API requests|
|Uses localized language files|

### Requirements
* [Laravel 5.1, 5.2, 5.3, 5.4, or 5.5+](https://laravel.com/docs/installation)

### Installation Instructions
1. From your projects root folder in terminal run:

    ```bash
        composer require jeremykenedy/laravel-https
    ```

2. Register the package

    * Laravel 5.5 and up
    Uses package auto discovery feature, no need to edit the `config/app.php` file.

    * Laravel 5.4 and below
    Register the package with laravel in `config/app.php` under `providers` with the following:

    ```php
        'providers' => [
        ...
            jeremykenedy\LaravelHttps\LaravelHttpsServiceProvider::class,
        ];
    ```

3. Optionally publish the packages views, config file, and language files by running the following from your projects root folder:

    ```bash
        php artisan vendor:publish --tag=LaravelHttps
    ```

4. Add the middleware to your routes or controller. See [Usage](#usage).

### Configuration
laravel-https can be configured in directly in `/config/laravel-https.php` if you published the assets.
Or you can variables to your `.env` file.

##### Environment File
Here are the `.env` file variables available:

```bash
LARAVEL_HTTP_ERROR_CODE=403
```

### Usage

##### From Route File:
* You can include the `checkHTTPS` or `forceHTTPS` in a route groups or on individual routes.

###### Route Group Example:

```php
    Route::group(['middleware' => ['web', 'checkHTTPS']], function () {
        Route::get('/', 'WelcomeController@welcome');
    });
```

###### Individual Route Examples:

```php
    Route::get('/', 'WelcomeController@welcome')->middleware('checkHTTPS');
    Route::match(['post'], '/test', 'Testing\TestingController@runTest')->middleware('forceHTTPS');
```

##### From Controller File:
* You can include the `checkHTTPS` or `forceHTTPS` in the contructor of your controller file.

###### Controller File Example:

```php
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
       $this->middleware('forceHTTPS');
    }
```

### Screenshots
![Http Middleware checkHTTPS](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-https/1-http-call.jpg)
![API Middleware checkHTTPS](https://s3-us-west-2.amazonaws.com/github-project-images/laravel-https/2-api-call.jpg)

### File Tree

```bash
├── .gitignore
├── LICENSE
├── README.md
├── composer.json
└── src
    ├── LaravelHttpsServiceProvider.php
    ├── app
    │   └── Http
    │       └── Middleware
    │           ├── CheckHTTPS.php
    │           └── ForceHTTPS.php
    ├── config
    │   └── laravel-https.php
    └── resources
        ├── lang
        │   └── en
        │       └── laravel-https.php
        └── views
            └── errors
                └── 403.blade.php
```

* Tree command can be installed using brew: `brew install tree`
* File tree generated using command `tree -a -I '.git|node_modules|vendor|storage|tests'`

### Opening an Issue
Before opening an issue there are a couple of considerations:
* If you did not **star this repo** *I will close your issue immediatly without consideration.*.
* **Read the instructions** and make sure all steps were *followed correctly*.
* **Check** that the issue is not *specific to your development environment* setup.
* **Provide** *duplication steps*.
* **Attempt to look into the issue**, and if you *have a solution, make a pull request*.
* **Show that you have made an attempt** to *look into the issue*.
* **Check** to see if the issue you are *reporting is a duplicate* of a previous reported issue.
* **Following these instructions show me that you have tried.**
* If you have a questions send me an email to jeremykenedy@gmail.com
* Please be considerate that this is an open source project that I provide to the community for FREE when openeing an issue. 

Open source projects are a the community’s responsibility to use, contribute, and debug.

### License
Laravel-https is licensed under the MIT license. Enjoy!
