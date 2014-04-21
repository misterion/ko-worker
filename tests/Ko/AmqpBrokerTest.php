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
use Ko\AmqpBroker;

class AmqpBrokerTest extends PHPUnit_Framework_TestCase
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
        $config = [
            'producers' => [
                'test' => []
            ],
        ];

        $mock = $this->getMockBuilder('\Ko\AmqpBroker')
            ->disableOriginalConstructor()
            ->setMethods(['createProducer'])
            ->getMock();

        $mock->expects($this->once())
            ->method('createProducer')
            ->with($this->equalTo('test'))
            ->will($this->returnValue(1));

        $mock->setConfig($config);
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
        $config = [
            'consumers' => [
                'test' => []
            ],
        ];

        $mock = $this->getMockBuilder('\Ko\AmqpBroker')
            ->disableOriginalConstructor()
            ->setMethods(['createConsumer'])
            ->getMock();

        $mock->expects($this->once())
            ->method('createConsumer')
            ->with($this->equalTo('test'))
            ->will($this->returnValue(1));

        $mock->setConfig($config);
        $mock->getConsumer('test');
        $mock->getConsumer('test');
    }
}
 
 