<?php

declare(strict_types=1);

namespace Encoding;

enum DecodingMode
{
    case Strict;
    case Forgiving;
}
