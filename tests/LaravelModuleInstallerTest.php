<?php

use PHPUnit\Framework\TestCase;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Joshbrw\LaravelModuleInstaller\Exceptions\LaravelModuleInstallerException;
use Joshbrw\LaravelModuleInstaller\LaravelModuleInstaller;
use PHPUnit\Framework\Attributes\Test;

class LaravelModuleInstallerTest extends TestCase
{
    protected $io;
    protected $composer;
    protected $config;
    protected $manager;
    protected $package;
    protected $test;

    public function setUp(): void
    {
        $this->io = Mockery::mock(IOInterface::class);
        $this->config = Mockery::mock(Config::class, [
            'get' => './vendor'
        ]);
        $this->manager = Mockery::mock('alias:Composer\Downloader\DownloadManager');

        $this->initLaravelModuleInstaller();
    }

    protected function initLaravelModuleInstaller(array $extra = [])
    {
        $this->test = new LaravelModuleInstaller(
            $this->io, $this->mockComposer($this->mockRootPackage($extra))
        );
    }

    protected function mockComposer($package) {
        $composer = Mockery::mock(Composer::class);
        $composer->allows([
            'getPackage' => $package,
            'getDownloadManager' => $this->manager,
            'getConfig' => $this->config
        ]);
        return $composer;
    }

    protected function mockPackage($name, $interface, array $extra = [])
    {
        $package = Mockery::mock($interface)
            ->shouldReceive('getPrettyName')
            ->once()
            ->andReturn($name)
            ->getMock();

        return $package
            ->shouldReceive('getExtra')
            ->andReturn($extra)
            ->getMock();
    }

    protected function mockModulePackage($name, array $extra = [])
    {
        return $this->mockPackage($name, PackageInterface::class, $extra);
    }

    protected function mockRootPackage(array $extra = [])
    {
        return $this->mockPackage('__root__', RootPackageInterface::class, $extra);
    }

    #[Test]
    public function it_supports_laravel_module_type_only(): void
    {
        $this->assertFalse($this->test->supports('module'));
        $this->assertTrue($this->test->supports('laravel-module'));
    }

    #[Test]
    public function it_throws_exception_if_given_malformed_name_1(): void
    {
        $mock = $this->mockModulePackage('vendor');

        $this->expectException(LaravelModuleInstallerException::class);

        $this->test->getInstallPath($mock);        
    }

    #[Test]
    public function it_throws_exception_if_given_malformed_name_2(): void
    {
        $mock = $this->mockModulePackage('vendor/0');

        $this->expectException(LaravelModuleInstallerException::class);

        $this->test->getInstallPath($mock);
    }

    #[Test]
    public function it_throws_exception_if_suffix_not_included_and_module_name_not_set(): void
    {
        $mock = $this->mockModulePackage('vendor/some-name');

        $this->expectException(LaravelModuleInstallerException::class);

        $this->test->getInstallPath($mock);
    }

    #[Test]
    public function it_returns_modules_folder_by_default(): void
    {
        $mock = $this->mockModulePackage('vendor/name-module');

        $this->assertEquals('Modules/Name', $this->test->getInstallPath($mock));
    }

    #[Test]
    public function it_can_use_compound_module_names(): void
    {
        $mock = $this->mockModulePackage('vendor/compound-name-module');

        $this->assertEquals('Modules/CompoundName', $this->test->getInstallPath($mock));
    }

    #[Test]
    public function it_can_keep_module_name_in_path(): void
    {
        $package = $this->mockModulePackage('vendor/compound-name-module', [
            LaravelModuleInstaller::OPTION_INCLUDE_MODULE_PART => true
        ]);

        $this->assertEquals('Modules/CompoundNameModule', $this->test->getInstallPath($package));
    }

    #[Test]
    public function it_can_use_custom_path(): void
    {
        $package = $this->mockModulePackage('vendor/name-module');

        $this->initLaravelModuleInstaller([
            LaravelModuleInstaller::OPTION_MODULE_DIR => 'Custom'
        ]);

        $this->assertEquals('Custom/Name', $this->test->getInstallPath($package));
    }

    #[Test]
    public function it_can_use_custom_path_with_custom_module_name(): void
    {
        $package = $this->mockModulePackage('vendor/name-module', [
            LaravelModuleInstaller::OPTION_MODULE_NAME => 'change-name'
        ]);

        $this->initLaravelModuleInstaller([
            LaravelModuleInstaller::OPTION_MODULE_DIR => 'Custom'
        ]);

        $this->assertEquals('Custom/ChangeName', $this->test->getInstallPath($package));
    }

    #[Test]
    public function it_can_use_vendor_namespace_path(): void
    {
        $package = $this->mockModulePackage('vendor/name-module', [
            LaravelModuleInstaller::OPTION_INCLUDE_MODULE_NAMESPACE => true
        ]);

        $this->assertEquals('Modules/Vendor/Name', $this->test->getInstallPath($package));
    }

    #[Test]
    public function it_can_use_vendor_namespace_with_custom_path(): void
    {
        $package = $this->mockModulePackage('vendor/name-module', [
            LaravelModuleInstaller::OPTION_INCLUDE_MODULE_NAMESPACE => true
        ]);

        $this->initLaravelModuleInstaller([
            'module-dir' => 'Custom'
        ]);

        $this->assertEquals('Custom/Vendor/Name', $this->test->getInstallPath($package));
    }

    #[Test]
    public function it_can_use_vendor_namespace_custom_path_custom_module_name(): void
    {
        $package = $this->mockModulePackage('vendor/name-module', [
            LaravelModuleInstaller::OPTION_INCLUDE_MODULE_NAMESPACE => true,
            LaravelModuleInstaller::OPTION_MODULE_NAME => 'custom-name'
        ]);

        $this->initLaravelModuleInstaller([
            'module-dir' => 'Custom'
        ]);

        $this->assertEquals('Custom/Vendor/CustomName', $this->test->getInstallPath($package));
    }

}
