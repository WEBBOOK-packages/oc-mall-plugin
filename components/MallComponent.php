<?php

declare(strict_types=1);

namespace WebBook\Mall\Components;

use Cms\Classes\ComponentBase;
use WebBook\Mall\Classes\Traits\HashIds;

/**
 * This is the base class of all WebBook.Mall components.
 */
abstract class MallComponent extends ComponentBase
{
    use HashIds;

    protected function setVar($name, $value)
    {
        return $this->$name = $this->page[$name] = $value;
    }
}
