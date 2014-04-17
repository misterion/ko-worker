<?php
use Ko\RabbitMq\Consumer;

class ConsumerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $channelMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $queueMock;

    public function setUp()
    {
        $this->channelMock = $this->getMockBuilder('\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->queueMock  = $this->getMockBuilder('\AMQPQueue')
            ->disableProxyingToOriginalMethods()
            ->disableOriginalConstructor()
            ->setMethods(['consume', 'bind', 'declareQueue'])
            ->getMock();
    }

    public function testQueueOptionsHasValidDefaultValues()
    {
        $expectedDefaultOptions = [
            'name' => '',
            'passive' => false,
            'durable' => true,
            'exclusive' => false,
            'auto_delete' => false,
            'nowait' => false,
            'arguments' => [],
            'ticket' => null
        ];

        $c = new Consumer($this->channelMock, $this->queueMock);
        $this->assertEquals($expectedDefaultOptions, $c->getQueueOptions());
    }

    public function testSetQueueOptions()
    {
        $expectedOptions = [
            'name' => '1',
            'passive' => true,
        ];

        $c = new Consumer($this->channelMock, $this->queueMock);
        $expectedDefaultOptions = $c->getQueueOptions();
        $c->setQueueOptions($expectedOptions);
        $this->assertEquals(array_merge($expectedDefaultOptions, $expectedOptions), $c->getQueueOptions());
    }

    public function testConsumeShouldDeclareQueueOnlyOnce()
    {
        $this->queueMock->expects($this->once())
            ->method('declareQueue');

        $c = new Consumer($this->channelMock, $this->queueMock);
        $c->setQueueOptions(['name' => 'myQueue', 'binding' => ['name' => 'myExchange', 'routing-keys' => '']]);
        $c->consume(function(){});
        $c->consume(function(){});
    }

    public function testOnceBinding()
    {
        $this->queueMock->expects($this->once())
            ->method('bind')
            ->with($this->equalTo('testName'), $this->equalTo('testKey'));

        $c = new Consumer($this->channelMock, $this->queueMock);
        $c->setQueueOptions(['name' => 'myQueue', 'binding' => ['name' => 'testName', 'routing-keys' => 'testKey']]);
        $c->consume(function(){return 'someMessage';});
    }

    public function testMultipleBinding()
    {
        $this->queueMock->expects($this->at(2))
            ->method('bind')
            ->with($this->equalTo('testName'), $this->equalTo('testKey'));

        $c = new Consumer($this->channelMock, $this->queueMock);
        $binding = [
            ['name' => 'testName', 'routing-keys' => 'testKey'],
            ['name' => 'testName', 'routing-keys' => 'testKey'],
        ];
        $c->setQueueOptions(['name' => 'myQueue', 'binding' => $binding]);
        $c->consume(function(){return 'someMessage';});
    }

    /**
     * @dataProvider flagProvider
     */
    public function testFlagOptions($passive, $durable, $autoDelete, $noWait, $flag)
    {
        $c = new Consumer($this->channelMock, $this->queueMock);
        $options = [
            'passive' => false,
            'durable' => false,
            'auto_delete' => false,
            'nowait' => false,
        ];
        $c->setQueueOptions($options);
        $this->assertEquals(0, $c->getFlagsFromOptions());

        $options = [
            'passive' => $passive,
            'durable' => $durable,
            'auto_delete' => $autoDelete,
            'nowait' => $noWait,
        ];
        $c->setQueueOptions($options);
        $this->assertEquals($flag, $c->getFlagsFromOptions());
    }

    public function flagProvider()
    {
        return [
            [true, false, false,false, AMQP_PASSIVE],
            [false, true, false,false, AMQP_DURABLE],
            [false, false, true, false, AMQP_AUTODELETE],
            [false, false, false, true, AMQP_NOWAIT],
            [true, true, true, true, AMQP_PASSIVE | AMQP_DURABLE | AMQP_AUTODELETE | AMQP_NOWAIT],
        ];
    }
}
