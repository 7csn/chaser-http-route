<?php

namespace chaser\http\route\component;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
class Cache
{
    /**
     * 初始化缓存时限（秒）
     *
     * @param int $duration
     */
    public function __construct(private int $duration)
    {
    }

    /**
     * 获取缓存时限（秒）
     *
     * @return int
     */
    public function duration(): int
    {
        return $this->duration;
    }
}
