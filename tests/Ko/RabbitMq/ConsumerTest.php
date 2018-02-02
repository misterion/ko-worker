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
use Ko\RabbitMq\Consumer;
use PHPUnit\Framework\TestCase;

class ConsumerTest extends TestCase
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
            ->setMethods(['consume', 'bind', 'declareQueue', 'setPrefetchCount'])
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

    /**
     * @expectedException \DomainException
     */
    public function testExceptionWithAutoAskAndQosEnabled()
    {
        $c = new Consumer($this->channelMock, $this->queueMock);
        $c->setQueueOptions(
            ['name' => 'myQueue', 'binding' => ['name' => 'testName', 'routing-keys' => 'testKey'], 'qos_count' => 3]
        );

        $c->consume(function(){}, AMQP_AUTOACK);
    }

    public function testSetQosCountInChannel()
    {
        $this->channelMock->expects($this->once())
            ->method('setPrefetchCount')
            ->with($this->equalTo(3));

        $c = new Consumer($this->channelMock, $this->queueMock);
        $c->setQueueOptions(
            ['name' => 'myQueue', 'binding' => ['name' => 'testName', 'routing-keys' => 'testKey'], 'qos_count' => 3]
        );

        $c->consume(function(){});
    }
}
