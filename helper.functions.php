<?php

/**
 * Return an array with all current and running 
 * Unix/Linux instances/terminals of "$file".
 * 
 * 
 * ```
 * [0] => 501           User ID
 * [1] => 34715         PID
 * [2] => 1             Parent PID
 * [3] => 10:14AM       Starting time
 * [4] => ttys000       Name of the controlling terminal for the process
 * [5] => 0:00.02       Total CPU usage time
 * [6] => php           Command
 * [7] => handler.php   File
 * [8] => 0_1612289666  $argv[1]
 * ```
 * @link https://kb.iu.edu/d/afnv
 * 
 * @param string $file
 * @return array
 */
function getInstancesOf( $file ) : array
{
    # Get all running Unix/Linux instances of "$file"
    $instances = `ps -wef | grep "{$file}"`;
    // $instances = `ps -w | grep "{$file}"`;
    // $instances = `ps -e | grep "{$file}"`;

    # Format list of instances
    $list_instances = explode( "\n", $instances );
    $list_instances = array_filter( array_map( 'trim', $list_instances ) );

    # Remove last 2 items: these are the "grep command" instance running and -
    # the other one is the "grep" call itself
    array_splice( $list_instances, -2 );

    # Remove empty items and reset keys
    array_walk( $list_instances, function( &$item ){
        $item = array_values( array_filter( explode( " ", $item ) ) );
    });

    return $list_instances;
}

/**
 * Returns the elapsed time in secs from a given PID.
 *
 * @param integer $pid
 * @return mixed
 */
function getElapsedTimeFromPID( int $pid )
{
    $pid_elapsed_time = trim(`ps -p {$pid} -o etime=`);

    if( empty( $pid_elapsed_time ) )
        return null;

    # It's been running for less than an hour: 59:59...
    if( strlen( $pid_elapsed_time ) < 6 )
        return getDiffTime( '00:' . $pid_elapsed_time );

    # for more than or equal an hour: 01:00:00...
    if( strlen( $pid_elapsed_time ) < 9 )
        return getDiffTime( $pid_elapsed_time );

    # for more than a day: 04-05:33:59 - 4 days, 5 hours, 33 mins and 59 secs
    if( strpos( $pid_elapsed_time, '-' ) !== false ){
        $elapsed = explode( '-', $pid_elapsed_time );
        return ((int) $elapsed[0] * 86400) + getDiffTime( trim($elapsed[1]) );
    }

}

/**
 * Returns the diff of seconds from a given $time against today.
 *
 * @param string $time
 * @return int
 */
function getDiffTime( string $time ) : int
{
    return strtotime($time) - strtotime('TODAY');
}
