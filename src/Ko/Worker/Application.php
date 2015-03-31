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
namespace Ko\Worker;

use Closure;
use Ko\Process;
use Ko\ProcessManager;
use Symfony\Component\Yaml\Yaml;
use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

/**
 * Class Application
 *
 * @package Ko\Worker
 * @copyright 2014 Nikolay Bondarenko. All rights reserved.
 * @author Nikolay Bondarenko <nikolay.bondarenko@syncopate.ru>
 * @version 1.0
 */
class Application
{
    const VERSION = '1.2.0';

    const SUCCESS_EXIT = 1;

    const FAILED_EXIT = 0;

    /**
     * @var Getopt
     */
    protected $opts;

    /**
     * @var ProcessManager
     */
    protected $processManager;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var string
     */
    protected $name;

    public function __construct()
    {
        $this->app = 'App';
        $this->version = '0.0.1';

        $this->buildCommandLineOptions();
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set custom application version
     *
     * @param string $version
     *
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
      Set custom application name
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    protected function buildCommandLineOptions()
    {
        $this->opts = new Getopt(
            [
                (new Option('w', 'workers', Getopt::REQUIRED_ARGUMENT))
                    ->setDefaultValue(1)
                    ->setDescription('Worker process count'),
                (new Option('c', 'config', Getopt::REQUIRED_ARGUMENT))
                    ->setDescription('Worker configuration file'),
                (new Option('q', 'queue', Getopt::REQUIRED_ARGUMENT))
                    ->setDescription('Consumer queue name'),
                (new Option('d', 'demonize', Getopt::NO_ARGUMENT))
                    ->setDescription('Run application as daemon'),
                (new Option('f', 'fork', Getopt::NO_ARGUMENT))
                    ->setDescription('Use fork instead of spawn child process'),
            ]
        );
    }

    /**
     * @param ProcessManager $processManager
     *
     * @return $this
     */
    public function setProcessManager(ProcessManager $processManager)
    {
        $this->processManager = $processManager;

        return $this;
    }

    public function run()
    {
        $this->setProcessTitle('running');

        $this->parseCommandLine();

        $this->demonize();

        $this->runChilds();

        $this->setProcessTitle('idle');
        $this->processManager->wait();

        $this->exitApp(self::SUCCESS_EXIT);
    }

    protected function setProcessTitle($title)
    {
        $this->processManager->setProcessTitle('ko-worker[m|' . $this->getQueue() . ']: ' . $title);
    }

    protected function getQueue()
    {
        return $this->opts['q'];
    }

    protected function parseCommandLine()
    {
        try {
            $this->opts->parse();
        } catch (\UnexpectedValueException $e) {
            echo "Error: " . $e->getMessage() . PHP_EOL . PHP_EOL;
            $this->printAbout();
            $this->exitApp(self::FAILED_EXIT);
        }

        if (!$this->isValidCommandLine()) {
            $this->printAbout();
            $this->exitApp(self::FAILED_EXIT);
        }
    }

    protected function printAbout()
    {
        echo sprintf("%s version %s (ko-worker version %s)", $this->name, $this->version, self::VERSION);
        echo PHP_EOL;
        echo $this->opts->getHelpText();
    }

    protected function exitApp($code)
    {
        exit($code);
    }

    protected function isValidCommandLine()
    {
        return $this->opts->count() !== 0
        && file_exists($this->opts['config']);
    }

    protected function demonize()
    {
        if ($this->shouldDemonize()) {
            $this->processManager->demonize();
        }
    }

    protected function shouldDemonize()
    {
        return isset($this->opts['d']);
    }

    protected function runChilds()
    {
        $count = $this->getWorkerCount();
        for ($i = 0; $i < $count; $i++) {
            $closure = $this->getChildCallable();

            if ($this->isForkMode()) {
                $this->processManager->fork($closure);
            } else {
                $this->processManager->spawn($closure);
            }
        }
    }

    public function getWorkerCount()
    {
        return (int)$this->opts['w'];
    }

    protected function getChildCallable()
    {
        $config = Yaml::parse(file_get_contents($this->opts['config']));
        $queue = $this->opts['q'];

        if (empty($queue)) {
            throw new \DomainException('You must declare queue name');
        }

        if (!isset($config['consumers'][$queue]['class'])) {
            throw new \DomainException('You must declare a class for the job');
        }

        return Closure::bind(
            function (Process $process) use ($config, $queue) {
                $child = new Child();
                $child->setConfig($config);
                $child->setName($queue);
                $child->setExecutorClass($config['consumers'][$queue]['class']);
                $child->run($process);
            },
            null
        );
    }

    public function isForkMode()
    {
        return isset($this->opts['f']);
    }
}