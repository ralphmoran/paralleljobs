# Parallel jobs - Asynced processes - Threads
Vanilla #PHP does not support #asynchronous or #background processes or #multitask feature. Here, I can explain how I've accomplished this well-required feature on *NIX OS.

The use of backticks (`) in your PHP script is not recommended because they make your code less portable but there are many options to "achieve" that feature, like building a VirtualBox and defining the best Linux distro for your needs where your code is going to run on, that way, your code can accomplish that.

## Thread class

### Instantiation

```php
$thread = new Thread(); # Default thread group is assigned

or 

$thread = new Thread('spx'); # 'spx' is now the general group label
```

### Dispatching a single thread (default group)

It registers and creates a new thread in background for 'async_process.php' with one argument 'single_command_default_group'. Thread object adds a unique hash to each new asynced thread for further handling.

```php
$thread->dispatch( 'async_process.php single_command_default_group' );
```

### Dispatching a list of threads, assigning a group name to this colection, registering and dispatching them all.

```php
#-----------------------------------------------------------------
# Register threads, assing a group "g1", and dispatch them all.
$thread
    ->group( 'g1' ) # 'g1' overwrites general group label (highest priority)
    ->register(
    [
        ['async_process.php 1 2 3 4 5'],
        ['async_process.php 1 2 3'],
        ['async_process.php 1'],
    ]
    );
    ->dispatchAll(); # Calling dispatch() or dispatchAll() will blank group name.
```

```php
#-----------------------------------------------------------------
# Register a single thread, assing it to a group "g2", and dispatch it.
$thread
        ->group( 'g2' )
        ->register( 'async_process.php register_dispatchAll' );
        ->dispatchAll(); # Calling dispatch() or dispatchAll() will blank group name.

```

```php
#-----------------------------------------------------------------
# Add to a group "g3", register them, and dispatch a collection of commands.
$thread
        ->group( 'g3' )
        ->dispatch(
        [
            ['async_process.php d1'],
            ['async_process.php d2'],
            ['async_process.php d3'],
        ]
    ); # Calling dispatch() or dispatchAll() will blank group name.

```

```php
#-----------------------------------------------------------------
# Dispatch a single command in series, it takes the last assigned group, in this case "g3".
for( $i=0; $i<=2; $i++ )
    $thread->dispatch( 'async_process.php single_command_last_group' . $i ); # Calling dispatch() or dispatchAll() will blank group name.

$thread->dispatchAll(); # Calling dispatch() or dispatchAll() will blank group name
```

### Get all registered threads

```php
#-----------------------------------------------------------------
# Get all registered threads
print_r( $thread->getRegisteredThreads() );
```

### Get all running threads or by group, word

```php
#-----------------------------------------------------------------
# Get ONLY running threads by group or word
$thread->getRunningThreads('spx');
$thread->getRunningThreads('g1');
$thread->getRunningThreads('g2');
$thread->getRunningThreads('g3');

# Get ONLY al running threads
$thread->getRunningThreads();
```

## Killing all threads or by group, word

```php
#-----------------------------------------------------------------
# Kill threads by groups or word
$thread->killAll('spx');
$thread->killAll('g1');
$thread->killAll('g2');
$thread->killAll('g3');

# Kill all threads
$thread->killAll();
```


## Important notes for me

### How to make async instances of a PHP script

```php
<?php

# Make 10 async jobs for async_process.php
for( $i = 0; $i < 10; $i++ ){
    
    $id = $i . '_' . time();

    # The amp "&" char at the end makes the magic here ;)
    `php async_process.php {$id} > /dev/null &`; 
}
```

## How to wake the deamon up
```php
<?php

# Again, it's important the last amp "&" char to make it asynced and pass this call to Unix/Linux kernel
`php deamon.php > /dev/null &`;
```

### Other userful commands

####  Get all running Unix/Linux instances that contain "word"
```php
<?php

# List all running processes that contain word in them, including the actual grep process
$instances = `ps -wef | grep "{word}"`;

# Rules out the actual grep process from results
$instances = `ps -wef | grep "{word}" | grep -v grep`; 

# Returns ONLY running process with specific columns: PID, ELAPSED TIME, COMMAND, and rules out grep process
$instances = `ps -ro pid,etime,command | grep "{word}" | grep -v grep`; 
```

I.e.
```php
<?php

$instances = `ps -wef | grep "async_process.php"`;
$instances = `ps -wef | grep "async_process.php" | grep -v grep`;
$instances = `ps -ro pid,etime,command | grep "async_process.php" | grep -v grep`;
```

#### Returns the elapsed time in secs from a given PID
```php
<?php

$pid_elapsed_time = trim(`ps -p {$pid} -o etime=`);
```

I.e.
```php
<?php

# It returns 03:07 which is 3 mins and 7 secs
$pid_elapsed_time = trim(`ps -p 37809 -o etime=`);
```


#### Returns detailed data of the Linux server.
```
uname -a 
```

#### List all server resources for the current user.
```
ulimit -a 
```

#### Sets the maximum value for the flag.
```
ulimit -S[flag from ulimit -a] unlimited 
```
I.e.
```
# Sets the maximum number of processes that the user can use.
ulimit -Su unlimited 
```

#### Returns maximum processes for current user.
```
ulimit -u 
```

#### Num of processes currently running.
ps -e | wc -l 

4,194,303 is the maximum limit for Linux x86_64
and 32,767 is the maximum limit for Linux x86

#### Process columns description - Ref. https://kb.iu.edu/d/afnv
```
ps -elf
```
