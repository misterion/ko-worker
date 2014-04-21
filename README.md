# ko-worker #
[![Build Status](https://travis-ci.org/misterion/ko-worker.svg)](https://travis-ci.org/misterion/ko-worker)

## About ##
Ko-worker project is a PHP library that aims to abstract queue and RPC messaging patterns that can be implemented over RabbitMQ.
The other goal is simplify message dispatching and RPC server development over RabbitMQ.

```php
$m = new Ko\AmqpBroker($config);
$m->getProducer('upload_picture')->publish($message);
```

Each time you starting to use something like queues you should decide how to process your messages.
Yes, it's sounds not complicated:
 - write some console application or daemon
 - grab message from queue and execute them in master or child process
 - restart child process if it fail
 - log crashes or so on
 - ...

And Ko-Worker allows to simplify this to just 2 steps
 - create config file
 - run ko application

```bash
$ ./bin/ko --workers 5 --consumer upload_picture
```

Why Ko-worker?
* Used in real life highload [GameNet project](http://gamenet.ru)
* Close to 100% code coverage
* Simple - less then 5 minuted before you start.

## Requirements ##

    PHP >= 5.4
    pcntl extension installed
    posix extension installed
    amqp extension installed

## Installation ##

### Composer ###
The recommended way to install library is [composer](http://getcomposer.org).
You can see [package information on Packagist](https://packagist.org/packages/misterion/ko-worker).

```JSON
{
	"require": {
		"misterion/ko-worker": "*"
	}
}
```

### Do not use composer? ###
Just clone the repository and take care about autoload for namespace `Ko`.

## Examples ##

UNDONE

## Usage ##

UNDONE

## Credits ##

Ko-worker written as a part of [GameNet project](http://gamenet.ru) by Nikolay Bondarenko (misterionkell at gmail.com)
and Vadim Sabirov (pr0head at gmail.com).
We use some interesting ideas from Alvaro Videla [Thumper](https://github.com/videlalvaro/Thumper) and [RabbitMqBundle](https://github.com/videlalvaro/RabbitMqBundle).

## License ##

Released under the [MIT](LICENSE) license.

## Links ##

* [Project profile on the Ohloh](https://www.ohloh.net/p/ko-worker)