<?php

declare(strict_types=1);

namespace Tests\Stubs;

use Router\Group;
use Router\Interfaces\UseMiddleware;

abstract class GroupWithMiddleware extends Group implements UseMiddleware {}
