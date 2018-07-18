<?php

namespace Kyoushu\SolrServe;

use Kyoushu\SolrServe\Exception\PackageException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class Package
{

    /**
     * @var string
     */
    protected $version;

    /**
     * @var PackageManager
     */
    protected $manager;

    public function __construct(PackageManager $manager, string $version)
    {
        $this->manager = $manager;
        $this->version = $version;
    }

    public function getBinDir(): string
    {
        return sprintf('%s/solr-%s/bin', $this->getUnpackDir(), $this->version);
    }

    public function getBinPath(): string
    {
        return sprintf('%s/solr', $this->getBinDir());
    }

    public function getServerDir(): string
    {
        return sprintf('%s/solr-%s/server', $this->getUnpackDir(), $this->version);
    }

    protected function getDir(): string
    {
        return sprintf('%s/%s', $this->manager->getDir(), $this->version);
    }

    protected function getTgzFilename(): string
    {
        return sprintf('solr-%s.tgz', $this->version);
    }

    protected function getTgzPath(): string
    {
        return sprintf('%s/solr-%s.tgz', $this->getDir(), $this->version);
    }

    protected function getUnpackDir(): string
    {
        return sprintf('%s/unpack', $this->getDir());
    }

    protected function isDownloaded(): bool
    {
        return file_exists($this->getTgzPath());
    }

    public function assertDownloaded()
    {
        if($this->isDownloaded()) return;
        throw new PackageException(sprintf('Solr %s has not been downloaded yet', $this->version), PackageException::ERROR_DEPENDENCY_UNMET);
    }

    public function isUnpacked(): bool
    {
        return file_exists($this->getBinPath());
    }

    public function assertUnpacked()
    {
        if($this->isUnpacked()) return;
        throw new PackageException(sprintf('Solr %s has not been unpacked yet', $this->version), PackageException::ERROR_DEPENDENCY_UNMET);
    }

    public function download(int $mirrorIndex = 0, bool $force = false): self
    {
        $tgzPath = $this->getTgzPath();
        if(file_exists($tgzPath) && !$force) return $this;

        $url = $this->manager->generateUrl(sprintf('/%s/%s', $this->version, basename($tgzPath)), $mirrorIndex);
        $tgzDir = dirname($tgzPath);
        if(!file_exists($tgzDir)) mkdir($tgzDir, 0777, true);

        $fs = new Filesystem();
        $fs->copy($url, $tgzPath);

        return $this;
    }

    public function unpack(bool $force = false): self
    {
        if($this->isUnpacked() && !$force) return $this;

        $this->assertDownloaded();

        $unpackDir = $this->getUnpackDir();

        if(!file_exists($unpackDir)) mkdir($unpackDir, 0777, true);

        $cmd = sprintf('tar zxf %s -C %s', escapeshellarg($this->getTgzPath()), escapeshellarg($unpackDir));
        $process = new Process($cmd);
        $process->run();
        if(!$process->isSuccessful()){
            throw new PackageException($process->getErrorOutput(), PackageException::ERROR_COULD_NOT_UNPACK);
        }
        return $this;
    }

    public function createServer(array $options): Server
    {
        return new Server($this, $options);
    }

}