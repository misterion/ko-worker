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
}
