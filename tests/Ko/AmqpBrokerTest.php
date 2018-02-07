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
use Ko\AmqpBroker;
use PHPUnit\Framework\TestCase;

/**
 * Class AmqpBrokerTest
 *
 * @package Ko
 * @author Vadim Sabirov <pr0head@gmail.com>
 * @version 1.0.0
 */
class AmqpBrokerTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsExceptionOnUnknownProducer()
    {
        $broker = new AmqpBroker(['connections' => []]);
        $broker->getProducer('test');
    }

    public function testShouldCacheCreatedProducer()
    {
        $producerMock = $this->getMockBuilder('\Ko\RabbitMq\Producer')
            ->disableOriginalConstructor()
            ->getMock();

        $mock = $this->getMockBuilder('\Ko\AmqpBroker')
            ->disableOriginalConstructor()
            ->setMethods(['createProducer'])
            ->getMock();

        $mock->expects($this->once())
            ->method('createProducer')
            ->with($this->equalTo('test'))
            ->will($this->returnValue($producerMock));

        $mock->setConfig($this->getConfig());
        $mock->getProducer('test');
        $mock->getProducer('test');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsExceptionOnUnknownConsumer()
    {
        $broker = new AmqpBroker(['connections' => []]);
        $broker->getConsumer('test');
    }

    public function testShouldCacheCreatedConsumer()
    {
        $consumerMock = $this->getMockBuilder('\Ko\RabbitMq\Consumer')
            ->disableOriginalConstructor()
            ->getMock();

        $mock = $this->getMockBuilder('\Ko\AmqpBroker')
            ->disableOriginalConstructor()
            ->setMethods(['createConsumer'])
            ->getMock();

        $mock->expects($this->once())
            ->method('createConsumer')
            ->with($this->equalTo('test'))
            ->will($this->returnValue($consumerMock));

        $mock->setConfig($this->getConfig());
        $mock->getConsumer('test');
        $mock->getConsumer('test');
    }

    private function getConfig()
    {
        return [
            'producers' => [
                'test' => []
            ],
            'consumers' => [
                'test' => []
            ],
        ];
    }
}
 
 