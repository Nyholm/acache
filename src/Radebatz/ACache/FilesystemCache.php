<?php

/*
* This file is part of the ACache library.
*
* (c) Martin Rademacher <mano@radebatz.net>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Radebatz\ACache;

use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Filesystem cache.
 *
 * @author Martin Rademacher <mano@radebatz.net>
 */
class FilesystemCache implements CacheInterface
{
    protected $directory;
    protected $mode;
    protected $keySanitiser;

    /**
     * Create instance.
     *
     * @param string   $directory    The root directory of this cache.
     * @param int      $mode         The permissions to be used for all directories created.
     * @param callable $keySanitiser Optional sanitizer to avoid invalid filenames.
     */
    public function __construct($directory, $mode = 0777, $keySanitiser = null)
    {
        $this->mkdir($directory, $mode);
        if (!is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('The directory "%s" does not exist and could not be created.', $directory));
        }

        if (!is_writable($directory)) {
            throw new InvalidArgumentException(sprintf('The directory "%s" is not writable.', $directory));
        }

        $this->directory = realpath($directory);
        $this->mode = $mode;
        $this->keySanitiser = is_callable($keySanitiser) ? $keySanitiser : function ($key) { return $key; };
    }

    /**
     * {@inheritDoc}
     */
    public function available()
    {
        return is_dir($this->directory) && is_writeable($this->directory);
    }

    /**
     * Get the configured cache directory.
     *
     * @return string The cache directory path.
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Recursive mkdir.
     *
     * @param string $path The path.
     * @param int    $mode The permissions to be used for all directories created.
     */
    protected function mkdir($path, $mode)
    {
        if (is_dir($path)) {
            return;
        }

        $this->mkdir(dirname($path), $mode);
        if (!file_exists($path)) {
            mkdir($path, $mode);
            chmod($path, $mode);
        }
    }

    /**
     * Convert an id into a filename.
     *
     * @param string       $id        The id.
     * @param string|array $namespace Optional namespace.
     *
     * @return string The filename.
     */
    protected function getFilenameForId($id, $namespace)
    {
        $path = array_merge((array) $namespace, str_split(md5($id), 8));

        return implode(DIRECTORY_SEPARATOR, array($this->directory, implode(DIRECTORY_SEPARATOR, $path), $id));
    }

    /**
     * Get a cache entry for the given id.
     *
     * @param string       $id        The id.
     * @param string|array $namespace Optional namespace.
     * @param boolean      $full      Flag to indicate whether to include data loading or meta data only.
     *
     * @return array The cache entry or <code>null</code>.
     */
    protected function getEntryForId($id, $namespace, $full = false)
    {
        $filename = $this->getFilenameForId($id, $namespace);

        if (!is_file($filename)) {
            return;
        }

        $expires = -1;
        $data = '';

        $fh = fopen($filename, 'r');

        // load  expires
        if (false !== ($line = fgets($fh))) {
            $expires = (integer) $line;
        }

        if ($full) {
            // load data too
            while (false !== ($line = fgets($fh))) {
                $data .= $line;
            }
        }

        fclose($fh);

        return array('data' => unserialize($data), 'expires' => $expires);
    }

    /**
     * {@inheritDoc}
     */
    public function fetch($id, $namespace = null)
    {
        $id = call_user_func($this->keySanitiser, $id);

        if (!$this->contains($id, $namespace)) {
            return;
        }

        $entry = $this->getEntryForId($id, $namespace, true);

        return $entry['data'];
    }

    /**
     * {@inheritDoc}
     */
    public function contains($id, $namespace = null)
    {
        $id = call_user_func($this->keySanitiser, $id);

        if (!$entry = $this->getEntryForId($id, $namespace, false)) {
            return false;
        }

        return 0 == $entry['expires'] || $entry['expires'] > time();
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeToLive($id, $namespace = null)
    {
        $id = call_user_func($this->keySanitiser, $id);

        if (!$entry = $this->getEntryForId($id, $namespace, false)) {
            return false;
        }

        return $entry['expires'] ? ($entry['expires'] - time()) : 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultTimeToLive()
    {
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function save($id, $data, $lifeTime = null, $namespace = null)
    {
        $id = call_user_func($this->keySanitiser, $id);

        $filename = $this->getFilenameForId($id, $namespace);
        $filepath = pathinfo($filename, PATHINFO_DIRNAME);

        if (!is_dir($filepath)) {
            $this->mkdir($filepath, 0777);
        }

        if (!is_dir($filepath)) {
            return false;
        }

        $lifeTime = null !== $lifeTime ? (int) $lifeTime : $this->getDefaultTimeToLive();
        $expires = $lifeTime ? (time() + $lifeTime) : 0;

        return (bool) file_put_contents($filename, $expires.PHP_EOL.serialize($data));
    }

    /**
     * {@inheritDoc}
     */
    public function delete($id, $namespace = null)
    {
        $id = call_user_func($this->keySanitiser, $id);

        return @unlink($this->getFilenameForId($id, $namespace));
    }

    /**
     * {@inheritDoc}
     */
    public function flush($namespace = null)
    {
        $namespace = implode(DIRECTORY_SEPARATOR, array_merge(array($this->directory), (array) $namespace));

        if (!file_exists($namespace)) {
            return true;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($namespace));
        foreach ($iterator as $name => $file) {
            if ($file->isFile()) {
                @unlink($name);
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getStats()
    {
        $size = 0;
        if (is_dir($this->directory)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->directory));
            foreach ($iterator as $name => $file) {
                if ($file->isFile()) {
                    ++$size;
                }
            }
        }

        return array(
            CacheInterface::STATS_SIZE => $size,
        );
    }
}
