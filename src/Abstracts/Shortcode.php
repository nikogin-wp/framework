<?php

namespace Nikogin\Framework\Abstracts;

use Exception;
use Nikogin\Framework\Contracts\Handleable;

abstract class Shortcode implements Handleable
{
    protected string $tag;

    /**
     * @throws Exception
     */
    public function __construct(string $tag)
    {
        $this->tag = $tag;
        if (empty($this->tag)) {
            throw new Exception('Shortcode tag is not defined in ' . static::class);
        }

        add_shortcode($this->tag, [$this, 'handle']);
    }

    /**
     * Must be implemented by all subclasses.
     *
     * @param array<string, mixed> $attrs
     * @param string|null          $content
     * @return string
     */
    abstract public function handle(array $attrs = [], string $content = null): mixed;
}