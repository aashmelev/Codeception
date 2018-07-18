<?php

namespace Codeception\Extension;

use Codeception\Events;
use Codeception\Exception\ExtensionException;
use Codeception\Extension;
use Symfony\Component\Process\Process;

/**
 * Extension for execution of some processes before running tests.
 *
 * Can be configured in suite config:
 *
 * ```yaml
 * # acceptance.suite.yml
 * extensions:
 *     enabled:
 *         - Codeception\Extension\RunBefore:
 *             - independent_process_1
 *             -
 *                 - dependent_process_1_1
 *                 - dependent_process_1_2
 *             - independent_process_2
 *             -
 *                 - dependent_process_2_1
 *                 - dependent_process_2_2
 * ```
 *
 * HINT: you can use different configurations per environment.
 */
class RunBefore extends Extension
{
    protected $config = [];

    protected static $events = [
        Events::SUITE_BEFORE => 'runBefore'
    ];

    /** @var array[] */
    private $processes = [];

    public function _initialize()
    {
        if (!class_exists('Symfony\Component\Process\Process')) {
            throw new ExtensionException($this, 'symfony/process package is required');
        }
    }

    public function runBefore()
    {
        $this->runProcesses();
        $this->processMonitoring();
    }

    private function runProcesses()
    {
        foreach ($this->config as $item) {
            if (is_array($item)) {
                $currentCommand = array_shift($item);
                $followingCommands = $item;
            } else {
                $currentCommand = $item;
                $followingCommands = [];
            }

            $this->runProcess($currentCommand, $followingCommands);
        }
    }

    /**
     * @param string $command
     * @param string[] $following
     */
    private function runProcess($command, array $followingCommands)
    {
        $process = new Process($command, $this->getRootDir());
        $this->output->debug('[RunBefore] Starting ' . $command);
        $process->start();
        $this->addProcessToMonitoring($process, $followingCommands);
    }

    /**
     * @param string[] $followingCommands
     */
    private function addProcessToMonitoring(Process $process, array $followingCommands)
    {
        $this->processes[] = [
            'process' => $process,
            'following' => $followingCommands
        ];
    }

    private function processMonitoring()
    {
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

                $this->runFollowingCommand($process['following']);

                unset($this->processes[$key]);
            }
        }
    }

    /**
     * @param string[] $followingCommands
     */
    private function runFollowingCommand(array $followingCommands)
    {
        if (count($followingCommands) > 0) {
            $this->runProcess(array_shift($followingCommands), $followingCommands);
        }
    }

    private function isRunning(Process $process) {
        if ($process->isRunning()) {
            return true;
        }
        return false;
    }
}
