<?php

namespace miladmTest\router\stubs;

use miladm\router\Controller;
use miladm\router\interface\UseMiddleware;

abstract class ControllerWithMiddleware extends Controller implements UseMiddleware
{
}
