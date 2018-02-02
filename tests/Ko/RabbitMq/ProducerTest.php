<?php
/**
 * The MIT License
 *
 * Copyright (c) 2014 Nikolay Bondarenko
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * PHP version 5.4
 *
 * @package Ko
 * @author Nikolay Bondarenko
 * @copyright 2014 Nikolay Bondarenko. All rights reserved.
 * @license MIT http://opensource.org/licenses/MIT
 */
use Ko\RabbitMq\Producer;
use PHPUnit\Framework\TestCase;

class ProducerTest extends TestCase
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

    public function testParamsOnPublishMessage()
    {
        $this->exchangeMock->expects($this->once())
            ->method('publish')
            ->with($this->equalTo('message'), $this->equalTo('routeKey'), $this->equalTo('flag'), $this->equalTo(['param1' => 'value1']));

        $p = new Producer($this->channelMock, $this->exchangeMock);
        $p->setExchangeOptions(['name' => 'myExchange', 'type' => 'someType']);
        $p->publish('message', 'routeKey', 'flag', ['param1' => 'value1']);
    }

    public function exchangeFlagProvider()
    {
        return \Symfony\Component\Yaml\Yaml::parse(file_get_contents(__DIR__ . "/Fixtures/exchangeFlagProvider.yml"));
    }
}
