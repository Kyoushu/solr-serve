<?php

namespace Kyoushu\SolrServe;

use Kyoushu\SolrServe\Exception\SolrServeException;
use Symfony\Component\Process\Process;

class SolrProcess
{

    const REGEX_ERROR = '/error: [^\n]+/i';

    /**
     * @var string
     */
    protected $binPath;

    /**
     * @var int|null
     */
    protected $timeout;

    /**
     * @var string[]
     */
    protected $args;

    public function __construct(string $binPath, int $timeout = null)
    {
        $this->binPath = $binPath;
        $this->timeout = $timeout;
        $this->args = [];
    }

    public function addArgument(string $flag, string $value = null): self
    {
        $this->args[] = ['flag' => $flag, 'value' => $value];
        return $this;
    }

    protected function getArgumentsString()
    {
        $parts = [];
        foreach($this->args as $arg){
            if($arg['value']){
                $parts[] = sprintf('%s %s', $arg['flag'], escapeshellarg($arg['value']));
            }
            else{
                $parts[] = $arg['flag'];
            }
        }
        return implode(' ', $parts);
    }

    protected function getLastProcessError(Process $process): ?string
    {
        $message = $process->getOutput() . "\n" . $process->getErrorOutput();
        $lines = explode("\n", $message);
        $lines = array_reverse($lines);
        foreach($lines as $line){
            if(!preg_match(self::REGEX_ERROR, $line, $match)) continue;
            return $match[0];
        }
        $error = $process->getErrorOutput();
        if(!$error) $error = $process->getOutput();
        return $error;
    }

    public function generateCommand(): string
    {
        return sprintf('%s %s', $this->binPath, $this->getArgumentsString());
    }

    protected function onTerminate(Process $process)
    {
        if($process->isSuccessful()) return;
        $error = $this->getLastProcessError($process);
        throw new SolrServeException($error);
    }

    protected function createProcess(): Process
    {
        $cmd = $this->generateCommand();
        return new Process($cmd, null, null, null, $this->timeout ?? 5);
    }

    public function run()
    {
        $process = $this->createProcess();
        $process->run();
        $this->onTerminate($process);
    }

}