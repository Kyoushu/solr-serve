<?php

namespace Kyoushu\SolrServe;

class PackageManager
{

    /**
     * @var string
     */
    protected $dir;

    protected $mirrors = [
        'http://apache.mirror.anlx.net/lucene/solr',
        'http://apache.mirrors.nublue.co.uk/lucene/solr',
        'http://mirror.ox.ac.uk/sites/rsync.apache.org/lucene/solr',
        'http://mirror.vorboss.net/apache/lucene/solr',
        'http://mirrors.ukfast.co.uk/sites/ftp.apache.org/lucene/solr',
        'http://www.mirrorservice.org/sites/ftp.apache.org/lucene/solr'
    ];

    public function __construct(string $dir)
    {
        $this->dir = $dir;
    }

    public function getDir(): string
    {
        return $this->dir;
    }

    public function generateUrl(string $pathInfo, int $mirrorIndex = 0): string
    {
        return $this->mirrors[$mirrorIndex] . $pathInfo;
    }

    /**
     * @param string $version
     * @return Package
     * @throws Exception\PackageException
     */
    public function createPackage(string $version): Package
    {
        return new Package($this, $version);
    }

}