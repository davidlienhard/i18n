# davidlienhard/i18n
🐘 php library to use for internationalization

[![Latest Stable Version](https://img.shields.io/packagist/v/davidlienhard/i18n.svg?style=flat-square)](https://packagist.org/packages/davidlienhard/i18n)
[![Source Code](https://img.shields.io/badge/source-davidlienhard/i18n-blue.svg?style=flat-square)](https://github.com/davidlienhard/i18n)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://github.com/davidlienhard/i18n/blob/master/LICENSE)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.0-8892BF.svg?style=flat-square)](https://php.net/)
[![CI Status](https://github.com/davidlienhard/i18n/actions/workflows/check.yml/badge.svg)](https://github.com/davidlienhard/i18n/actions/workflows/check.yml)

## Setup

You can install through `composer` with:

```
composer require davidlienhard/i18n:^1
```

*Note: davidlienhard/i18n requires PHP 8.0*

## How to use

### 1. Create language files
Create at least one language file. Supported filetypes are `json`, `yaml`, `yml` or `ini`.

`./lang/en.yml` (English)
```yml
save: Save
greeting: Hi %1
```

`./lang/de.yml` (German)
```yml
save: Speichern
greeting: Hallo %1
```

### 2. Load the class
Use composer autoloader if possible or include the files in the `src` folders manually

### 3. create the object
```php
<?php declare(strict_types=1);

use DavidLienhard\i18n\i18n;

$i18n = new i18n;
```

### 4. Set the options
you can either set some options right through the constructor or via the set methods
```php
$i18n = new i18n(
    filePath: "./lang/{LANGUAGE}.yml",
    cachePath: "./cache/",
    fallbackLang: "de",
    prefix: "L"
);

$i18n->setNamespace("\YourApp\Translations\");
```

The following setter methods are available:
 - `setFilePath(string $filePath)`: Sets the path to the language files
 - `setCachePath(string $cachePath)`: Sets the path to the cache directory
 - `setFallbackLang(string $fallbackLang)`: sets a fallback language
 - `setMergeFallback(bool $mergeFallback)`: whether or not to merge the fallback language
 - `setPrefix(string $prefix)`: Sets the prefix/name of the class to contain the translations
 - `setForcedLang(string $forcedLang)`: a language that is forced to be used
 - `setSectionSeparator(string $sectionSeparator)`: the character to use to concatenate sections

### 5. Initialize the class / create cache-files
```php
$i18n->init();
```

Thss will then create the cache file if required and load the new translation data with the given namespace & prefix/class-name.

### use the translation data
```php
use \YourApp\Translations\L;

echo L::save;                       // Save / Speichern
echo L::get("save");                // Save / Speichern
echo L::greeting("David");          // Hi David // Hallo David
echo L::get("greeting", "David");   // Hi David // Hallo David
```

## License

The MIT License (MIT). Please see [LICENSE](https://github.com/davidlienhard/i18n/blob/master/LICENSE) for more information.
