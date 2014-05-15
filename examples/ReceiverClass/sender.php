#!/usr/bin/env php
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
require_once '../../vendor/autoload.php';

echo 'Press ctrl + c to stop execution' . PHP_EOL;

$config = \Symfony\Component\Yaml\Yaml::parse(file_get_contents('config.yaml'));

$broker = new Ko\AmqpBroker($config);
$producer = $broker->getProducer('user');

while (true) {
    $message = 'Hello world ' . time();
    echo 'Sending message `' . $message . '`' . PHP_EOL ;
    $producer->publish($message);
    sleep(1);
}
