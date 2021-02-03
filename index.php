<?php
############################################################
# Stpe 1: Get the max num of available Linux/Unix processes
############################################################

$running_processes  = (int)(`ps -e | wc -l`);
$max_processes      = (int)(`ulimit -u`);

# Getting up to 10% of max num of available processes from current user
$available_async_processes = ceil( ($max_processes - $running_processes) * .6 );

echo "$available_async_processes alerts to be processed.\n";

############################################################
# Stpe 2: Make async processes
############################################################

$begin_time = time();

for( $i = 0; $i <= $available_async_processes; $i++ ){
    
    $id = $i . '_' . time();

    # The amp "&" char at the end makes the magic here ;)
    `php async_process.php $id > /dev/null &`; 
}

$end_time = time();

############################################################
# Stpe 3: Wake the deamon up!
############################################################

`php deamon.php > /dev/null &`;

echo 'Total secs to process ' . $available_async_processes . ' async instances: ' . ($end_time - $begin_time);
