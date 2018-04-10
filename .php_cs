<?php

require(__DIR__ .'/vendor/autoload.php');

$config = new \Mapado\CS\Config(false);

$config->getFinder()
    ->in([
        __DIR__.'/src',
        __DIR__.'/Tests',
    ])
    // if you want to exclude Tests directory
    // ->exclude([ 'Tests' ])
;

return $config;
