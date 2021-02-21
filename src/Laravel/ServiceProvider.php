<?php

namespace CodeDistortion\RealNum\Laravel;

use CodeDistortion\RealNum\Exceptions\InvalidLocaleException;
use CodeDistortion\RealNum\Percent;
use CodeDistortion\RealNum\RealNum;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Events\EventDispatcher;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * RealNum & Percent ServiceProvider for Laravel.
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Service-provider register method.
     *
     * @return void
     */
    public function register(): void
    {
        // Needed for Laravel < 5.3 compatibility
    }

    /**
     * Service-provider boot method.
     *
     * @return void
     * @throws InvalidLocaleException Thrown when the locale cannot be resolved.
     */
    public function boot(): void
    {
        $this->initialiseConfig();
        $this->setDefaults();
        $this->localeListen();
    }



    /**
     * Initialise the config settings file.
     *
     * @return void
     */
    protected function initialiseConfig(): void
    {
        // initialise the config
        $configPath = __DIR__ . '/../../config/config.php';
        $this->mergeConfigFrom($configPath, 'code-distortion.realnum');

        // allow the default config to be published
        if (
            (!$this->app->environment('testing'))
            && ($this->app->runningInConsole())
        ) {

            $this->publishes(
                [$configPath => config_path('code-distortion.realnum.php'),],
                'config'
            );
        }
    }

    /**
     * Set the RealNum & Percent default values.
     *
     * @return void
     * @throws InvalidLocaleException Thrown when the locale cannot be resolved.
     */
    protected function setDefaults(): void
    {
        $this->updateLocale();

        if (config('code-distortion.realnum')) {

            $maxDecPl = config('code-distortion.realnum.max_dec_pl');
            RealNum::setDefaultMaxDecPl($maxDecPl);
            Percent::setDefaultMaxDecPl($maxDecPl);

            $immutable = config('code-distortion.realnum.immutable');
            RealNum::setDefaultImmutability($immutable);
            Percent::setDefaultImmutability($immutable);

            $formatSettings = config('code-distortion.realnum.format_settings');
            RealNum::setDefaultFormatSettings($formatSettings);
            Percent::setDefaultFormatSettings($formatSettings);
        }
    }

    /**
     * Listen for locale changes.
     *
     * @return void
     */
    protected function localeListen(): void
    {
        if (!$this->app->bound('events')) {
            return;
        }

        $events = $this->app['events'];
        if ($this->isEventDispatcher($events)) {

            // update the locale when the locale-updated event is triggered
            $event = class_exists('Illuminate\Foundation\Events\LocaleUpdated')
                    ? 'Illuminate\Foundation\Events\LocaleUpdated'
                    : 'locale.changed';
            $service = $this;
            $events->listen($event, function () use ($service) {
                $service->updateLocale();
            });
        }
    }

    /**
     * Ensure the given thing is an event dispatcher.
     *
     * @param mixed $instance The object to check.
     * @return boolean
     */
    protected function isEventDispatcher($instance)
    {
        return ($instance instanceof EventDispatcher
            || $instance instanceof Dispatcher
            || $instance instanceof DispatcherContract);
    }

    /**
     * Update the RealNum & Percent locale.
     *
     * @return void
     * @throws InvalidLocaleException Thrown when the locale cannot be resolved.
     */
    protected function updateLocale(): void
    {
        $app = (($this->app) && (method_exists($this->app, 'getLocale')) ? $this->app : app('translator'));
        $locale = $app->getLocale();
        RealNum::setDefaultLocale($locale);
        Percent::setDefaultLocale($locale);
    }
}
