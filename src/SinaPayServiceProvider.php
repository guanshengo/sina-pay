<?php

namespace Guanshengo\SinaPay;

class SinaPayServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(SinaPay::class, function(){
            return new SinaPay(config('sina_pay'));
        });

        $this->app->alias(SinaPay::class, 'sinaPay');
    }

    public function provides()
    {
        return [SinaPay::class, 'sinaPay'];
    }
}