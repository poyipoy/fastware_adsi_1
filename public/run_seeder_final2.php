<?php
$output = shell_exec('cd ../ && php artisan clear-compiled 2>&1');
$output .= shell_exec('cd ../ && php artisan optimize:clear 2>&1');
$output .= shell_exec('cd ../ && php artisan db:seed --class=DummyCompetencyDataSeeder 2>&1');
echo $output;
