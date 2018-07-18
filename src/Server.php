<?php

namespace Kyoushu\SolrServe;

use Kyoushu\SolrServe\Exception\ServerException;
use SebastianBergmann\CodeCoverage\Node\File;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Process;

class Server
{

    const REGEX_CONFIG_XML_PLACEHOLDER = '/\{\{\s+?(?<key>[^\}\s]+)\s+?\}\}/';

    /**
     * @var Package
     */
    protected $package;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var bool
     */
    protected $ready;

    public function __construct(Package $package, array $options = [])
    {
        $this->package = $package;
        $this->options = $options;
        $this->ready = false;

        $this->options = $this->createOptionsResolver()->resolve($options);

        $this->package->assertUnpacked();
    }

    protected function createOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();

        $resolver->setRequired([
            'port',
            'core_name',
            'dir'
        ]);

        return $resolver;
    }

    /**
     * @return string Path to config file
     */
    protected function createConfigFile(): string
    {
        $fs = new Filesystem();
        $sourcePath = __DIR__ . '/Resources/solr.xml';
        $path = $this->getConfigPath();
        $dir = dirname($path);
        if(!$fs->exists($dir)) $fs->mkdir($dir);
        $fs->copy($sourcePath, $path);
        return $path;
    }

    protected function getDirs()
    {
        return [
            'server' => $this->package->getServerDir(),
            'data' => sprintf('%s/data', $this->options['dir']),
            'cores' => sprintf('%s/cores', $this->options['dir'])
        ];
    }

    protected function getConfigPath(): string
    {
        $dirs = $this->getDirs();
        return sprintf('%s/solr.xml', $dirs['cores']);
    }

    protected function createProcess(int $timeout = null): SolrProcess
    {
        return new SolrProcess($this->package->getBinPath(), $timeout);
    }

    protected function assertReady()
    {
        if($this->ready) return;
        throw new ServerException(sprintf(
            'Server is not ready, %s::initialise() must be called first',
            static::class
        ));
    }

    public function initialise(): self
    {
        if($this->ready) throw new ServerException('Server has already been initialised');

        $fs = new Filesystem();

        foreach($this->getDirs() as $dir){
            if(!$fs->exists($dir)) $fs->mkdir($dir);
        }

        $this->createConfigFile();

        $this->ready = true;

        return $this;
    }

    protected function coreExists(): bool
    {
        $dirs = $this->getDirs();
        $dir = sprintf('%s/%s', $dirs['cores'], $this->options['core_name']);
        return file_exists($dir);
    }

    protected function createCore(): self
    {
        $this->createProcess()
            ->addArgument('create_core')
            ->addArgument('-c', $this->options['core_name'])
            ->addArgument('-p', $this->options['port'])
            ->run()
        ;

        return $this;
    }

    public function isRunning(): bool
    {
        return $this->getPid() !== null;
    }

    public function stop(): self
    {
        if(!$this->isRunning()) return $this;

        $pid = $this->getPid();
        $process = new Process(sprintf('kill %s', $pid));
        $process->run();
        if(!$process->isSuccessful()){
            throw new ServerException(sprintf(
                'Could not stop server: %s',
                $process->getErrorOutput()
            ));
        }

        return $this;
    }

    public function getPid(): ?int
    {
        return ListeningPortPidFinder::findPidByListeningPort($this->options['port']);
    }

    public function start()
    {
        $this->assertReady();

        $this->stop();

        $dirs = $this->getDirs();

        $this->createProcess()
            ->addArgument('start')
            ->addArgument('-t', $dirs['data'])
            ->addArgument('-s', $dirs['cores'])
            ->addArgument('-d', $dirs['server'])
            ->addArgument('-p', $this->options['port'])
            ->run()
        ;

        if(!$this->coreExists()){
            $this->createCore();
        }
    }

}