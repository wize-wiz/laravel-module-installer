<?php

namespace Joshbrw\LaravelModuleInstaller;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Silencer;
use Joshbrw\LaravelModuleInstaller\Exceptions\LaravelModuleInstallerException;

class LaravelModuleInstaller extends LibraryInstaller
{
    const DEFAULT_ROOT = "Modules";

    const OPTION_MODULE_DIR = 'module-dir';
    const OPTION_MODULE_NAME = 'module-name';
    const OPTION_INCLUDE_MODULE_NAMESPACE = 'include-module-namespace';
    const OPTION_INCLUDE_MODULE_PART = 'include-module-part';
    const OPTION_USE_SYMLINKS = 'use-symlinks';

    protected function getModuleInstallPath(PackageInterface $package)
    {
        return $this->getBaseInstallationPath() . '/' . $this->getModuleName($package);
    }

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        return !$this->shouldModuleBySymlinked() ?
            $this->getModuleInstallPath($package) :
            parent::getInstallPath($package);
    }

    /**
     * Get the base path that the module should be installed into.
     * Defaults to Modules/ and can be overridden in the module's composer.json.
     *
     * @return string
     */
    protected function getBaseInstallationPath()
    {
        if($package = $this->getRootPackage()) {
            $extra = $package->getExtra();

            if($dir = ($extra[static::OPTION_MODULE_DIR] ?? false)) {
                return $dir;
            }
        }

        return self::DEFAULT_ROOT;
    }

    /**
     * Get the module name, i.e. "joshbrw/something-module" will be transformed into "Something"
     *
     * @param PackageInterface $package Compose Package Interface
     *
     * @return string Module Name
     *
     * @throws LaravelModuleInstallerException
     */
    protected function getModuleName(PackageInterface $package)
    {
        $return = [];
        $extra = $package->getExtra();
        $module_name = $extra[static::OPTION_MODULE_NAME] ?? false;
        $pretty_name = $package->getPrettyName();

        @list($vendor, $name) = explode("/", $pretty_name);

        if(!$name) {
            throw LaravelModuleInstallerException::fromInvalidPackage($pretty_name);
        }

        // if custom name was given
        if(is_string($module_name)) {
            $name = $module_name;
        }
        else {
            if(! str_ends_with($pretty_name, '-module')) {
                throw LaravelModuleInstallerException::fromInvalidPackage($pretty_name);
            }
        }

        if($extra[static::OPTION_INCLUDE_MODULE_NAMESPACE] ?? false) {
            $return[] = $this->nameToKebab($vendor, false);
        }

        // backwards compatibility to strip names of `-module` ending
        $return[] = $this->nameToKebab($name, !($extra[static::OPTION_INCLUDE_MODULE_PART] ?? false));

        return implode('/', $return);
    }

    private function nameToKebab(string $value, bool $exclude_module = true)
    {
        if(! str_contains($value, '-')) {
            return ucfirst($value);
        }

        $split = explode('-', $value);
        if($exclude_module && end($split) === 'module') {
            array_pop($split);
        }

        return implode('', array_map('ucfirst', $split));
    }

    protected function getRootPackage()
    {
        return $this->composer && ($root = $this->composer->getPackage()) ?
            $root : null;
    }

    protected function shouldModuleBySymlinked()
    {
        if($root = $this->getRootPackage()) {
            $extra = $root->getExtra();
            return ($extra[static::OPTION_USE_SYMLINKS] ?? false);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return 'laravel-module' === $packageType;
    }

    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $promise = parent::install($repo, $package);
        if(! $this->shouldModuleBySymlinked()) {
            return $promise;
        }

        $module_path = $this->vendorDir . '/../' . $this->getModuleInstallPath($package);
        $install_path = $this->getInstallPath($package);

        return $promise->then(static function () use ($package, $repo, $module_path, $install_path) {
            if($repo->hasPackage($package)) {
                Silencer::call('symlink', $install_path, $module_path);
            }
        });
    }

    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $promise = parent::uninstall($repo, $package);
        if(! $this->shouldModuleBySymlinked()) {
            return $promise;
        }

        $module_path = $this->vendorDir . '/../' . $this->getModuleInstallPath($package);

        return $promise->then(function() use ($package, $repo, $module_path) {
            if(! $repo->hasPackage($package) && is_link($module_path)) {
                Silencer::call('unlink', $module_path);
            }
        });
    }

}
