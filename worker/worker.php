<?php

    require __DIR__ . '/vendor/autoload.php';

    $tick = 0;

    $path = dirname(__FILE__);

    $pid = getmypid();

    if(!isset($argv[1]) || $argv[1] == null || $argv[1] === '') {
        echo "No tSuite uri argument specified for worker.php. Exiting!\n";
        exit(1);
    }

    $uri = $argv[1];

    while(true) {
        if($tick++ % 10 == 0) {
            echo shell_exec("php $path/test_worker.php $pid $uri");
        }
        sleep(1);
    }

?>