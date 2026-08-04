<?php

namespace App\Import;

enum ImportRowStatus
{
    case CREATED;
    case SKIPPED_EXISTING;
    case ERROR;
}
