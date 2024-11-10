<?php

declare(strict_types=1);

namespace miladmTest\router\stubs;

use miladm\Group;
use miladm\interface\UseMiddleware;

abstract class GroupWithMiddleware extends Group implements UseMiddleware {}
