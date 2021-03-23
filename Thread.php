<?php

$start = microtime(true);

/*
    TODO:

    1. Work on access permissions: 
        one set for the owner, 
        one set for the group and 
        one set for others.
    2. Make it distribuited: "Search if this script is running on remote machines"
 */

/**
 * Thread
 * 
 * // *nix
 * echo DIRECTORY_SEPARATOR; // /
 * echo PHP_SHLIB_SUFFIX;    // so
 * echo PATH_SEPARATOR;      // :
 * 
 * // Win*
 * echo DIRECTORY_SEPARATOR; // \
 * echo PHP_SHLIB_SUFFIX;    // dll
 * echo PATH_SEPARATOR;      // ;
 */

final class Thread
{
    const NIX_OS_LABEL = 'NIX';
    const WIN_OS_LABEL = 'WIN';
    const NIX_DEFAULT_OUTPUT = '/dev/null';
    const WIN_DEFAULT_OUTPUT = '$null';

    const STATUS_INIT = 0;
    const STATUS_RUNNING = 1;
    const STATUS_STOPPED = 2;
    const STATUS_FINISHED = 3;
    const STATUS_KILLED = 4;

    const THREAD_PREPEND = 'thread_';

    private $thread_keyword = '';
    private $max_available_processes;
    private $executables = ['php'];
    private $output_operators = ['>', '>>'];
    private $output_endpoints = ['/dev/null', 'NUL', '$null'];
    private $registered_threads = [];

    private $current_os = '';

    /*
        TODO:

        1. Return running processes
        2. Return elapsed time from a process
        - 3. Add a hash key to each thread for internal control
        5. Fix output_endpoints to accept custom ones
        6. Work on memory efficiency: heavy arrays... Use generators
    */

    public function __construct( ...$args ) // $thread_group = '' )
    {
        $this->parseArgs( $args );

        $this->current_os = ( substr( php_uname(), 0, 3 ) == "Win" ) ? self::WIN_OS_LABEL : self::NIX_OS_LABEL;

        # Set thread group label
        if( ! empty( $args['thread_group'] ) && is_string( $args['thread_group'] ) )
            $this->setThreadKeyword( $args['thread_group'] );

        # Set executables
        if( ! empty( $args['execs'] ) && is_array( $args['execs'] ) )
            $this->setExecutables( $args['execs'] );
    }


    /**
     * Registers a possible thread into a global array of threads to be dispatched.
     *
     * @param array $threads
     * @return Thread
     */
    public function register( $threads = [] )
    {
        if( is_array( $threads ) )
        {
            foreach( $threads as $thread )
            {
                $thread = $this->clean( $thread );

                if( is_array( $thread ) && ! empty($thread) )
                    $this->registerThread( $this->formatThread( $thread ) );
            }

            return $this;
        }

        if( is_string( $threads ) && ! empty($threads) )
            $this->registerThread( $this->formatThread( [ $threads ] ) );

        return $this;
    }


    /**
     * Dispatches all registered threads.
     *
     * @return Thread
     */
    public function dispatchAll()
    {
        foreach( $this->getRegisteredThreads() as $hash => $t )
            if( ! empty( $hash ) && ! in_array( $hash, array_values( array_column( $this->getRunningThreads(), 'hash' ) ) ) )
                $this->dispatch( $hash );

        return $this->updateRegisteredThreads()
                ->setThreadKeyword('');
    }


    /**
     * Dispatches a single registered thread by its internal hash.
     *
     * @param string $thread_hash
     * @return Thread
     */
    public function dispatch( $thread_hash )
    {
        if( empty( $thread_hash ) )
            return $this;

        if( is_array( $thread_hash ) )
            return $this->register( $thread_hash )
                ->dispatchAll();

        if( isset( $this->getRegisteredThreads()[ $thread_hash ] ) )
        {
            $this->sendToKernel( $this->getRegisteredThreads()[ $thread_hash ][ 'body' ] );

            return $this;
        }

        $thread_hash = $this->formatThread( [ $thread_hash ] );

        $this->registerThread( $thread_hash )
                ->sendToKernel( $thread_hash[ 'body' ] );

        return $this;
    }


    /**
     * Returns running threads based on $keyword.
     *
     * @param string $keyword If it is not empty, 
     * @return array
     */
    public function getRunningThreads( $keyword = '' ) : array
    {
        $keyword = $keyword ?: self::THREAD_PREPEND;

        return array_map( function( $t )
                { 
                    return [
                        'pid'     => $t[ 0 ],
                        'elapsed' => $t[ 1 ],
                        'hash'    => end( $t )
                    ]; 
                }, $this->find( $keyword ) );
    }


    /**
     * Returns registered threads.
     *
     * @return array
     */
    public function getRegisteredThreads()
    {
        $this->updateRegisteredThreads();

        return $this->registered_threads;
    }


