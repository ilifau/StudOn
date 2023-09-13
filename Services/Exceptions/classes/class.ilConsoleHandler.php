<?php
// fau: consoleError - new class for error output on the console.

use Whoops\Handler\Handler;
use Whoops\Exception\Formatter;

class ilConsoleHandler extends Handler
{
    /**
     * Last missing method from HandlerInterface.
     *
     * @return null
     */
    public function handle()
    {
        echo Formatter::formatExceptionPlain($this->getInspector()) . "\n";
    }
}
