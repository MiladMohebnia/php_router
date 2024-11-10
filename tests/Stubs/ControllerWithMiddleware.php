<?php

declare(strict_types=1);

namespace Tests\Stubs;

use Router\Controller;
use Router\Interfaces\UseMiddleware;

abstract class ControllerWithMiddleware extends Controller implements UseMiddleware {}
