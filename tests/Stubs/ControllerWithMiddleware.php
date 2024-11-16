<?php

declare(strict_types=1);

namespace Tests\Stubs;

use Router\AbstractController;
use Router\Interfaces\UseMiddleware;

abstract class ControllerWithMiddleware extends AbstractController implements UseMiddleware {}
