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

    /**
     * @dataProvider exchangeFlagProvider
     */
    public function testFlagOptions($passive, $durable, $autoDelete, $noWait, $internal, $flag)
    {
        $p = new Producer($this->channelMock, $this->exchangeMock);

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

    public function exchangeFlagProvider()
    {
        return \Symfony\Component\Yaml\Yaml::parse(file_get_contents(__DIR__ . "/Fixtures/exchangeFlagProvider.yml"));
    }
}
