<?php
$files = glob(__DIR__.'/../*.sql');
print_r($files);
$files2 = glob(__DIR__.'/../database/*.sql');
print_r($files2);
$files3 = glob(__DIR__.'/../database/seeders/*.sql');
print_r($files3);
