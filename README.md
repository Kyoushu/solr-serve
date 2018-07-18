# kyoushu/solr-serve

A library for managing instances of Solr to aid unit tests

## Usage Example

```php
$manager = new \Kyoushu\SolrServe\PackageManager('/tmp/solr-packages');
$package = $manager->createPackage('7.4.0')->download()->unpack();
$server = $package->createServer([
    'core_name' => 'foo',
    'dir' => '/tmp/foo',
    'port' => 9000
]);
$server->initialise();
$server->start();
// Do things with Solr here...
$server->stop();
```