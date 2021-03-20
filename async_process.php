<?php

$secs = mt_rand(600, 1000);

`echo 'Processing data for #job({$argv[1]}): sleep({$secs})...' >> logs/log.txt`;

sleep($secs);

`echo 'Finished #job({$argv[1]})!.' >> logs/log.txt`;