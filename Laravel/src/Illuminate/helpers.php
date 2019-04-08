<?php

namespace Illuminate;

class Helpers
{
    /**
     * Get the evaluated view contents for the given view.
     *
     * @param  string  $view
     * @param  \Illuminate\Contracts\Support\Arrayable|array   $data
     * @param  array   $mergeData
     * @return \Illuminate\View\View
     */
    public static function view(string $view, array $data = [], array $mergeData = []): \Illuminate\View\View
    {
        return view($view, $data, $mergeData);
    }
}
