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
use Ko\Worker\Application;

class ApplicationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Application
     */
    private $app;

    public function setUp()
    {
        $this->app = new Application();
    }

    public function testSetRightProcessTitle()
    {
        $pmMock = $this->getProcessManagerMock();
        $pmMock->expects($this->at(0))
            ->method('setProcessTitle')
            ->with($this->equalTo('ko-worker[m|queueName]: running'));

        $appMock = $this->getMock('Ko\Worker\Application', ['runChilds', 'parseCommandLine', 'exitApp', 'getQueue']);
        $appMock->expects($this->atLeastOnce())
            ->method('getQueue')
            ->will($this->returnValue('queueName'));

        /** @var Application $appMock */
        /** @var Ko\ProcessManager $pmMock */
        $appMock->setProcessManager($pmMock)
            ->run();
    }

    private function getProcessManagerMock()
    {
        return $this->getMock('\Ko\ProcessManager', ['fork', 'spawn', 'setProcessTitle', 'wait', 'demonize']);
    }

    public function testDemonizeOptionEnabled()
    {
        $pmMock = $this->getProcessManagerMock();
        $pmMock->expects($this->once())
            ->method('demonize');

        $appMock = $this->getMock(
            'Ko\Worker\Application',
            ['runChilds', 'parseCommandLine', 'exitApp', 'shouldDemonize']
        );
        $appMock->expects($this->atLeastOnce())
            ->method('shouldDemonize')
            ->will($this->returnValue(true));

        /** @var Application $appMock */
        /** @var Ko\ProcessManager $pmMock */
        $appMock->setProcessManager($pmMock)
            ->run();
    }

    public function testDemonizeOptionDisabled()
    {
        $pmMock = $this->getProcessManagerMock();
        $pmMock->expects($this->never())
            ->method('demonize');

        $appMock = $this->getMock(
            'Ko\Worker\Application',
            ['runChilds', 'parseCommandLine', 'exitApp', 'shouldDemonize']
        );

        $appMock->expects($this->atLeastOnce())
            ->method('shouldDemonize')
            ->will($this->returnValue(false));

        /** @var Application $appMock */
        /** @var Ko\ProcessManager $pmMock */
        $appMock->setProcessManager($pmMock)
            ->run();
    }

    public function testForkOptionUsed()
    {
        $pmMock = $this->getProcessManagerMock();
        $pmMock->expects($this->exactly(2))
            ->method('fork');

        $appMock = $this->getMock(
            'Ko\Worker\Application',
            ['parseCommandLine', 'getChildCallable', 'exitApp', 'getWorkerCount', 'isForkMode']
        );

        $appMock->expects($this->once())
            ->method('getWorkerCount')
            ->will($this->returnValue(2));

        $appMock->expects($this->any())
            ->method('isForkMode')
            ->will($this->returnValue(true));

        $appMock->expects($this->any())
            ->method('getChildCallable')
            ->will($this->returnValue(function(){}));

        /** @var Application $appMock */
        /** @var Ko\ProcessManager $pmMock */
        $appMock->setProcessManager($pmMock)
            ->run();
    }

    public function testForkOptionUnused()
    {
        $pmMock = $this->getProcessManagerMock();
        $pmMock->expects($this->exactly(2))
            ->method('spawn');

        $appMock = $this->getMock(
            'Ko\Worker\Application',
            ['parseCommandLine', 'getChildCallable', 'exitApp', 'getWorkerCount', 'isForkMode']
        );
        $appMock->expects($this->once())
            ->method('getWorkerCount')
            ->will($this->returnValue(2));

        $appMock->expects($this->any())
            ->method('isForkMode')
            ->will($this->returnValue(false));

        $appMock->expects($this->any())
            ->method('getChildCallable')
            ->will($this->returnValue(function(){}));

        /** @var Application $appMock */
        /** @var Ko\ProcessManager $pmMock */
        $appMock->setProcessManager($pmMock)
            ->run();
    }
}
 