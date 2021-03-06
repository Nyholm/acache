<?php

/*
* This file is part of the ACache library.
*
* (c) Martin Rademacher <mano@radebatz.net>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Radebatz\ACache\Tests;

use Radebatz\ACache\FilesystemCache;

/**
 * FilesystemCache tests.
 */
class FilesystemCacheTest extends NamespaceCacheTest
{
    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        @rmdir($this->getTempDir());
    }

    /**
     * Get a temp directory.
     *
     * @param int $perms File permissions.
     *
     * @return string The directory name.
     */
    protected function getTempDir($perms = 0777)
    {
        $tempdir = __DIR__.'/_acache';
        if (is_dir($tempdir)) {
            chmod($tempdir, 0777);
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tempdir), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($it as $file) {
                if (in_array($file->getBasename(), array('.', '..'))) {
                    continue;
                } elseif ($file->isDir()) {
                    chmod($file->getPathname(), 0777);
                    rmdir($file->getPathname());
                } elseif ($file->isFile() || $file->isLink()) {
                    chmod($file->getPathname(), 0777);
                    unlink($file->getPathname());
                }
            }
            rmdir($tempdir);
        } elseif (file_exists($tempdir)) {
            unlink($tempdir);
        }

        mkdir($tempdir, $perms, true);

        return $tempdir;
    }

    /**
     * Cache provider.
     */
    public function cacheProvider()
    {
        return array(
            array(new FilesystemCache($this->getTempDir())),
        );
    }

    /**
     * Test not writeable folder.
     *
     * @expectedException InvalidArgumentException
     */
    public function testNotWriteable()
    {
        $dir = $this->getTempDir(0000);
        if (is_writeable($dir)) {
            $this->markTestSkipped('Seems chmod is not supported here.');
        }

        new FilesystemCache($dir);
    }

    /**
     * Test invalid directory.
     *
     * @expectedException InvalidArgumentException
     */
    public function testInvalid()
    {
        new FilesystemCache(tempnam(sys_get_temp_dir(), 'acache_'));
    }

    /**
     * Test directory.
     */
    public function testDirectory()
    {
        $dir = $this->getTempDir();
        $cache = new FilesystemCache($dir);
        $this->assertEquals($dir, $cache->getDirectory());
    }

    /**
     * Test permissions.
     */
    public function testPermissions()
    {
        $dir = $this->getTempDir();
        // force the cache to create the actual cache root folder
        $cacheRoot = $dir.'/foo/bar';
        $cache = new FilesystemCache($cacheRoot);
        $this->assertEquals($cacheRoot, $cache->getDirectory());

        // both foo and bar should have 0777 permissions
        foreach (array($dir.'/foo', $cacheRoot) as $path) {
            $actualFilePerms = (int) substr(sprintf('%o', fileperms($path)), -3);
            $this->assertEquals(777, $actualFilePerms);
        }
    }
}
