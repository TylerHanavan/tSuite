<?php

    $tick = 0;

    while(true) {
        if($tick++ % 10 == 0) {
            echo shell_exec('php test_worker.php');
        }
        sleep(1);
    }

?>