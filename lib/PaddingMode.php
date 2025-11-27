<?php

declare(strict_types=1);

namespace Encoding;

enum PaddingMode
{
    case VariantControlled;
    case StripPadding;
    case PreservePadding;
}
