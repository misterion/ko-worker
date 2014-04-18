<?php
/**
 * The MIT License
 *
 * Copyright (c) 2010 Nikolay Bondarenko
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
