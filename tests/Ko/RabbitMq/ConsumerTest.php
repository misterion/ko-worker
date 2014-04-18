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

    public function testConsumeShouldDeclareQueueOnlyOnce()
    {
        $this->queueMock->expects($this->once())
            ->method('declareQueue');

        $c = new Consumer($this->channelMock, $this->queueMock);
        $c->setQueueOptions(['name' => 'myQueue', 'binding' => ['name' => 'myExchange', 'routing-keys' => '']]);
        $c->consume(function(){});
        // Using mock object without locking the previous call
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
        $this->queueMock->expects($this->at(1))
            ->method('bind')
            ->with($this->equalTo('testName1'), $this->equalTo('testKey1'));

        $this->queueMock->expects($this->at(2))
            ->method('bind')
            ->with($this->equalTo('testName2'), $this->equalTo('testKey2'));

        $c = new Consumer($this->channelMock, $this->queueMock);
        $binding = [
            ['name' => 'testName1', 'routing-keys' => 'testKey1'],
            ['name' => 'testName2', 'routing-keys' => 'testKey2'],
        ];
        $c->setQueueOptions(['name' => 'myQueue', 'binding' => $binding]);
        $c->consume(function(){return 'someMessage';});
    }

    /**
     * @dataProvider queueFlagProvider
     */
    public function testFlagOptions($passive, $durable, $autoDelete, $noWait, $flag)
    {
        $c = new Consumer($this->channelMock, $this->queueMock);

        $options = [
            'passive' => $passive,
            'durable' => $durable,
            'auto_delete' => $autoDelete,
            'nowait' => $noWait,
        ];
        $c->setQueueOptions($options);
        $this->assertEquals($flag, $c->getFlagsFromOptions());
    }

    public function queueFlagProvider()
    {
        return \Symfony\Component\Yaml\Yaml::parse(file_get_contents(__DIR__ . "/Fixtures/queueFlagProvider.yml"));
    }
}
