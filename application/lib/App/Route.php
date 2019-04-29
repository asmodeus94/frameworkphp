<?php

namespace App;


use App\Helper\Url;

class Route
{
    /**
     * @var Route
     */
    private static $instance;

    /**
     * @var array
     */
    private $rules = [];

    /**
     * @var Request
     */
    private $request;

    /**
     * Predefiniowane wyrazenia dostepne pod kolejnymi kluczami.
     */
    private const PREDEFINED_PATTERNS_MAP = [
        'multiParams' => '(?:[\/][a-z0-9_-]*)*',
        'slug' => '(?:[a-z][a-z0-9]*(?:-[a-z0-9]+)+)+',
        'number' => '[1-9][0-9]*',
        'word' => '[a-zA-Z]+',
    ];


    private function __construct()
    {
        $this->loadRules();
    }

    public static function getInstance(): Route
    {
        if (!isset(self::$instance)) {
            self::$instance = new Route();
        }

        return self::$instance;
    }

    /**
     * @param Request $request
     *
     * @return $this
     */
    public function setRequest(Request $request): Route
    {
        $this->request = $request;

        return $this;
    }

    /**
     *
     */
    private function loadRules(): void
    {
        $routingFiles = array_diff(scandir(ROUTING), ['.', '..']);
        foreach ($routingFiles as $routingFile) {
            if (strpos($routingFile, '.php') === false) {
                continue;
            }

            $routingGroupName = str_replace('.php', '', $routingFile);
            $routingGroupRules = require ROUTING . $routingFile;

            foreach ($routingGroupRules as $routingGroupRuleName => $routingGroupRule) {
                $this->rules[$routingGroupName . '-' . $routingGroupRuleName] = $routingGroupRule;
            }
        }
    }

    /**
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @param string $path
     *
     * @return string|null
     */
    private function prepareRegexp(string $path): ?string
    {
        $path = str_replace('/', '\/', $path);
        preg_match_all('/(?:(?:\[\[.*?)?{{[a-zA-Z]+(?::[a-zA-Z]+)?}}(?:\/?\]\])?)/', $path, $pathGroup);

        if (empty($pathGroup[0])) {
            return null;
        }

        $regexp = $path;
        $pathGroup = $pathGroupCopy = $pathGroup[0];
        foreach ($pathGroup as &$group) {
            if (!preg_match('/{{([a-zA-Z]+)(?::([a-zA-Z]+))?}}/', $group, $groupAnalyzed)) {
                continue;
            }

            $groupRegexp = null;
            if ($groupAnalyzed[1] === 'multiParams') {
                $groupRegexp = self::PREDEFINED_PATTERNS_MAP['multiParams'];
            } elseif (isset($groupAnalyzed[2]) && isset(self::PREDEFINED_PATTERNS_MAP[$groupAnalyzed[2]])) {
                $groupRegexp = self::PREDEFINED_PATTERNS_MAP[$groupAnalyzed[2]];
            }

            if ($groupRegexp === null) {
                $groupRegexp = '.*?';
            }

            $pattern = '(?<' . $groupAnalyzed[1] . '>' . $groupRegexp . ')';

            $group = str_replace($groupAnalyzed[0], $pattern, $group);

            if (preg_match('/^\[\[(.*)\]\]$/', $group, $optionalPattern)) {
                $group = str_replace($optionalPattern[0], '(?:' . $optionalPattern[1] . ')?', $group);
            }
        }

        return '/^' . str_replace($pathGroupCopy, $pathGroup, $regexp) . '$/';
    }

    /**
     * @param string $routeName
     * @param string $url
     *
     * @return bool
     */
    public function validate(string $routeName, $url): bool
    {
        if (!isset($this->rules[$routeName]) || ($regexp = $this->prepareRegexp($this->rules[$routeName]['path'])) === null) {
            return false;
        }

        if (($strPos = strpos($url, '?')) !== false) {
            $url = substr($url, 0, $strPos);
        }

        var_dump($regexp, $url);

        return (bool)preg_match($regexp, $url);
    }

    /**
     * @return string|null
     */
    private function analyzePath(): ?string
    {
        foreach ($this->rules as $routingRuleName => $routingRule) {
            if (($regexp = $this->prepareRegexp($routingRule['path'])) === null) {
                continue;
            }

            if (preg_match($regexp, $this->request->getPath(), $matches)) {
                if (isset($matches['multiParams'])) {
                    $multiParams = array_values(array_filter(explode('/', $matches['multiParams'])));
                    $nOfEle = count($multiParams);
                    if ($nOfEle % 2 !== 0) {
                        unset($multiParams[--$nOfEle]);
                    }

                    for ($index = 0; $index < $nOfEle - 1; $index += 2) {
                        $this->request->appendGet($multiParams[$index], $multiParams[$index + 1]);
                    }

                    unset($matches['multiParams']);
                }

                foreach ($matches as $param => $value) {
                    if (!is_string($param) || $value === '') {
                        continue;
                    }

                    $this->request->appendGet($param, $value);
                }

                return $routingRuleName;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function run(): array
    {
        $class = $method = null;
        if (($routingRuleName = $this->analyzePath()) !== null) {
            $class = !empty($this->rules[$routingRuleName]['class']) ? $this->rules[$routingRuleName]['class'] : null;
            $method = !empty($this->rules[$routingRuleName]['method']) ? $this->rules[$routingRuleName]['method'] : null;
        }

        return [$class, $method];
    }
}
