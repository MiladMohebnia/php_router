<?php

declare(strict_types=1);

namespace Tests\Stubs;

use Router\AbstractGroup;
use Router\Interfaces\UseMiddleware;

abstract class GroupWithMiddleware extends AbstractGroup implements UseMiddleware {}
