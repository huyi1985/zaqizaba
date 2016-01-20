<?php

require 'ae_select.php';

$el = new aeEventLoop();


$el->aeSetBeforeSleepProc(function() {
    echo 'aeSetBeforeSleepProc', PHP_EOL;
});

$el->aeMain();
$el->aeDeleteEventLoop();