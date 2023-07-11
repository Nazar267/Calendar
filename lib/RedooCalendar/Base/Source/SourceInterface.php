<?php

namespace RedooCalendar\Base\Source;

interface SourceInterface
{
    public static function getData(): array;

    public static function getOptionsData(): array;
}