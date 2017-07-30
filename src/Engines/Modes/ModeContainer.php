<?php

namespace Yab\MySQLScout\Engines\Modes;

class ModeContainer
{
    public $mode;

    public $fallbackMode;

    public function __construct($mode, $fallbackMode)
    {
        $this->mode = $mode;
        $this->fallbackMode = $fallbackMode;
    }
}
