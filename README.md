# Aide for base32 encoding and decoding

functions or class to allow encoding or decoding strings using [RFC4648](https://datatracker.ietf.org/doc/html/rfc4648) base32 algorithm.

> [!CAUTION]  
> Sub-split of Aide for Error.  
> ⚠️ this is a sub-split, for pull requests and issues, visit: https://github.com/bakame-php/aide

## Installation

### Composer

~~~
composer require bakame-php/aide-error
~~~

### System Requirements

You need:

- **PHP >= 8.1** but the latest stable version of PHP is recommended

## Usage

The package provides a userland base32 encoding and decoding mechanism.

You can either use the `Base32` class as shown below:

```php
<?php

use Bakame\Aide\Base32\Base32;

Base32::encode('Bangui');                       // returns 'IJQW4Z3VNE======'
Base32::decode('IJQW4Z3VNE======');              // returns 'Bangui'
Base32::decodeLax('IJQW4Z083VNE======');         // returns 'Bangui'
Base32::decode('IJQW4Z083VNE======');            // throws Base32Exception
Base32::encode('Bangui', Base32::HEX);           // returns '89GMSPRLD4======'
Base32::decode('89GMSPRLD4======', Base32::HEX); // returns 'Bangui'
```

or use the equivalent functions in the default scope

```php
<?php

base32_encode('Bangui');                                     // returns 'IJQW4Z3VNE======'
base32_decode('IJQW4Z3VNE======');                           // returns 'Bangui'
base32_decode('IJQW4Z083VNE======');                         // returns 'Bangui'
base32_decode('IJQW4Z083VNE======', PHP_BASE32_ASCII, '=', true); // throws Base32Exception
base32_encode('Bangui', PHP_BASE32_HEX);                          // returns '89GMSPRLD4======'
base32_decode('89GMSPRLD4======', PHP_BASE32_HEX, '=', true);     // returns 'Bangui'
```

In case of an error during decoding the `Base32` enumeration will throw a `Base23Exception` while
the equivalent functions will return `false`;
