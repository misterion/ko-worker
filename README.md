# ko-worker #

[![Build Status](https://travis-ci.org/misterion/ko-worker.svg)](https://travis-ci.org/misterion/ko-worker)

Ko-worker project is a base to develop amqp based message dispatchers.


Based on idea of
 - https://github.com/videlalvaro/RabbitMqBundle
 - https://github.com/videlalvaro/Thumper

    $config = [
    ....
    ];

    $m = new Ko\AmqpManager($config);
    $m->getProducer('name')->publish($message);

    $m = new Ko\AmqpManager($config);
    $m->getConsumer('name')->consume(function() {

    });


  ko --config=somefile.yaml --workers=10

