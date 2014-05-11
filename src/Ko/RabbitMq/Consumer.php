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
namespace Ko\RabbitMq;

use AMQPChannel;
use AMQPQueue;

/**
 * Class Consumer
 *
 * @package Ko\RabbitMq
 * @copyright 2014 Nikolay Bondarenko. All rights reserved.
 * @author Nikolay Bondarenko <nikolay.bondarenko@syncopate.ru>
 * @version 1.0
 */
class Consumer
{
    /**
     * @var AMQPQueue
     */
    protected $queue;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    protected $queueDeclared = false;

    /**
     * @var array
     */
    protected $queueOptions = array(
        'name' => '',
        'passive' => false,
        'durable' => true,
        'exclusive' => false,
        'auto_delete' => false,
        'nowait' => false,
        'arguments' => [],
        'ticket' => null
    );

    public function __construct(AMQPChannel $channel, AMQPQueue $queue = null)
    {
        $this->channel = $channel;
        $this->queue = $queue;
    }

    /**
     * @param callable $callback
     * @param int $flags
     * @param string $consumerTag
     *
     * @throws \DomainException
     */
    public function consume(callable $callback, $flags = AMQP_NOPARAM, $consumerTag = null)
    {
        if (isset($this->queueOptions['qos_count'])) {
            if ($flags & AMQP_AUTOACK) {
                throw new \DomainException('Can not be used AUTOASK message and QOS');
            }

            $this->channel->setPrefetchCount($this->queueOptions['qos_count']);
        }

        if (!$this->queueDeclared) {
            $this->queueDeclare();
        }

        $this->queue->consume($callback, $flags, $consumerTag);
    }

    /**
     * @return int
     */
    public function getFlagsFromOptions()
    {
        $flags = ($this->queueOptions['passive'] ? AMQP_PASSIVE : AMQP_NOPARAM)
            | ($this->queueOptions['durable'] ? AMQP_DURABLE : AMQP_NOPARAM)
            | ($this->queueOptions['auto_delete'] ? AMQP_AUTODELETE : AMQP_NOPARAM)
            | ($this->queueOptions['nowait'] ? AMQP_NOWAIT : AMQP_NOPARAM);

        return $flags;
    }

    /**
     * @throws \InvalidArgumentException
     *
     * @param  array $options
     *
     * @return void
     */
    public function setQueueOptions(array $options = [])
    {
        $this->queueOptions = array_merge($this->queueOptions, $options);
    }

    /**
     * @return array
     */
    public function getQueueOptions()
    {
        return $this->queueOptions;
    }

    protected function queueDeclare()
    {
        if ($this->queue === null) {
            $this->queue = new \AMQPQueue($this->channel);
        }

        $this->queue->setName($this->queueOptions['name']);
        $this->queue->setArguments($this->queueOptions['arguments']);
        $this->queue->setFlags($this->getFlagsFromOptions());
        $this->queue->declareQueue();

        if (isset($this->queueOptions['binding']['name'])) {
            $this->queue->bind($this->queueOptions['binding']['name'], $this->queueOptions['binding']['routing-keys']);
        } else {
            foreach ($this->queueOptions['binding'] as $bindOpt) {
                $this->queue->bind($bindOpt['name'], $bindOpt['routing-keys']);
            }
        }

        $this->queueDeclared = true;
    }
} 