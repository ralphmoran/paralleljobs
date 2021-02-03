<?php

include('helper.functions.php');

# Value in seconds when each async process must be stopped/killed
$max_elapsed_time   = 45;

do {

    $instances = getInstancesOf( 'async_process.php' );

    foreach( $instances as $index => $instance ){

        $pid            = $instance[1];
        $elapsed_time   = getElapsedTimeFromPID( $pid );

        if( $elapsed_time >= $max_elapsed_time ){
            
            `kill $pid`;
            
            # Not required. Just for debugging purpose
            `echo 'Process PID:$pid was killed due max running time of $max_elapsed_time secs. Internal ID: $instance[8].' >> logs/log.txt`;
            
            unset($instances[$index]);
            
        }

    }

    usleep(900);

} while( count( $instances ) );