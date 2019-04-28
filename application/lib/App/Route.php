<?php

namespace App;


class Route
{
    /**
     * @var Route
     */
    private static $instance;

    /**
     * @var array
     */
    private $routingRules = [];

    /**
     * @var Request
     */
    private $request;

    /**
     * Predefiniowane wyrazenia dostepne pod kolejnymi kluczami.
     * Przykladowe uzycie w from.url: {{slug}}
     *
     */
    private const PREDEFINED_PATTERNS_MAP = [
        'multiParams' => '(?:[\/][a-z0-9_-]*)*',
        'slug' => '(?:[a-z][a-z0-9]*(?:-[a-z0-9]+)+)+',
        'number' => '[1-9][0-9]*',
        'word' => '[a-zA-Z]+',
    ];


    private function __construct()
    {
        $this->loadRoutingRules();
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

    private function loadRoutingRules()
    {
        $routingFiles = array_diff(scandir(ROUTING), ['.', '..']);
        foreach ($routingFiles as $routingFile) {
            $routingGroupName = str_replace('.php', '', $routingFile);
            $routingGroupRules = require ROUTING . $routingFile;

            foreach ($routingGroupRules as $routingGroupRuleName => $routingGroupRule) {
                $this->routingRules[$routingGroupName . '-' . $routingGroupRuleName] = $routingGroupRule;
            }
        }
    }

    /**
     *
     */
    private function analyzePath()
    {
        foreach ($this->routingRules as $routingRuleName => $routingRule) {
            $path = str_replace('/', '\/', $routingRule['path']);
            preg_match_all('/(?:(?:\[\[.*?)?{{[a-zA-Z]+(?::[a-zA-Z]+)?}}(?:\/?\]\])?)/', $path, $pathGroup);

            if (empty($pathGroup)) {
                continue;
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

            $regexp = '/^' . str_replace($pathGroupCopy, $pathGroup, $regexp) . '$/';
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
                break;
            }
        }

        var_dump($this->request->get());
    }

    public function run()
    {
        $this->analyzePath();
    }
}
