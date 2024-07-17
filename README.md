# Laravel Module Installer

The purpose of this package is to allow for easy installation of standalone Modules into the [Laravel Modules](https://github.com/nWidart/laravel-modules) package. This package will ensure that your module is installed into the `Modules/` directory instead of `vendor/`.

**This a refactored version of `joshbrw/laravel-module-installer`. It incorporates most of the PR's open since 2021.**

## Installation

1. Ensure you have the `type` set to `laravel-module` in your module's `composer.json`
2. If your package is named in the convention of `<namespace>/<name>-module`, for example `joshbrw/user-module`, it will install by default to `Modules/User`.
3. Require this package: `composer require joshbrw/laravel-module-installer`
4. Require your bespoke module using Composer. You may want to set the constraint to `dev-master` to ensure you always get the latest version.

## Options

### Application `composer.json`

All options go into `extra` of the application `composer.json`

| option       | type   | default   |                                                     |
|--------------|--------|-----------|-----------------------------------------------------|
| `module-dir` | string | `Modules` | Sets the directory name where modules are installed |

#### `extra.module-dir`

To change the default `Modules` directory where the modules are installed, set the `module-dir` in `extra` of the applications `composer.json`.

```json
{
  "extra": {
    "module-dir": "Custom"
  }
}
```

### Package `composer.json` 

All options go into `extra` of the package `composer.json`

| option                     | type     | default |                                                                                    |
|----------------------------|----------|---------|------------------------------------------------------------------------------------|
| `module-name`              | `string` |         | Sets a custom module name                                                          |
| `include-module-namespace` | `bool`   | `false` | Includes the vendor (namespace) in the module directory path                       |
| `include-module-part`      | `bool`   | `false` | Does not remove the ending `-module` and becomes part of the module directory path |

#### `extra.module-name`

By default, this package uses the `<vendor>/<package-name>` structure as a base to determine the module's directory path. To
change this default behaviour, set the `extra.module-name` to any custom name. 

```json
{
  "extra": {
    "module-name": "custom-module-name"
  }
}
```

This will result in a module directory path called `Modules/CustomModuleName`

#### `extra.include-module-namespace`

To include the package vendor name in the module's directory path, set the `include-module-namespace` to `true` (defaults to `false`).

```json
{
  "extra": {
    "include-module-namespace": true
  }
}
```

Given `vendor/some-module` will result in a module directory path called `Modules/Vendor/Some`

#### `extra.include-module-part`

If a package name ends with `-module`, this will be removed by default. If `-module` should be part of the module directory path, 
set `include-module-part` to `true` to incorporate it into its path.

```json
{
  "extra": {
    "include-module-part": true
  }
}
```

Given `vendor/some-module` results in a module directory path called `Modules/SomeModule`

## Tests

```bash
composer test
```

## Notes
* When working on a module that is version controlled within an app that is also version controlled, you have to commit and push from inside the Module directory and then `composer update` within the app itself to ensure that the latest version of your module (dependant upon constraint) is specified in your composer.lock file.
