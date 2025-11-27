# Aide for base32 encoding and decoding

functions or class to allow encoding or decoding strings using [RFC4648](https://datatracker.ietf.org/doc/html/rfc4648) base32/base16 algorithm.
This is a quick polyfill for the proposed RFC around [adding an encoding API to PHP](https://wiki.php.net/rfc/data_encoding_api)

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
use Encoding\Base32;
use Encoding\DecodingMode;
use Encoding\PaddingMode;

base32_encode(string $data, Base32 $variant = Base32::Ascii, PaddingMode $paddingMode = PaddingMode::VariantControlled): string
base32_decode(string $data, Base32 $variant = Base32::Ascii, DecodingMode $decodingMode = DecodingMode::Strict): string
```

#### Parameters:

- `$data` : the string to encode or decode
- `$variant` : the base32 alphabet represented as an Enum.
- `$paddingMode` : the padding mode
- `$decodingMode` : Strict (follow the RFC) or Forgiving
 
If the `$decodingMode` parameter is set to `Strict` then the `base32_decode` function will throw

- if encoded sequence length is invalid
- if the input contains character from outside the base64 alphabet.
- if padding is invalid
- if encoded characters do not follow the alphabet lettering.

otherwise listed constraints are silently ignored or discarded.

#### Examples

```php
<?php

use Encoding\Base16;
use Encoding\Base32;
use Encoding\DecodingMode;
use Encoding\PaddingMode;
use Encoding\TimingMode;
use function Encoding\base16_decode;
use function Encoding\base16_encode;
use function Encoding\base32_decode;
use function Encoding\base32_encode;

base32_encode('Bangui');           // returns 'IJQW4Z3VNE======'
base32_decode('IJQW4Z3VNE======'); // returns 'Bangui'
base32_decode('IJQW4Z083VNE======', decodingMode: DecodingMode::Strict) // throw UnableToDecodeException
base32_encode('Bangui', Base32::Hex, '=');                         // returns '89GMSPRLD4======'
base32_decode('89GMSPRLD4', Base32::Hex, DecodingMode::Forgiving); // returns 'Bangui'
base16_decode("48 65\n6C\t6C\r6F 2C 20 57 6F 72 6C 64 21"); // returns  "Hello, World!"
base16_decode("48 65\n6C\t6C\r6F 2c 20 57 6F 72 6C 64 21", decodingMode: DecodingMode::Forgiving); // returns  "Hello, World!"
```

By default `base16_decode` is in `TimingMode::Constant`. 

> [!CAUTION]
> Since this is a POV, no real validation check is done on the constant-time decoding for base16.