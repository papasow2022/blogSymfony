<?php

use App\Kernel;

// Configuration du fuseau horaire pour la Guinée (GMT+0)
date_default_timezone_set('Africa/Conakry');

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
