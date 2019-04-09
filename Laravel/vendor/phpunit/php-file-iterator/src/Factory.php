<?php
/*
 * This file is part of php-file-iterator.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\FileIterator;

class Factory
{
    /**
     * @param array|string $paths
     * @param array|string $suffixes
     * @param array|string $prefixes
     * @param array        $exclude
     *
     * @return \AppendIterator
     */
    public function getFileIterator($paths, $suffixes = '', $prefixes = '', array $exclude = [])
    {
        if (\is_string($paths)) {
            $paths = [$paths];
        }

        $paths   = $this->getPathsAfterResolvingWildcards($paths);
        $exclude = $this->getPathsAfterResolvingWildcards($exclude);

        if (\is_string($prefixes)) {
            if ($prefixes !== '') {
                $prefixes = [$prefixes];
            } else {
                $prefixes = [];
            }
        }

        if (\is_string($suffixes)) {
            if ($suffixes !== '') {
                $suffixes = [$suffixes];
            } else {
                $suffixes = [];
            }
        }

		$path = $paths[0];
        /*$iterator = new \AppendIterator;

        foreach ($paths as $path) {
            if (\is_dir($path)) {
                $iterator->append(*/
                    return new Iterator(
                        $path,
                        new \RecursiveIteratorIterator(
                            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::FOLLOW_SYMLINKS | \RecursiveDirectoryIterator::SKIP_DOTS)
                        ),
                        $suffixes,
                        $prefixes,
                        $exclude
                    );
				/*);
            }
        }

        return $iterator;*/
    }

    protected function getPathsAfterResolvingWildcards(array $paths): array
    {
        return $paths;
    }
}
