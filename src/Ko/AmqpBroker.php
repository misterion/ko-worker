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
namespace Ko;

use Ko\RabbitMq\ConnectionFactory;
use Ko\RabbitMq\Consumer;
use Ko\RabbitMq\Producer;

use AMQPChannel;

/**
 * Class AmqpBroker
 *
 * @package Ko
 * @author Nikolay Bondarenko <misterionkell@gmail.com>
 * @version 1.0.0
 */
class AmqpBroker
{
    protected $config;

    /**
     * @var ConnectionFactory
     */
    protected $connFactory;

    /**
     * @var Producer[]
     */
    protected $producers;

    /**
     * @var Consumer[]
     */
    protected $consumers;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connFactory = new ConnectionFactory($config['connections']);
    }

    /**
     * @param mixed $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @param $name
     *
     * @return Producer
     * @throws \InvalidArgumentException
     */
    public function getProducer($name)
    {
        if (!isset($this->config['producers'][$name])) {
            throw new \InvalidArgumentException();
        }

        return $this->createProducer($name);
    }

    protected function createProducer($name)
    {
        if (isset($this->producers[$name])) {
            return $this->producers[$name];
        }

        $conf = $this->config['producers'][$name];
        $conn = $this->connFactory->getConnection($conf['connection']);

        $p = new Producer(new AMQPChannel($conn));
        $p->setExchangeOptions($conf['exchange_options']);

        $this->producers[$name] = $p;
        return $p;
    }

    public function getConsumer($name)
    {
    }
}