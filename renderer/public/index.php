<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Renderer\Presenters\PublicSitePresenter;

$presenter = new PublicSitePresenter();
$presenter->render();
