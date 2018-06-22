<?php

declare(strict_types=1);

namespace Ypf\Router;

class RegexRoute extends Route
{
    private $parameters = [];

    public function getParameters(): iterable
    {
        return array_filter($this->parameters, function ($idx) {
            return !is_int($idx);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function isMatch(string $path): bool
    {
        if (preg_match("~^{$this->parse($this->getPattern())}$~x", $path, $this->parameters)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function parse(string $path): string
    {
        return preg_replace(
            ['~\{(\w+)\}+~iuU', '~\{(\w+)\:(.*)\}+~iuU', '~\{/?(.*)\}\?~iuU', '~\{(.*)\}\?~iuU'],
            ['(?P<$1>[^/]+)', '(?P<$1>$2)', '(?:$1)?', '(?:$1)?'],
            str_replace('/*', '/(?:.*)', $path)
        );
    }
}
