<?php

namespace chaser\http\route\component;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
class Suffix
{
    /**
     * 初始化后缀
     *
     * @param string $suffix
     */
    private function __construct(private string $suffix)
    {
    }

    /**
     * 获取后缀
     *
     * @return string
     */
    public function suffix(): string
    {
        return $this->suffix;
    }
}
