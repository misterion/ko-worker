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
    const VERSION = '1.1.0';

    /**
     * @var Getopt
     */
    protected $opts;

    /**
     * @var ProcessManager
     */
    protected $processManager;

    /**
     * @param ProcessManager $processManager
     */
    public function setProcessManager($processManager)
    {
        $this->processManager = $processManager;
    }

    public function run()
    {
        $this->buildCommandLineOptions();
        $this->parseCommandLine();
        $this->prepareMasterProcess();

        for ($i = 0; $i < (int)$this->opts['w']; $i++) {
            $closure = $this->getChildCallable();

            if (isset($this->opts['f'])) {
                $this->processManager->fork($closure);
            } else {
                $this->processManager->spawn($closure);
            }
        }

        $this->setProcessTitle('idle');
        $this->processManager->wait();

        exit(0);
    }

    protected function setProcessTitle($title)
    {
        $this->processManager->setProcessTitle('ko-worker[m|' . $this->opts['q'] . ']: ' . $title);
    }

    protected function buildCommandLineOptions()
    {
        $this->opts = new Getopt(
            [
                (new Option('w', 'workers', Getopt::REQUIRED_ARGUMENT))
                    ->setDefaultValue(1)
                    ->setDescription('Worker process count'),
                (new Option('c', 'config', Getopt::REQUIRED_ARGUMENT))
                    ->setDefaultValue('config.yaml')
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

    protected function parseCommandLine()
    {
        try {
            $this->opts->parse();
            if ($this->opts->count() === 0 || !file_exists($this->opts['config'])) {
                $this->printAbout();
                exit(0);
            }
        } catch (\UnexpectedValueException $e) {
            echo "Error: " . $e->getMessage() . PHP_EOL;
            $this->printAbout();
            exit(1);
        }
    }

    protected function printAbout()
    {
        echo 'Ko-worker version ' . self::VERSION . PHP_EOL;
        echo $this->opts->getHelpText();
    }

    protected function prepareMasterProcess()
    {
        $this->setProcessTitle('running');
        if (isset($this->opts['d'])) {
            $this->processManager->demonize();
        }
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
}