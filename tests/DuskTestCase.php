<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Start ChromeDriver. Call from each Browser test class via a @beforeClass hook.
     *
     * @return void
     */
    public static function prepare()
    {
        if (static::runningInSail()) {
            return;
        }

        // @beforeClass runs before the app is bootstrapped; avoid base_path().
        $custom = dirname(__DIR__).'/storage/dusk/chromedriver';
        $fromEnv = $_ENV['DUSK_CHROMEDRIVER_PATH'] ?? ($_SERVER['DUSK_CHROMEDRIVER_PATH'] ?? null);

        if (is_string($fromEnv) && $fromEnv !== '' && is_executable($fromEnv)) {
            static::useChromedriver($fromEnv);
        } elseif (is_executable($custom)) {
            static::useChromedriver($custom);
        } else {
            throw new \RuntimeException(
                'ChromeDriver not found or not executable. Install a Chrome-for-Testing binary: bash scripts/dusk-chromedriver.sh'
            );
        }

        static::startChromeDriver();

        // Allow ChromeDriver to bind before the first session is created.
        usleep(500000);
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver()
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
        ])->unless($this->hasHeadlessDisabled(), function ($items) {
            return $items->merge([
                '--disable-gpu',
                '--headless',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://127.0.0.1:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     * Determine whether the Dusk command has disabled headless mode.
     *
     * @return bool
     */
    protected function hasHeadlessDisabled()
    {
        return isset($_SERVER['DUSK_HEADLESS_DISABLED']) ||
               isset($_ENV['DUSK_HEADLESS_DISABLED']);
    }

    /**
     * Determine if the browser window should start maximized.
     *
     * @return bool
     */
    protected function shouldStartMaximized()
    {
        return isset($_SERVER['DUSK_START_MAXIMIZED']) ||
               isset($_ENV['DUSK_START_MAXIMIZED']);
    }
}
