<?php

namespace chaser\http\route\component;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
class Domains
{
    /**
     * 初始化开放域名组
     *
     * @param string[] $domains
     */
    public function __construct(private array $domains)
    {
    }

    /**
     * 获取开放域名组
     *
     * @return string[]
     */
    public function domains(): array
    {
        return $this->domains;
    }
}
