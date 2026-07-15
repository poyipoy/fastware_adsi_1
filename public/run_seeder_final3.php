<?php
$output = shell_exec('cd ../ && php -d opcache.enable_cli=0 artisan db:seed --class=DummyCompetencyDataSeeder 2>&1');
echo $output;
