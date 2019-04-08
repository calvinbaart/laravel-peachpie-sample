<?php

namespace Illuminate\Foundation\Events;

use SplFileInfo;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class DiscoverEvents
{
    /**
     * Get all of the events and listeners by searching the given listener directory.
     *
     * @param  string  $listenerPath
     * @param  string  $basePath
     * @return array
     */
    public static function within($listenerPath, $basePath)
    {
        $listenerEvents = collect(static::getListenerEvents((new Finder)
                    ->files()
                    ->in($listenerPath), $basePath));

        return $listenerEvents->values()
                ->zip($listenerEvents->keys()->all())
                ->reduce(function ($carry, $listenerEventPair) {
                    $carry[$listenerEventPair[0]][] = $listenerEventPair[1];

                    return $carry;
                }, []);
    }

    /**
     * Get all of the listeners and their corresponding events.
     *
     * @param  iterable  $listeners
     * @param  string  $basePath
     * @return array
     */
    protected static function getListenerEvents($listeners, $basePath)
    {
        $listenerEvents = [];

        foreach ($listeners as $listener) {
            $listener = new ReflectionClass(
                static::classFromFile($listener, $basePath)
            );

            foreach ($listener->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (! Str::is('handle*', $method->name) ||
                    ! isset($method->getParameters()[0])) {
                    continue;
                }

                $listenerEvents[$listener->name.'@'.$method->name] =
                                optional($method->getParameters()[0]->getClass())->name;
            }
        }

        return array_filter($listenerEvents);
    }

    /**
     * Extract the class name from the given file path.
     *
     * @param  \SplFileInfo  $file
     * @param  string  $basePath
     * @return string
     */
    protected static function classFromFile(SplFileInfo $file, $basePath)
    {
        $class = trim(str_replace($basePath, '', $file->getRealPath()), DIRECTORY_SEPARATOR);

        return str_replace(DIRECTORY_SEPARATOR, '\\', ucfirst(Str::replaceLast('.php', '', $class)));
    }
}
