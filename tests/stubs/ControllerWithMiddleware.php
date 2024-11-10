<?php

declare(strict_types=1);

namespace Tests\stubs;

use Router\Controller;
use Router\interface\UseMiddleware;

abstract class ControllerWithMiddleware extends Controller implements UseMiddleware {}
