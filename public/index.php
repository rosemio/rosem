<?php
/**
 * Rosem - Yet another Web Framework.
 *
 * @package  Rosem
 * @author   Roman Shevchenko <iroman.via@gmail.com>
 */

declare(strict_types=1);

define('APP_START', microtime(true));

//$_SERVER['REQUEST_METHOD'] = 'POST';
//$_POST['username'] = 'admin';
//$_POST['password'] = 'admin';
//$_SERVER['REQUEST_URI'] = '/admin/login';

/*
|--------------------------------------------------------------------------
| Register The Composer Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader
| for our application. We just need to utilize it! We'll require it
| into the script here so that we do not have to worry about the
| loading of any our classes "manually". Feels great to relax.
|
*/

require_once __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Turn On The Lights
|--------------------------------------------------------------------------
|
| We need to illuminate PHP development, so let us turn on the lights.
| This bootstraps the framework and gets it ready for use, then it
| will load up this application so that we can run it and send
| the responses back to the browser and delight our users.
|
*/

require_once __DIR__.'/../bootstrap.php';
