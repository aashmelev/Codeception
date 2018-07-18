<?php

namespace Codeception\Extension;

use Codeception\Events;
use Codeception\Exception\ExtensionException;
use Codeception\Extension;
use Symfony\Component\Process\Process;

/**
 * Extension for execution of some processes before running tests.
 * Can be used to build files using webpack, etc.
 *
 * Can be configured in suite config:
 *
 * ```yaml
 * # acceptance.suite.yml
 * extensions:
 *     enabled:
 *         - Codeception\Extension\RunBefore:
 *             - async_proc1
 *             -
 *                 - sync_proc1
 *                 - sync_proc2
 *             - async_proc2
 * ```
 *
 * HINT: you can use different configurations per environment.
 */
class RunBefore extends Extension
{
    public $config = [];

    static $events = [
        Events::SUITE_BEFORE => 'runProcess'
    ];

    /** @var Process[] */
    protected $processes = [];

    public function _initialize()
    {
        if (!class_exists('Symfony\Component\Process\Process')) {
            throw new ExtensionException($this, 'symfony/process package is required');
        }
    }

    public function runProcess()
    {
        $this->processes = [];
        foreach ($this->config as $key => $command) {
            if (is_array($command)) {
                $currentCommand = array_shift($command);
                $process = new Process($currentCommand, $this->getRootDir(), null, null, null);
                $this->output->debug('[RunBefore] Starting ' . $currentCommand);
                $process->start();
                $nextProcesses = $command;
            } else {
                $process = new Process($command, $this->getRootDir(), null, null, null);
                $this->output->debug('[RunBefore] Starting ' . $command);
                $process->start();
                $nextProcesses = [];
            }

            $this->processes[] = [
                'process' => $process,
                'next' => $nextProcesses
            ];
        }

        while (count($this->processes) !== 0) {
            $this->checkProcesses();
            sleep(1);
        }
    }

    private function checkProcesses()
    {
        foreach ($this->processes as $key => $process) {
            if (!$this->isRunning($process['process'])) {
                $this->output->debug('[RunBefore] Completing ' . $process['process']->getCommandLine());

                if ($currentCommand = array_shift($process['next'])) {
                    $currentProcess = new Process($currentCommand, $this->getRootDir(), null, null, null);
                    $this->output->debug('[RunBefore] Starting ' . $currentCommand);
                    $currentProcess->start();
                    $nextProcesses = $process['next'];

                    $this->processes[] = [
                        'process' => $currentProcess,
                        'next' => $nextProcesses
                    ];
                }

                unset($this->processes[$key]);
            }
        }
    }

    private function isRunning(Process $process) {
        if ($process->isRunning()) {
            return true;
        }

        return false;
    }
}