    /**
     * Returns the diff of seconds from a given $time against today.
     *
     * @param string $time
     * @return int
     */
    public function getDiffTime( string $time ) : int
    {
        return strtotime($time) - strtotime('TODAY');
    }


    /**
     * Searches for one or more threads in global array of $this->registered_threads, if exist, 
     * it greps for the thread in the process' list, if this thread is still running and active,
     * $this->find( $thread ) returns an array with all current and running Unix/Linux 
     * instances/terminals for $thread.
     * 
     * ```
     * #ps -f | grep -E 'php handler.php 0_1612289666'
     * 
     * [0] => 501           User ID
     * [1] => 34715         PID
     * [2] => 1             Parent PID
     * [3] => 10:14AM       Starting time
     * [4] => ttys000       Name of the controlling terminal for the process
     * [5] => 0:00.02       Total CPU usage time
     * [6] => php           Command
     * [7] => handler.php   File
     * [8] => 0_1612289666  $argv[1]
     * 
     * #ps -e | grep -E 'php handler.php 0_1612289666'
     * 
     * [0] => 34715         PID
     * [1] => ttys000       Name of the controlling terminal for the process
     * [2] => 0:00.02       Total CPU usage time
     * ...
     * [N-2] => /dev/null/  Default OS output
     * [N-1] => &           For *NIX
     * 
     * #ps -ro pid,etime,command | grep -E '$threads' | grep -v grep
     * 
     * [0] => 34715         PID
     * [1] => 03:15         Elapsed time
     * [2] => php           Command
     * [3] => --no-php-init args1
     * [...] => ...         argsN
     * ```
     * @link https://kb.iu.edu/d/afnv
     * 
     * @param string $file
     * @return array
     */
    public function find( $threads ) : array
    {
        $instances = $this->sendToKernel( "ps -ro pid,etime,command | grep -E '$threads' | grep -v grep" );

        if( empty( $instances ) ) 
            return [];

        $this->explodeAndClean( $instances, "\n" );

        array_walk( $instances, function( &$item ){
            $item = array_values( array_filter( explode( " ", $item ) ) );
        });

        return $instances;
    }


    /**
     * Kills a thread by its PID
     *
     * @param string $hash
     * @return mixed
     */
    public function kill( $hash )
    {
        $pid = $this->registered_threads[ $hash ][ 'pid' ];

        if( ! empty( $pid ) && $this->getOS() === self::NIX_OS_LABEL )
        {
            $this->sendToKernel( "kill $pid" );
            $this->registered_threads[ $hash ][ 'status' ] = self::STATUS_KILLED;
            $this->registered_threads[ $hash ][ 'pid' ] = '';
        }

        return $this;

        # WIN OS
    }


    /**
     * Kills all running threads based on a group. If $group is empty, it kills 
     * all processes based on self::THREAD_PREPEND value.
     *
     * @return Thread
     */
    public function killAll( $group = '' )
    {
        $pids_to_kill = '';

        foreach( array_column( $this->getRunningThreads( $group ), 'pid' ) as $pid )
            $pids_to_kill .= $pid . ' ';

        $this->sendToKernel( 'kill ' . trim( $pids_to_kill ) );

        return $this;
    }


    /**
     * Sets/assings a thread group.
     *
     * @param string $group
     * @return Thread
     */
    public function group( $group )
    {
        return $this->setThreadKeyword( $group );
    }


    /**
     * Updates all registered threads.
     *
     * @return Thread
     */
    private function updateRegisteredThreads()
    {
        foreach( $this->getRunningThreads() as $thread )
        {
            $this->registered_threads[ $thread[ 'hash' ] ][ 'pid' ] = $thread[ 'pid' ];
            $this->registered_threads[ $thread[ 'hash' ] ][ 'status' ] = self::STATUS_RUNNING;
            $this->registered_threads[ $thread[ 'hash' ] ][ 'elapsed' ] = $this->getElapsedTimeFrom( $thread[ 'elapsed' ] );
        }

        return $this;
    }


    /**
     * Returns the OS flag. For now it can be NIX or WIN.
     *
     * @return void
     */
    private function getOS()
    {
        return $this->current_os;
    }


    /**
     * Sets a thread keyword.
     *
     * @param string $keyword
     * @return Thread
     */
    private function setThreadKeyword( $keyword )
    {
        $this->thread_keyword = self::THREAD_PREPEND . ( $keyword ?: '' );

        return $this;
    }


    /**
     * Gets the thread keyword.
     *
     * @return string
     */
    private function getThreadKeyword() : string
    {
        return $this->thread_keyword ?: self::THREAD_PREPEND;
    }


