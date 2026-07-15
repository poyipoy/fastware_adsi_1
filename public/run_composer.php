<?php
$output = shell_exec('composer dump-autoload 2>&1');
echo $output;
