<?php

namespace Codeception\Extension;

use Codeception\Events;
use Codeception\Exception\ExtensionException;
use Codeception\Extension;
use Symfony\Component\Process\Process;

/**
 * Extension to run some processes before running tests.
 * Can be used to build files using webpack before running the tests.
 *
 * Can be configured in suite config:
 *
 * ```yaml
 * # acceptance.suite.yml
 * extensions:
 *     enabled:
 *         - Codeception\Extension\RunBefore:
 *             - yarn run build-dev
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
            if (!$command) {
                continue;
            }
            if (!is_int($key)) {
                continue; // configuration options
            }
            $process = new Process($command, $this->getRootDir(), null, null, null);
            $this->output->debug('[RunProcess] Starting '.$command);
            $process->run();
            $this->processes[] = $process;
        }
    }
}
