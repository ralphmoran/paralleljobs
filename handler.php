<?php

$secs = mt_rand(1, 20);

`echo 'Processing data for #job({$argv[1]}): sleep({$secs})...' >> logs/log_$argv[1].txt`;

sleep($secs);

// `rm logs/log$argv[1].txt`;
// `touch logs/log$argv[1].txt`;
`echo 'Finished #job({$argv[1]})!.' >> logs/log_$argv[1].txt`;