    /**
     * Gives a thread the required format based on the OS.
     * 
     * ```php
     *  # Prameter:
     * 
     *  $thread = [
     *              0 => 'file [arg1] [arg2] ... [argN]',
     *              1 => 'php', : default 'php'
     *              2 => '>', : default '>' (clobbers)
     *              3 => '/logs/logN.txt' : default *NIX => '/dev/null' for | WIN => '$null'
     *          ];
     * 
     *  # Return 
     *  $formatted_thread = [
     *              'body'      => $formatted_thread,
     *              'status'    => 'init',
     *              'pid'       => '',
     *              'elapsed'   => 0,
     *          ];
     * ```
     * 
     * I.e.: 'php deamon.php > $null';
     *       'php deamon.php > /dev/null &';
     *
     * @param array $thread
     * @return array
     */
    private function formatThread( array $thread ) : array
    {
        $formatted_thread = '';

        $body    = $thread[0];
        $command = ( ( isset( $thread[1] ) && in_array( $thread[1], $this->getExecutables() ) ) ? $thread[1] : 'php --no-php-ini' );
        $append  = ( ( isset( $thread[2] ) && in_array( $thread[2], $this->output_operators ) ) ? $thread[2] : '>' );
        $output  = ( ( isset( $thread[3] ) && in_array( $thread[3], $this->output_endpoints ) ) ? $thread[3] : self::WIN_DEFAULT_OUTPUT );
        $thread_hash = $this->getThreadHash();

        # I.e.: 'php --no-php-ini deamon.php > $null';
        $formatted_thread .= $command . ' ' . $body . ' ' . $thread_hash . ' ' . $append . ' ' . $output; 

        if( $this->getOS() == self::NIX_OS_LABEL )
            # I.e.: 'php --no-php-ini deamon.php > /dev/null &';
            $formatted_thread = str_replace( $output, self::NIX_DEFAULT_OUTPUT, $formatted_thread ) . ' &';

        return [
            'body'      => $formatted_thread,
            'status'    => self::STATUS_INIT,
            'pid'       => '',
            'elapsed'   => 0,
            'hash'      => $thread_hash
        ];
    }


    /**
     * Registers a thread in a global array within object scope.
     *
     * @param array $thread
     * @return Thread
     */
    private function registerThread( array $thread )
    {
        if( ! in_array( $thread['body'], array_column( $this->registered_threads, 'body' ) ) )
            $this->registered_threads[ $this->getThreadHash() ] = $thread;

        return $this;
    }


    /**
     * Builds a hash based on the last array key of $registered_thread array.
     *
     * @return string
     */
    private function getThreadHash() : string
    {
        end( $this->registered_threads );

        // return $this->getThreadKeyword() . hash( 'sha1', microtime(true) . key( $this->registered_threads ) );
        return $this->getThreadKeyword() . hash( 'sha1', time() . key( $this->registered_threads ) );
    }


    /**
     * Returns a parse string based on the current registered threads.
     *
     * @return string
     */
    public function getParsedThreadHashes() : string
    {
        if( empty( $this->registered_threads ) ) 
            return $this->getThreadKeyword();

        $formatted_hashes = '';

        foreach( $this->registered_threads as $hash => $thread )
            $formatted_hashes .= $hash . '|';

        return trim( $formatted_hashes, '|' );
    }


    /**
     * Sends a command to the OS kernel, *NIX or WIN, to run it in background.
     * 
     * ```php
     *  $thread_body = 'php file.php 1 2 3 > /dev/null/ &';
     * ```
     * 
     * Note: If WIN OS interrupts too fast the command call before this command finishes, there is a need of making a wrapper.
     * 
     * @link https://www.php.net/manual/en/function.popen
     *
     * @param string $command
     * @return mixed
     */
    private function sendToKernel( $command )
    {
        if( empty( $command ) )
            return false;

        if( $this->getOS() === self::NIX_OS_LABEL )
            return `$command`;

        /*
            TODO:

            1. After sending the thread to the WIN kernel it needs to return the PID
        */
        # WIN OS
        @pclose( @popen( "start /B " . $command, "r" ) );

        return $this;
    }


    /**
     * Returns the elapsed time in secs.
     *
     * @param string $elapsed_time
     * @return mixed
     */
    public function getElapsedTimeFrom( $elapsed_time )
    {
        // $elapsed_time = $this->sendToKernel("ps -p {$pid} -o etime=");

        if( empty( $elapsed_time ) )
            return null;

        # lte <= 59:59
        if( strlen( $elapsed_time ) < 6 )
            return $this->getDiffTime( '00:' . $elapsed_time );

        # gte >= 01:00:00
        if( strlen( $elapsed_time ) < 9 )
            return $this->getDiffTime( $elapsed_time );

        # gt > a day 04-05:33:59 - 4 days, 5 hours, 33 mins and 59 secs
        if( strpos( $elapsed_time, '-' ) !== false )
        {
            list( $days, $hh_mm_ss ) = explode( '-', $elapsed_time );
            return ( (int) $days * 86400) + $this->getDiffTime( trim($hh_mm_ss) );
        }
    }


