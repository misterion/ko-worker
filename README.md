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

First of all your should create your RabbitMq configuration file. For example config.yaml:
```yaml
connections:
  default:
    host:     'localhost'
    port:     5672
    login:    'guest'
    password: 'guest'
    vhost:    '/'
producers:
  social_activity:
    connection:       default
    exchange_options: {name: 'social_activity_exchange', type: direct, durable: 1, passive: 1}
consumers:
  social_activity:
    connection:    default
    queue_options:
      name: 'social_activity_queue'
      durable: 1
      autodelete: 1
      exclusive: 0
      qos: 5
      binding: {name: social_activity_exchange, routing-keys: *}
  class: \MyProject\TestAction
```

Here we configure the exchange producer and queue consumer that our application will have. In this example Ko\AmqpBroker will contain the producer `social_activity` and consumer named `social_activity`.

You should specify a connection for the client.

If you need to add optional queue or exchange arguments, then your options can be something like this:

```yaml
queue_options: {name: 'upload-picture', arguments: {'x-ha-policy': ['S', 'all']}}
```

another example with message TTL of 20 seconds:

```yaml
queue_options: {name: 'upload-picture', arguments: {'x-message-ttl': ['I', 20000]}}
```

The argument value must be a list of datatype and value. Valid datatypes are:

* `S` - String
* `I` - Integer
* `D` - Decimal
* `T` - Timestamps
* `F` - Table
* `A` - Array

Adapt the `arguments` according to your needs.

If you want to bind queue with specific routing keys you can declare it in producer or consumer config:

```yaml
queue_options:
    binding:
        - {name: "social_activity_exchange", routing_keys: 'social.#.addFriends'}
        - {name: "social_activity_exchange", routing_keys: '*.removeFriends'}
```

### Producers, Consumers, AqmBroker? ###

In a messaging application, the process sending messages to the broker is called __producer__ while the process receiving
those messages is called __consumer__. In your application you will have several of them that you can list under their
respective entries in the configuration.

### Producer ###

A producer will be used to send messages to the server. In the AMQP Model, messages are sent to an __exchange__, this means that in the configuration for a producer you will have to specify the connection options along with the exchange options, which usually will be the name of the exchange and the type of it.

Now let's say that you want to process some social network activity in the background. After you add friend, you will publish a message to server with the following information:

```php
public function addFriendAction($name)
{
    $msg = array('user_id' => 1235, 'friend_id' => '67890');
    $this->broker->getProducer('social_activity')->publish(json_encode($msg));
}
```

Besides the message itself, the `Ko\RabbitMq\Producer#publish()` method also accepts an optional routing key parameter and an optional array of additional properties. This way, for example, you can change the application headers.

The next piece of the puzzle is to have a consumer that will take the message out of the queue and process it accordingly.

### Consumers ###

A consumer will connect to the server and start a __loop__  waiting for incoming messages to process. Depending on the specified __class__ for such consumer will be the behavior it will have. Let's review the consumer configuration from above:

```yaml
consumers:
  social_activity:
    connection:    default
    queue_options:
      name: 'social_activity_queue'
      durable: 1
      autodelete: 1
      exclusive: 0
      qos: 5
      binding: {name: social_activity_exchange, routing-keys: *}
  class: \MyProject\TestAction
```

As we see there, the __class__ option has a reference to an __\\MyProject\\TestAction__ class. It should implements __Ko\\Worker\\ActionInterface__.

When the consumer gets a message from the server it will create and execute such class. If for testing or debugging purposes you need to specify a different class, then you can change it there.

Apart from the callback we also specify the connection to use, the same way as we do with a __producer__. The remaining options are the the __queue\_options__. In the __queue\_options__ we will provide a __queue name__ and __binding__.

Why?

As we said, messages in AMQP are published to an __exchange__. This doesn't mean the message has reached a __queue__. For this to happen, first we need to create such __queue__ and then bind it to the __exchange__.

The cool thing about this is that you can bind several __queues__ to one __exchange__, in that way one message can arrive to several destinations.

The advantage of this approach is the __decoupling__ from the producer and the consumer.
The producer does not care about how many consumers will process his messages.

All it needs is that his message arrives to the server.
In this way we can expand the actions we perform every time a friend added  without the need to change code in our controller.

Now, how to run a consumer? There's a command for it that can be executed like this:

```bash
$ ./bin/ko -q social_activity -w 10
```

What does this mean?

We are executing the __social\_activity__ consumer telling it should use 10 child process to consuming.

Every time the consumer receives a message from the server, it will execute the configured callback passing the AMQP message as an instance of the `AMQPEnvelope` class. The message body can be obtained by calling `$msg->getBody()`.

By default the consumer will process messages in an __endless loop__ for some definition of _endless_.

UNDONE

## Credits ##

Ko-worker written as a part of [GameNet project](http://gamenet.ru) by Nikolay Bondarenko (misterionkell at gmail.com)
and Vadim Sabirov (pr0head at gmail.com).
We use some interesting ideas from Alvaro Videla [Thumper](https://github.com/videlalvaro/Thumper) and part of documentation from [RabbitMqBundle](https://github.com/videlalvaro/RabbitMqBundle).

## License ##

Released under the [MIT](LICENSE) license.

## Links ##

* [Project profile on the Ohloh](https://www.ohloh.net/p/ko-worker)