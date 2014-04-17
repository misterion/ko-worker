<?php
use Ko\RabbitMq\Producer;

class ProducerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $channelMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $exchangeMock;

    public function setUp()
    {
        $this->channelMock = $this->getMockBuilder('\AMQPChannel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->exchangeMock  = $this->getMockBuilder('\AMQPExchange')
            ->disableProxyingToOriginalMethods()
            ->disableOriginalConstructor()
            ->setMethods(['declareExchange', 'publish'])
            ->getMock();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExchangeOptionsShouldHaveName()
    {
        $p = new Producer($this->channelMock, $this->exchangeMock);
        $p->setExchangeOptions([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExchangeOptionsShouldHaveType()
    {
        $p = new Producer($this->channelMock, $this->exchangeMock);
        $p->setExchangeOptions(['name' => 'myExchange']);
    }

    public function testExchangeOptionsHasValidDefaultValues()
    {
        $expectedDefaultOptions = [
            'passive' => false,
            'durable' => true,
            'auto_delete' => false,
            'internal' => false,
            'nowait' => false,
            'arguments' => [],
            'ticket' => null,
            'binding' => [],
        ];

        $p = new Producer($this->channelMock, $this->exchangeMock);
        $this->assertEquals($expectedDefaultOptions, $p->getExchangeOptions());
    }

    public function testSetExchangeOptions()
    {
        $expectedOptions = [
            'name' => time(),
            'type' => 'direct',
        ];

        $p = new Producer($this->channelMock, $this->exchangeMock);
        $expectedDefaultOptions = $p->getExchangeOptions();
        $p->setExchangeOptions($expectedOptions);
        $this->assertEquals(array_merge($expectedDefaultOptions, $expectedOptions), $p->getExchangeOptions());
    }

    public function testPublishMessageShouldDeclareExchangeOnlyOnce()
    {
        $this->exchangeMock->expects($this->once())
            ->method('declareExchange');

        $p = new Producer($this->channelMock, $this->exchangeMock);
        $p->setExchangeOptions(['name' => 'myExchange', 'type' => 'someType']);
        $p->publish('someMessage');
        $p->publish('someMessage');
    }

    public function testPublishMessage()
    {
        $this->exchangeMock->expects($this->once())
            ->method('publish')
            ->with($this->equalTo('someMessage'));

        $p = new Producer($this->channelMock, $this->exchangeMock);
        $p->setExchangeOptions(['name' => 'myExchange', 'type' => 'someType']);
        $p->publish('someMessage');
    }

    /**
     * @dataProvider flagProvider
     */
    public function testFlagOptions($passive, $durable, $autoDelete, $noWait, $internal, $flag)
    {
        $p = new Producer($this->channelMock, $this->exchangeMock);
        $options = [
            'name' => 'test',
            'type' => 'direct',
            'passive' => false,
            'durable' => false,
            'auto_delete' => false,
            'nowait' => false,
            'internal' => false,
        ];
        $p->setExchangeOptions($options);
        $this->assertEquals(0, $p->getFlagsFromOptions());

        $options = [
            'name' => 'test',
            'type' => 'direct',
            'passive' => $passive,
            'durable' => $durable,
            'auto_delete' => $autoDelete,
            'nowait' => $noWait,
            'internal' => $internal,
        ];
        $p->setExchangeOptions($options);
        $this->assertEquals($flag, $p->getFlagsFromOptions());
    }

    public function flagProvider()
    {
        return [
            [true, false, false, false, false, AMQP_PASSIVE],
            [false, true, false, false, false, AMQP_DURABLE],
            [false, false, true, false, false, AMQP_AUTODELETE],
            [false, false, false, true, false, AMQP_NOWAIT],
            [false, false, false, false, true, AMQP_INTERNAL],
            [true, true, true, true, true, AMQP_PASSIVE | AMQP_DURABLE | AMQP_AUTODELETE | AMQP_NOWAIT | AMQP_INTERNAL],
        ];
    }
}
