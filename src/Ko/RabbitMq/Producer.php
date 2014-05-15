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
use AMQPExchange;

/**
 * Class Producer
 *
 * @package Ko\RabbitMq
 * @copyright 2014 Nikolay Bondarenko. All rights reserved.
 * @author Nikolay Bondarenko <nikolay.bondarenko@syncopate.ru>
 * @version 1.0
 */
class Producer
{
    /**
     * @var \AMQPExchange
     */
    protected $exchange;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * @var bool
     */
    protected $exchangeDeclared = false;

    /**
     * @var array
     */
    protected $exchangeOptions = [
        'passive' => false,
        'durable' => true,
        'auto_delete' => false,
        'internal' => false,
        'nowait' => false,
        'arguments' => [],
        'ticket' => null,
        'binding' => [],
    ];

    public function __construct(\AMQPChannel $channel, AMQPExchange $exchange = null)
    {
        $this->exchange = $exchange;
        $this->channel = $channel;
    }

    /**
     * Publish a message to an exchange.
     *
     * Publish a message to the exchange represented by the AMQPExchange object.
     *
     * @param string $message The message to publish.
     * @param string $routingKey The optional routing key to which to publish to.
     * @param integer $flags One or more of AMQP_MANDATORY and AMQP_IMMEDIATE.
     * @param array $attributes One of content_type, content_encoding,
     *                          message_id, user_id, app_id, delivery_mode,
     *                          priority, timestamp, expiration, type
     *                          or reply_to, headers.
     *
     * @throws \AMQPExchangeException   On failure.
     * @throws \AMQPChannelException    If the channel is not open.
     * @throws \AMQPConnectionException If the connection to the broker was lost.
     */
    public function publish($message, $routingKey = null, $flags = AMQP_NOPARAM, array $attributes = [])
    {
        if (!$this->exchangeDeclared) {
            $this->exchangeDeclare();
        }

        $this->exchange->publish($message, $routingKey, $flags, $attributes);
    }

    protected function exchangeDeclare()
    {
        if ($this->exchange === null) {
            $this->exchange = new AMQPExchange($this->channel);
        }

        $this->exchange->setName($this->exchangeOptions['name']);
        $this->exchange->setType($this->exchangeOptions['type']);
        $this->exchange->setFlags($this->getFlagsFromOptions());
        $this->exchange->setArguments($this->exchangeOptions['arguments']);
        $this->exchange->declareExchange();

        foreach ($this->exchangeOptions['binding'] as $bindOpt) {
            $this->exchange->bind($bindOpt['name'], $bindOpt['routing-keys']);
        }

        $this->exchangeDeclared = true;
    }

    /**
     * @return int
     */
    public function getFlagsFromOptions()
    {
        $flags = ($this->exchangeOptions['passive'] ? AMQP_PASSIVE : AMQP_NOPARAM)
            | ($this->exchangeOptions['durable'] ? AMQP_DURABLE : AMQP_NOPARAM)
            | ($this->exchangeOptions['auto_delete'] ? AMQP_AUTODELETE : AMQP_NOPARAM)
            | ($this->exchangeOptions['internal'] ? AMQP_INTERNAL : AMQP_NOPARAM)
            | ($this->exchangeOptions['nowait'] ? AMQP_NOWAIT : AMQP_NOPARAM);

        return $flags;
    }

    /**
     * @return array
     */
    public function getExchangeOptions()
    {
        return $this->exchangeOptions;
    }

    /**
     * @throws \InvalidArgumentException
     *
     * @param  array $options
     *
     * @return void
     */
    public function setExchangeOptions(array $options = [])
    {
        if (empty($options['name'])) {
            throw new \InvalidArgumentException('You must provide an exchange name');
        }

        if (empty($options['type'])) {
            throw new \InvalidArgumentException('You must provide an exchange type');
        }

        $this->exchangeOptions = array_merge($this->exchangeOptions, $options);
    }
}