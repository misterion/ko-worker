<?php
namespace Ko\Worker;

use AMQPEnvelope;
use AMQPQueue;

interface ActionInterface
{
    public function execute(\AMQPEnvelope $envelope, \AMQPQueue $queue);
}