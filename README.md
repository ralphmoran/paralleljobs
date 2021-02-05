# Parallel jobs - Async process
Vanilla #PHP does not support #asynchronous #jobs or #multitask processes. Here I explain how you can achieve it when you run your script on a Unix/Linux OS.

The use of backticks (`) in your PHP script is not recommended because they make your code less portable but there are many options to "achieve" that feature, like building a VirtualBox and defining the best Linux distro for your needs where your code is going to run on, that way, your code can accomplish it.


## How to make async instances of a PHP script

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

####  Get all running Unix/Linux instances of "$file"
```php
<?php

$instances = `ps -wef | grep "{$file}"`;
```

I.e.
```php
<?php

$instances = `ps -wef | grep "async_process.php"`;
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
