<?php
namespace Ko\Worker;

use Ko\Process;
use Ko\AmqpBroker;

class Child
{
    protected $config;

    protected $name;

    protected $executorClass;

    /**
     * @param mixed $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param mixed $executorClass
     * @throws \InvalidArgumentException
     */
    public function setExecutorClass($executorClass)
    {
        if (!is_subclass_of($executorClass, '\Ko\Worker\ActionInterface')) {
            throw new \InvalidArgumentException('Executor class ' . $executorClass . ' should implements Ko\Worker\ActionInterface');
        }

        $this->executorClass = $executorClass;
    }

    public function run(Process $process)
    {
        $process->setProcessTitle('ko-worker: child process');

        $broker = new AmqpBroker($this->config);
        $broker->getConsumer($this->name)->consume(function(\AMQPEnvelope $envelope, \AMQPQueue $queue) use ($process) {
            pcntl_signal_dispatch();

            $process->setProcessTitle('ko-worker: child wait executing envelope');

            /**
             * @var $executor ActionInterface
             */
            try {
                $executor = new $this->executorClass();
                $executor->execute($envelope, $queue);

                $queue->ack($envelope->getDeliveryTag());
            } catch (\Exception $e) {
                $queue->nack($envelope->getDeliveryTag());
            }

            $process->setProcessTitle('ko-worker: child wait new envelope');

            if ($process->isShouldShutdown()) {
                $queue->cancel();
                return false;
            }

            return true;
        });
    }
} 