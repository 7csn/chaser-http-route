<?php

namespace chaser\http\route\component;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
class Where
{
    /**
     * 初始化条件
     *
     * @param array $where
     */
    public function __construct(private array $where)
    {
    }

    /**
     * 获取条件
     *
     * @return array
     */
    public function where(): array
    {
        return $this->where;
    }
}
