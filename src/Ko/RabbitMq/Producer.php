<?php
namespace Ko\RabbitMq;

use AMQPChannel;
use AMQPExchange;

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

    public function publish($message)
    {
        if (!$this->exchangeDeclared) {
            $this->exchangeDeclare();
        }

        $this->exchange->publish($message);
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