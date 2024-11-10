<?php

declare(strict_types=1);

namespace miladmTest\router\stubs;

use miladm\Controller;
use miladm\interface\UseMiddleware;

abstract class ControllerWithMiddleware extends Controller implements UseMiddleware {}