    /**
     * Returns a percentage of untaken Linux processes. By default 20%.
     *
     * @param float $percentage
     * @return integer
     */
    private function getUntakenProcessesByPercentage( $percentage = .2 ) : int
    {
        return ceil( $this->getUntakenProcesses() * $percentage );
    }


    /**
     * Returns an integer of untaken Linux processes.
     *
     * @return integer
     */
    private function getUntakenProcesses() : int
    {
        return $this->max_available_processes = (int) $this->sendToKernel('ulimit -u') - (int) $this->sendToKernel('ps -e | wc -l');
    }


    /**
     * Explodes by a delimiter and removes empty items.
     *
     * @param string $a
     * @param string $delimiter
     * @return 
     */
    private function explodeAndClean( string &$a, string $delimiter )
    {
        $a = explode( "$delimiter", $a );
        $a = $this->clean( $a );
    }


    /**
     * Removes all empty items from an array.
     *
     * @param array $array
     * @return array
     */
    private function clean( array $array ) : array
    {
        return array_filter( array_map( 'trim', $array ) );
    }


    /**
     * Parses by reference the splat-operator array on constructor.
     *
     * @param array &$args
     * @return void
     */
    private function parseArgs( array &$args )
    {
        $args = $args[0];
    }


    /**
     * Returns valid executables.
     *
     * @return array
     */
    private function getExecutables() : array
    {
        return $this->executables;
    }


    /**
     * Defines/assings valid executables.
     *
     * @param array $executables
     * @return Thread
     */
    public function setExecutables( array $executables ) : Thread
    {
        if( ! empty( $executables ) )
            $this->executables = $executables;

        return $this;
    }


    public function dd( $msg, $exit = true )
    {
        print_r( $msg );
        echo "\n";
        ( $exit ) ? exit : '' ;
    }

}



$thread = new Thread([
        'thread_group' => 'spx', # 'spx' is a general group label
        'execs'        => [] # It overwrites, when it's not empty, the default execs. By default, there is just one: php
    ]);

$thread->dispatch( 'async_process.php single_command_default_group' );



#-----------------------------------------------------------------
# Register threads, assing a group "billing_group", and dispatch them all.
$thread
    // ->group( 'g1_' ) # 'g1_' overwrites general group label (highest priority)
    ->register(
    [
        ['async_process.php 1 2 3 4 5'],
        ['async_process.php 1 2 3'],
        ['async_process.php 1'],
    ]
    );
// ->dispatchAll(); # Calling dispatch() or dispatchAll() will blank group name


#-----------------------------------------------------------------
# Register a single thread, assing it to a group "g2_", and dispatch it.
$thread
        // ->group( 'g2_' )
        ->register( 'async_process.php register_dispatchAll' );
        // ->dispatchAll(); # Calling dispatch() or dispatchAll() will blank group name


#-----------------------------------------------------------------
# Add to a group "g3_", register them, and dispatch a collection of commands.
$thread
        // ->group( 'g3_' )
        ->dispatch(
        [
            ['async_process.php d1'],
            ['async_process.php d2'],
            ['async_process.php d3'],
        ]
    ); # Calling dispatch() or dispatchAll() will blank group name


#-----------------------------------------------------------------
# Dispatch a single command in series, it takes the last assigned group.
for( $i=0; $i<=2; $i++ )
    $thread->dispatch( 'async_process.php single_command_last_group' . $i ); # Calling dispatch() or dispatchAll() will blank group name

$thread->dispatchAll(); # Calling dispatch() or dispatchAll() will blank group name

sleep(1);

#-----------------------------------------------------------------
# Get all registered threads
print_r( $thread->getRegisteredThreads() );

#-----------------------------------------------------------------
# Get ONLY running threads: all and by groups
// print_r( $thread->getRunningThreads( 'spx' ) );
// print_r( $thread->getRunningThreads( 'g1_' ) );
// print_r( $thread->getRunningThreads( 'g2_' ) );
// print_r( $thread->getRunningThreads( 'g3_' ) );
// print_r( $thread->getRunningThreads() );

#-----------------------------------------------------------------
# Kill threads by groups
// $thread->killAll( 'spx' );
// $thread->killAll( 'g1_' );
// $thread->killAll( 'g2_' );
// $thread->killAll( 'g3_' );
$thread->killAll();

// print_r( 
//         count( 
//                 $thread
//                 // ->killAll()
//                 // ->getParsedThreadBodies()
//                 // ->getParsedThreadHashes()
//                 ->getRegisteredThreads()
//             )  
//         );



 echo "\n" . ( microtime(true) - $start ) . "\n\n";