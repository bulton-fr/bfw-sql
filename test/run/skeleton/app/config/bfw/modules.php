<?php

$config = require(__DIR__.'/modules.php.original');

$config['modules']['db']['name']    = 'bfw-sql';
$config['modules']['db']['enabled'] = true;

return $config;
