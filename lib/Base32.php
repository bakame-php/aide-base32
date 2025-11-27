<?php

declare(strict_types=1);

namespace Encoding;

enum Base32
{
    case Ascii;
    case Hex;
    case Crockford;
    case Z;
}
