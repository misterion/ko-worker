<?php
namespace Ko\RabbitMq;

use AMQPChannel;
use AMQPQueue;

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
     */
    public function consume(callable $callback, $flags = AMQP_NOPARAM)
    {
        if (!$this->queueDeclared) {
            $this->queueDeclare();
        }

        $this->queue->consume($callback, $flags);
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