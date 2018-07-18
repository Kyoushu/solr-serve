<?php

namespace Kyoushu\SolrServe\Exception;

class PackageException extends SolrServeException
{

    const ERROR_NOT_FOUND = 1;
    const ERROR_NOT_DOWNLOADED = 2;
    const ERROR_COULD_NOT_UNPACK = 3;
    const ERROR_DEPENDENCY_UNMET = 4;

}