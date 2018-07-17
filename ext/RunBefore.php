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

    public function _initialize()
    {
        if (!class_exists('Symfony\Component\Process\Process')) {
            throw new ExtensionException($this, 'symfony/process package is required');
        }
    }

    public function runProcess()
    {
        foreach ($this->config as $key => $command) {
            $process = new Process($command, $this->getRootDir(), null, null, null);
            $this->output->debug('[RunBefore] Starting ' . $command);
            $process->run();
            $this->output->debug('[RunBefore] Completing ' . $process->getCommandLine());
        }
    }
}
