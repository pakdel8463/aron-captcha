<?php

namespace AronLabs\Captcha;

use Illuminate\Support\ServiceProvider;

class CaptchaServiceProvider extends ServiceProvider
{
    public function register(): void
    {

        // MERGE CONFIG
        $this->mergeConfigFrom(
            __DIR__.'/../config/captcha.php', 'aron-captcha'
        );

        // BIND CAPTCHA CLASS
       $this->app->singleton('aron-captcha', function ($app) {
            return new Captcha(
                $app['session.store'],
                $app['config']->get('aron-captcha')
            );
        });
    }

    public function boot(): void
    {
        $packageBasePath = realpath(__DIR__ . '/../');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'aron-captcha');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        $this->loadTranslationsFrom($packageBasePath . '/resources/lang', 'aron-captcha');

        if ($this->app->runningInConsole()) {





            $this->publishes([
                $packageBasePath . '/config/captcha.php' => config_path('aron-captcha.php'),
            ], 'captcha-config');


            $this->publishes([
                $packageBasePath . '/resources/views' => resource_path('views/vendor/aron-captcha'),
            ], 'captcha-views');


            $this->publishes([
                $packageBasePath . '/resources/fonts/default.ttf' => public_path('vendor/aron-captcha/fonts/default.ttf'),
            ], 'captcha-fonts');

            $this->loadTranslationsFrom($packageBasePath . '/resources/lang', 'aron-captcha');
        }
    }
}
