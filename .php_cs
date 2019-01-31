<?php

require(__DIR__ .'/vendor/autoload.php');

$config = new Mapado\CS\Config(true, [
    '@Symfony:risky' => true,
    'mb_str_functions' => true,
    'explicit_indirect_variable' => true,
    '@DoctrineAnnotation' => true,
    'native_function_invocation' => false,
    'doctrine_annotation_spaces' => [
        'before_array_assignments_colon' => false,
        'around_parentheses' => false,
    ],
    'doctrine_annotation_array_assignment' => [
        'operator' => '=',
    ],
    'simplified_null_return' => false,
]);


$config->getFinder()
    ->in([
        'src',
        'Tests',
    ])
    // if you want to exclude Tests directory
    ->exclude('cache')
;

return $config;
