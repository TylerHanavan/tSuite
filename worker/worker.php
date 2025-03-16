<?php

    require __DIR__ . '/vendor/autoload.php';

    $tick = 0;

    $path = dirname(__FILE__);

    while(true) {
        if($tick++ % 10 == 0) {
            echo shell_exec("php $path/test_worker.php");
        }
        sleep(1);
    }

?>