<?php

/*
    uname -a # Returns detailed data for Linux server.

    ulimit -a # List all server resources for user.
    ulimit -S[flag from ulimit -a] unlimited # Sets the maximum value for the flag.
    I.e.: ulimit -Su unlimited # Sets the maximum number of processes that the user can use.

    ulimit -u # Returns maximum processes for current user.
    ps -e | wc -l # Num of processes currently running.

    4,194,303 is the maximum limit for Linux x86_64
    32,767 is the maximum limit for Linux x86
*/


##########################################
# Getting global values from args
##########################################
$run_cron = ( !empty($argv[1]) ) ? $argv[1] : 0;
$alert_id = ( !empty($argv[2]) ) ? $argv[2] : 0;


##########################################
# Run this script to bring all alerts
##########################################
if( $run_cron && empty($alert_id) ) {

    # Cleaning base log dir
    // `rm $basepath/logs/* > logs/errors/error.log &`;

    # Get the maximum available Linux/Unix processes
    $running_processes  = (int)(`ps -e | wc -l`);
    $max_processes      = (int)(`ulimit -u`);

    # Getting up to 60% of max num of available processes from current user
    $bi_alerts          = ceil( ($max_processes - $running_processes) * .3 );

    echo "$bi_alerts alerts to be processed.\n";

    $begin_time = time();

    # BI alerts
    for( $i=0; $i<=$bi_alerts; $i++ ){

        $id = $i . '_' . time();

        // `php handler.php $id > logs/log$id.txt &`;
        `php index.php 0 $id > /dev/null &`;

    }

    $end_time = time();

    echo 'Total secs: ' . ($end_time - $begin_time);

}


##########################################
# Run this script as alert instance
##########################################
if( $alert_id && empty($run_cron) ){
    $secs = mt_rand(1, 5);

    `echo "Processing data for #job({$alert_id}): sleep({$secs})..." >> logs/log.txt`;

    sleep($secs);

    `echo "DONE: Finished #job({$alert_id})!. Slept: $secs secs." >> logs/log.txt`;
}

// $instances = `ps -wef | grep "index.php 0"`;

// $list_instances = explode( "\n", $instances );

// var_dump( $list_instances );