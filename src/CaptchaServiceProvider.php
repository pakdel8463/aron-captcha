<?php

namespace AronLabs\Captcha;

use Illuminate\Support\ServiceProvider;

class CaptchaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/aronlabs-captcha.php', 'aronlabs-captcha'
        );

        $this->app->singleton('aronlabs-captcha', function ($app) {
            return new Captcha(
                $app['session.store'],
                $app['config']->get('aronlabs-captcha')
            );
        });
    }

    public function boot(): void
    {
        $packageBasePath = realpath(__DIR__ . '/../');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'aronlabs-captcha');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        $this->loadTranslationsFrom($packageBasePath . '/resources/lang', 'aronlabs-captcha');

        if ($this->app->runningInConsole()) {

            $this->publishes([
                $packageBasePath . '/config/aronlabs-captcha.php' => config_path('aronlabs-captcha.php'),
            ], 'captcha-config');

            $this->publishes([
                $packageBasePath . '/resources/views' => resource_path('views/vendor/aronlabs-captcha'),
            ], 'captcha-views');

            $this->publishes([
                $packageBasePath . '/resources/fonts/default.ttf' => public_path('vendor/aronlabs-captcha/fonts/default.ttf'),
            ], 'captcha-fonts');

        }
    }
}
