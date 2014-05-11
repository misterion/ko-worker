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

use AMQPConnection;

/**
 * Class ConnectionFactory
 *
 * Create and cache AMQPConnection by name from given config.
 *
 * @package Ko\RabbitMq
 * @copyright 2014 Nikolay Bondarenko. All rights reserved.
 * @author Nikolay Bondarenko <nikolay.bondarenko@syncopate.ru>
 * @version 1.0
 */
class ConnectionFactory
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var AMQPConnection[]
     */
    protected $registry;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get AMQPConnection by name.
     *
     * @param string $name
     *
     * @return AMQPConnection
     */
    public function getConnection($name)
    {
        if (!isset($this->config[$name])) {
            throw new \InvalidArgumentException("AMQP Connection config named \"$name\" does not exist.");
        }

        if (!isset($this->registry[$name])) {
            $this->registry[$name] = $this->createConnection($this->config[$name]);
            $this->registry[$name]->connect();
        }

        return $this->registry[$name];
    }

    protected function createConnection($credentials)
    {
        return new AMQPConnection($credentials);
    }
}