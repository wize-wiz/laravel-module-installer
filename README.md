# Laravel Module Installer

The purpose of this package is to allow for easy installation of standalone Modules into the [Laravel Modules](https://github.com/nWidart/laravel-modules) package. This package will ensure that your module is installed into the `Modules/` directory instead of `vendor/`.

You can specify an alternate directory by including a `module-dir` in the extra data in your app `composer.json` file:

    "extra": {
        "module-dir": "Custom"
    }

You can also specify the exact module name in the package `composer.json` before its publication:

    "extra": {
        "module-name": "blog"
    }

Here the example target directory is `Modules/Blog` (`ucfirst` for the `module-name` applied).  

## Installation

1. Ensure you have the `type` set to `laravel-module` in your module's `composer.json`
2. If your package is named in the convention of `<namespace>/<name>-module`, for example `joshbrw/user-module`, it will install by default to `Modules/User`.
3. Require this package: `composer require joshbrw/laravel-module-installer`
4. Require your bespoke module using Composer. You may want to set the constraint to `dev-master` to ensure you always get the latest version.

## Options

### App `composer.json`

All options go into `extra`

| option     |                                                                        |
|------------|------------------------------------------------------------------------|
| module-dir | Sets the directory name where modules are installed, default `Modules` |

#### `extra.module-dir`

To change the default `Modules` directory where modules are installed, set the `module-dir` in `extra` of the applications `composer.json`.

```json
{
  "extra": {
    "laravel": {
      "dont-discover": []
    },
    "module-dir": "Custom"
  }
}
```

### Package `composer.json` 

All options go into `extra`

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

This results in a module directory path called `Modules/CustomModuleName`

#### `extra.include-module-namespace`

To include the package vendor name in the module's directory path, set the `include-module-namespace` to `true` (defaults to `false`). 

```json
{
  "extra": {
    "include-module-namespace": true
  }
}
```

Given `vendor/some-module` results in a module directory path called `Modules/Vendor/Some`

## Notes
* When working on a module that is version controlled within an app that is also version controlled, you have to commit and push from inside the Module directory and then `composer update` within the app itself to ensure that the latest version of your module (dependant upon constraint) is specified in your composer.lock file.
