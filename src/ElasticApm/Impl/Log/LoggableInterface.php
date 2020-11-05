<?php

declare(strict_types=1);

namespace Elastic\Apm\Impl\Log;

use JsonSerializable;

/**
 * Code in this file is part of implementation internals and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
interface LoggableInterface
{
    public function toLog(LogStreamInterface $stream): void;
}
