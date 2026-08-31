<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap;
use App\Http\Router;

Bootstrap::boot();

$router = new Router();
$router->dispatch();
