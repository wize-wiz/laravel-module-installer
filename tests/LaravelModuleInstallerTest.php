<?php

use PHPUnit\Framework\TestCase;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Joshbrw\LaravelModuleInstaller\Exceptions\LaravelModuleInstallerException;
use Joshbrw\LaravelModuleInstaller\LaravelModuleInstaller;
use PHPUnit\Framework\Attributes\Test;

class LaravelModuleInstallerTest extends TestCase
{
    protected $io;
    protected $composer;
    protected $config;
    protected $test;

    public function setUp(): void
    {
        $this->io = Mockery::mock(IOInterface::class);
        $this->composer = Mockery::mock(Composer::class);
        $this->config = Mockery::mock(Config::class, [
            'get' => './vendor'
        ]);
        $getPackage = Mockery::mock('alias:Composer\Package\RootPackageInterface')
            ->shouldReceive('getExtra')
            ->andReturn([])
            ->getMock();

        $this->composer->allows([
            'getPackage' => $getPackage,
            'getDownloadManager' => Mockery::mock('alias:Composer\Downloader\DownloadManager'),
            'getConfig' => $this->config
        ])->shouldReceive('getExtra')->byDefault();

        $this->test = new LaravelModuleInstaller(
            $this->io, $this->composer
        );
    }

    #[Test]
    public function it_supports_laravel_module_type_only(): void
    {
        $this->assertFalse($this->test->supports('module'));
        $this->assertTrue($this->test->supports('laravel-module'));
    }

    // @todo: will this ever happen? `vendor/package-name` is the default standard for composer, I don't see how this scenario can occur
    public function it_throws_exception_if_given_malformed_name(): void
    {
        $mock = $this->getMockPackage('vendor');

        $this->expectException(LaravelModuleInstallerException::class);

        $this->test->getInstallPath($mock);        
    }

    // @todo: this has changed but is still supported, does not throw an exception
    public function it_throws_exception_if_suffix_not_included(): void
    {
        $mock = $this->getMockPackage('vendor/name');

        $this->expectException(LaravelModuleInstallerException::class);

        $this->test->getInstallPath($mock);
    }

    #[Test]
    public function it_returns_modules_folder_by_default(): void
    {
        $mock = $this->getMockPackage('vendor/name-module');

        $this->assertEquals('Modules/Name', $this->test->getInstallPath($mock));
    }

    // @todo: it can take any name it wants
    public function it_throws_exception_if_given_malformed_compound_name(): void
    {
        $mock = $this->getMockPackage('vendor/some-compound-name');

        $this->expectException(LaravelModuleInstallerException::class);

        $this->test->getInstallPath($mock);
    }

    #[Test]
    public function it_can_use_compound_module_names(): void
    {
        $mock = $this->getMockPackage('vendor/compound-name-module');

        $this->assertEquals('Modules/CompoundName', $this->test->getInstallPath($mock));
    }

    #[Test]
    public function it_can_keep_module_name_in_path(): void
    {
        $mock = $this->getMockPackage('vendor/compound-name-module', [
            LaravelModuleInstaller::OPTION_INCLUDE_MODULE_PART => true
        ]);

        $this->assertEquals('Modules/CompoundNameModule', $this->test->getInstallPath($mock));
    }

    /**
     * You can optionally include a base path name
     * in which to install.
     *
     *    "extra": {
     *      "module-dir": "Custom"
     *    },
     */
    #[Test]
    public function it_can_use_custom_path(): void
    {
        $package = $this->getMockPackage('vendor/name-module');

        $this->composer->shouldReceive('getExtra')
            ->andReturn([LaravelModuleInstaller::OPTION_MODULE_DIR => 'Custom'])
            ->getMock();

        $this->assertEquals('Custom/Name', $this->test->getInstallPath($package));
    }

    #[Test]
    public function it_can_use_custom_path_with_custom_module_name(): void {
        $package = $this->getMockPackage('vendor/name-module', [
            LaravelModuleInstaller::OPTION_MODULE_NAME => 'change-name'
        ]);

        $this->setComposerExtra([
            LaravelModuleInstaller::OPTION_MODULE_DIR => 'Custom'
        ]);

        $this->assertEquals('Custom/ChangeName', $this->test->getInstallPath($package));
    }

    /**
     * You can optionally include a vendor path name
     * in the extra data in your composer.json file inside the module:
     *  "extra": {
     *      "module-namespace-dir": true
     *  }
     * If it is false or does not exist, the default mode will be used.
     *
     */
    #[Test]
    public function it_can_use_vendor_namespace_path(): void
    {
        $package = $this->getMockPackage('vendor/name-module', [
            LaravelModuleInstaller::OPTION_INCLUDE_MODULE_NAMESPACE => true
        ]);

        $this->assertEquals('Modules/Vendor/Name', $this->test->getInstallPath($package));
    }

    #[Test]
    public function it_can_use_vendor_namespace_with_custom_path(): void
    {
        $package = $this->getMockPackage('vendor/name-module', [
            LaravelModuleInstaller::OPTION_INCLUDE_MODULE_NAMESPACE => true
        ]);

        $this->setComposerExtra([
            'module-dir' => 'Custom'
        ]);

        $this->assertEquals('Custom/Vendor/Name', $this->test->getInstallPath($package));
    }

    #[Test]
    public function it_can_use_vendor_namespace_custom_path_custom_module_name(): void
    {
        $package = $this->getMockPackage('vendor/name-module', [
            LaravelModuleInstaller::OPTION_INCLUDE_MODULE_NAMESPACE => true,
            LaravelModuleInstaller::OPTION_MODULE_NAME => 'custom-name'
        ]);

        $this->setComposerExtra([
            'module-dir' => 'Custom'
        ]);

        $this->assertEquals('Custom/Vendor/CustomName', $this->test->getInstallPath($package));
    }

    private function getMockPackage($name, array $extra = [])
    {
        $package = Mockery::mock(PackageInterface::class)
            ->shouldReceive('getPrettyName')
            ->once()
            ->andReturn($name)
            ->getMock();

        return $package->shouldReceive('getExtra')
            ->andReturn($extra)
            ->getMock();
    }

    private function setComposerExtra(array $extra) {
        $this->composer->shouldReceive('getExtra')
            ->andReturn($extra)
            ->getMock();
    }
}
