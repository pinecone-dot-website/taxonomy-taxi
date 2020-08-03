<?php

namespace Taxonomy_Taxi;

define('TAXONOMY_TAXI_FILE', __FILE__);

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

if (is_admin()) {
    require __DIR__ . '/admin.php';
}
