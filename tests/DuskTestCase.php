<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;
use RuntimeException;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Pastikan DB Dusk aktif sebelum trait database (DatabaseTruncation, DatabaseMigrations,
     * RefreshDatabase) berjalan.
     *
     * Pengecekan tidak bisa ditaruh setelah parent::setUp(), karena truncation/migrate:fresh
     * sudah berjalan di dalamnya. Pengecekan juga tidak bisa ditaruh di beforeTruncatingDatabase(),
     * karena method trait di kelas test akan menimpa method dari kelas ini.
     */
    protected function setUpTraits()
    {
        $this->pastikanDatabaseDusk();

        return parent::setUpTraits();
    }

    /**
     * Hentikan tes bila DB aktif bukan DB khusus Dusk, supaya data DB dev tidak terhapus.
     */
    protected function pastikanDatabaseDusk(): void
    {
        $nama = (string) config('database.connections.'.config('database.default').'.database');

        if (! str_ends_with($nama, '_dusk')) {
            throw new RuntimeException(
                "Dusk dibatalkan: DB aktif '{$nama}' bukan DB Dusk. Buat .env.dusk.local (lihat SETUP.md) dan jalankan lewat php artisan dusk."
            );
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
            '--disable-features=PasswordLeakDetection',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        // Matikan password manager Chrome. Bubble "simpan/ubah kata sandi" setelah form login
        // dikirim bisa mengganggu klik berikutnya dan membuat tes gagal acak.
        $options->setExperimentalOption('prefs', [
            'credentials_enable_service' => false,
            'profile.password_manager_enabled' => false,
            'profile.password_manager_leak_detection' => false,
        ]);

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
}
