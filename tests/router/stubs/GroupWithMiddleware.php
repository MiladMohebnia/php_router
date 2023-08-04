<?php

namespace miladmTest\router\stubs;

use miladm\router\Group;
use miladm\router\interface\UseMiddleware;

abstract class GroupWithMiddleware extends Group implements UseMiddleware
{
}
