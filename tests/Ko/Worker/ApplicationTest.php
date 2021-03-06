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
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
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

        $appMock = $this->getMockBuilder('Ko\Worker\Application')
            ->setMethods(['runChilds', 'parseCommandLine', 'exitApp', 'getQueue'])
            ->getMock();
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
        return $this->createMock('\Ko\ProcessManager', ['fork', 'spawn', 'setProcessTitle', 'wait', 'demonize']);
    }

    public function testDemonizeOptionEnabled()
    {
        $pmMock = $this->getProcessManagerMock();
        $pmMock->expects($this->once())
            ->method('demonize');

        $appMock = $this->getMockBuilder('Ko\Worker\Application')
            ->setMethods(['runChilds', 'parseCommandLine', 'exitApp', 'shouldDemonize'])
            ->getMock();
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

        $appMock = $this->getMockBuilder('Ko\Worker\Application')
            ->setMethods(['runChilds', 'parseCommandLine', 'exitApp', 'shouldDemonize'])
            ->getMock();

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

        $appMock = $this->getMockBuilder('Ko\Worker\Application')
            ->setMethods(['parseCommandLine', 'getChildCallable', 'exitApp', 'getWorkerCount', 'isForkMode'])
            ->getMock();

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

        $appMock = $this->getMockBuilder('Ko\Worker\Application')
            ->setMethods(['parseCommandLine', 'getChildCallable', 'exitApp', 'getWorkerCount', 'isForkMode'])
            ->getMock();
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

    public function testGetSetName()
    {
        $this->app->setName('Name');
        $this->assertEquals('Name', $this->app->getName());
    }

    public function testGetSetVersion()
    {
        $this->app->setVersion('1.0.0');
        $this->assertEquals('1.0.0', $this->app->getVersion());
    }

    /**
     * @outputBuffering disabled
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Exit
     * @expectedExceptionCode 0
     */
    public function testAboutWithNameAndVersion()
    {
        global $argv;
        $argv = [];

        $appMock = $this->getMockBuilder('Ko\Worker\Application')
            ->setMethods(['exitApp'])
            ->getMock();

        $appMock->expects($this->once())
            ->method('exitApp')
            ->with($this->equalTo(Application::FAILED_EXIT))
            ->will($this->throwException(new \RuntimeException('Exit', Application::FAILED_EXIT)));

        $expectedAbout = <<< ABOUT
MyApp version 1.0.0 (ko-worker version 1.2.0)
Usage:  [options] [operands]
Options:
  -w, --workers <arg>     Worker process count
  -c, --config <arg>      Worker configuration file
  -q, --queue <arg>       Consumer queue name
  -d, --demonize          Run application as daemon
  -f, --fork              Use fork instead of spawn child process

ABOUT;
        $this->expectOutputString($expectedAbout);

        /** @var \Ko\ProcessManager $pmMock */
        $pmMock = $this->getProcessManagerMock();

        /** @var Application $appMock */
        $appMock->setName('MyApp')
            ->setVersion('1.0.0')
            ->setProcessManager($pmMock)
            ->run();
    }
}