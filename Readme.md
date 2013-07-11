ACache, a flexible PHP cache library
====================================

[![Build Status](https://travis-ci.org/DerManoMann/acache.png)](https://travis-ci.org/DerManoMann/acache)

ACache - another PHP cache library.

```php
<?php
require_once __DIR__.'/vendor/autoload.php';

$cache = new ACache\ArrayCache();

$cache->save('yin', 'yang');

echo 'yin and '.$cache->fetch('yin');
```

ACache requires PHP 5.3 or later.



## Features

ACache is inspired by the doctrine [cache][1] component.

Since some features were hard to add on top of that I ended up writing my own :)


### Namespaces

The `ACache\Cache` interface allows to explicitely use a namespace for any given id.

```php
<?php
include 'vendor/autoload.php';
define('MY_NAMESPACE', 'my');

$cache = new ACache\ArrayCache();

$cache->save('yin', 'yang', MY_NAMESPACE);

echo 'my yin and '.$cache->fetch('yin', MY_NAMESPACE).PHP_EOL;
```

While that works well it sometimes is desirable to do this a little bit more transparent (and save some typing).

```php
<?php
include 'vendor/autoload.php';
define('MY_NAMESPACE', 'my');

$cache = new ACache\ArrayCache();
$myCache = new ACache\NamespaceCache($cache, MY_NAMESPACE);

$myCache->save('yin', 'yang');

echo 'my yin and '.$myCache->fetch('yin').PHP_EOL;
// or, using the decorated cache directly
echo 'my yin and '.$cache->fetch('yin', MY_NAMESPACE).PHP_EOL;
```

Wrapping an existing cache instance in a `ACache\NamespaceCache` effectively allows to partition that cache without the need to 
carry the namespace around for all method calls.


### Multi level

Sometimes losing and re-building your cache due to a reboot or similar can be quite expensive. One way to cope with that is multi level caching.

A fast (non-persistent) cache is used as primary cache. If an entry cannot be found it will fall back to a persistent cache (cache, db).
Only if all configured cache instances are queried an entry would be declared as not found.

```php
<?php
include 'vendor/autoload.php';

$cache = new ACache\MultiLevelCache(array(
    new ACache\ArrayCache(),
    new ACache\FilesystemCache(__DIR__.'/cache')
));

// save both in ArrayCache and FilesystemCache
$cache->save('yin', 'yang');

// lookup will only use ArrayCache
echo 'my yin and '.$cache->fetch('yin').PHP_EOL;
```

Running the same code again will result in the same output, even if the `save()` call is commented out.

```php
<?php
include 'vendor/autoload.php';

$cache = new ACache\MultiLevelCache(array(
    new ACache\ArrayCache(),
    new ACache\FilesystemCache(__DIR__.'/cache')
));

// save both in ArrayCache and FilesystemCache
//$cache->save('yin', 'yang');

// lookup will only use ArrayCache
echo 'my yin and '.$cache->fetch('yin').PHP_EOL;
```

Here the `ACache\ArrayCache` instance will be empty and the `ACache\MultiLevelCache` will fall back to using the file based cache to lookup (and find)
the cache entry.


### Nesting

Both namespace and multi-level cache instances can be arbitrary nested.



## Installation

The recommended way to install ACache is [through
composer](http://getcomposer.org). Just create a `composer.json` file and
run the `php composer.phar install` command to install it:

    {
        "require": {
            "radebatz/acache": "1.0.*@dev"
        }
    }

Alternatively, you can download the [`acache.zip`][2] file and extract it.



## Tests

To run the test suite, you need [composer](http://getcomposer.org).

    $ php composer.phar install --dev
    $ vendor/bin/phpunit



## License

ACache is licensed under the MIT license.



[1]: https://github.com/doctrine/cache
[2]: https://github.com/DerManoMann/acache/archive/master.zip
