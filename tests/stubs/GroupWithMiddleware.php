<?php

declare(strict_types=1);

namespace Tests\stubs;

use Router\Group;
use Router\interface\UseMiddleware;

abstract class GroupWithMiddleware extends Group implements UseMiddleware {}
