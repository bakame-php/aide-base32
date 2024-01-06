# Aide for base32 encoding and decoding

functions or class to allow encoding or decoding strings using [RFC4648](https://datatracker.ietf.org/doc/html/rfc4648) base32 algorithm.

> [!CAUTION]  
> Sub-split of Aide for Base32.  
> ⚠️ this is a sub-split, for pull requests and issues, visit: https://github.com/bakame-php/aide

## Installation

### Composer

~~~
composer require bakame-php/aide-base32
~~~

### System Requirements

You need:

- **PHP >= 8.1** but the latest stable version of PHP is recommended

## Usage

The package provides a userland base32 encoding and decoding mechanism.

```php
base32_encode(string $string, string $alphabet = PHP_BASE32_ASCII): string
base32_decode(string $string, string $alphabet = PHP_BASE32_ASCII, bool $strict = false): string
```

#### Parameters:

- `$string` : the data to encode for `base32_encode` or to decode for `base32_decode`
- `$alphabet` : the base32 alphabet, by default `PHP_BASE_ASCII`. 

If `$alphabet` is `PHP_BASE_ASCII`, conversion is performed per RFC4648 US-ASCII standard.
If `$alphabet` is `PHP_BASE_HEXC`, conversion is performed per RFC4648 HEX standard.

**You can provide your own alphabet of 32 characters.**

- `$strict` : tell whether we need to perform strict decoding or not 

If the strict parameter is set to `true` then the base32_decode() function will return `false`

- if encoded sequence lenght is invalid
- if the input contains character from outside the base64 alphabet. 
- if padding is invalid
- if encoded characters are not all uppercased

otherwise listed constraints are silently ignored or discarded.

```php
<?php

base32_encode('Bangui');                                     // returns 'IJQW4Z3VNE======'
base32_decode('IJQW4Z3VNE======');                           // returns 'Bangui'
base32_decode('IJQW4Z083VNE======');                         // returns 'Bangui'
base32_decode('IJQW4Z083VNE======', PHP_BASE32_ASCII, true); // return false
base32_encode('Bangui', PHP_BASE32_HEX);                     // returns '89GMSPRLD4======'
base32_decode('89GMSPRLD4======', PHP_BASE32_HEX, true);     // returns 'Bangui'
```
