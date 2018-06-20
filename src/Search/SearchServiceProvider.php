<?php

namespace brunaobh\Search\Search;

use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider {

    public function register()
    {
        $this->app->bind('search', 'brunaobh\Search\Search\Search');
    }
}