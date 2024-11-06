<?php

namespace Theatrical;

class PerformanceCalculator
{
    public function __construct(
        public readonly Performance $performance,
        public readonly Play $play,
    ) {}
}
