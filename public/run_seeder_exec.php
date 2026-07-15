<?php
$output = shell_exec('cd ../ && php artisan db:seed --class=DummyCompetencyDataSeeder 2>&1');
echo $output;
