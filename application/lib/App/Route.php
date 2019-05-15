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
    private $rules = [];

    /**
     * @var Request
     */
    private $request;

    const DEFAULT_METHOD = 'index';

    const MULTI_PARAMS_PATTERN = 'multiParams';

    /**
     * Predefiniowane wyrażenia regularne
     */
    private const PREDEFINED_PATTERNS_MAP = [
        self::MULTI_PARAMS_PATTERN => '(?:[\/][a-z0-9_-]*)*',
        'slug' => '(?:[a-z0-9]+(?:-[a-z0-9]+)*?)+',
        'number' => '[0-9]+',
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
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Ładuje do tablicy wszystkie reguły dot. routingu
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
     * Na podstawie reguły routingu (jej ścieżki) tworzy wyr. regularne umożliwiające sprawdzenie poprawności ścieżki
     *
     * @param string $path
     *
     * @return string|null
     */
    private function prepareRegexp(string $path): ?string
    {
        $path = str_replace(['/', '-'], ['\/', '\-'], $path);
        $path = preg_replace('/\[(.*?)\]/', '(?:$1)?', $path);

        preg_match_all('/(?:{[a-zA-Z\-_]+(?::[a-zA-Z]+)?})/', $path, $placeholderGroups);

        $regexp = $path;
        $placeholderGroups = $placeholderGroupsCopy = $placeholderGroups[0];
        foreach ($placeholderGroups as &$placeholder) {
            if (!preg_match('/{([a-zA-Z\-_]+)(?::([a-zA-Z]+))?}/', $placeholder, $placeholderAnalyzed)) {
                continue;
            }

            $placeholderRegexp = null;
            if ($placeholderAnalyzed[1] === self::MULTI_PARAMS_PATTERN) {
                $placeholderRegexp = self::PREDEFINED_PATTERNS_MAP[self::MULTI_PARAMS_PATTERN];
            } elseif (isset($placeholderAnalyzed[2]) && isset(self::PREDEFINED_PATTERNS_MAP[$placeholderAnalyzed[2]])) {
                $placeholderRegexp = self::PREDEFINED_PATTERNS_MAP[$placeholderAnalyzed[2]];
            }

            if ($placeholderRegexp === null) {
                $placeholderRegexp = '.*?';
            }

            $pattern = '(?<' . $placeholderAnalyzed[1] . '>' . $placeholderRegexp . ')';
            $placeholder = str_replace($placeholderAnalyzed[0], $pattern, $placeholder);
        }

        return '/^' . str_replace($placeholderGroupsCopy, $placeholderGroups, $regexp) . '$/';
    }

    /**
     * Dla podanego routingu sprawdza, czy ścieżka jest zgodna z wyrażeniem regularnym
     *
     * @param string $routeName Nazwa routingu
     * @param string $path      Ścieżka do sprawdzenia
     *
     * @return bool
     */
    public function validate(string $routeName, string $path): bool
    {
        if (!isset($this->rules[$routeName]) || ($regexp = $this->prepareRegexp($this->rules[$routeName]['path'])) === null) {
            return false;
        }

        if (($strPos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $strPos);
        }

        return (bool)preg_match($regexp, $path);
    }

    /**
     * Na podstawie przekazanej przez użytkownika ścieżki dobiera odpowiedni routing
     *
     * @return string|null Nazwa routingu lub null w przypadku niepowodzenia
     */
    private function analyzePath(): ?string
    {
        foreach ($this->rules as $routingRuleName => $routingRule) {
            if (isset($routingRule['allowedHttpMethods']) && is_array($routingRule['allowedHttpMethods'])
                && !in_array($this->request->getRequestMethod(), $routingRule['allowedHttpMethods'])) {
                continue;
            }

            $routingRule['path'] = is_array($routingRule['path']) ? $routingRule['path'] : [$routingRule['path']];
            foreach ($routingRule['path'] as $path) {
                $regexp = $this->prepareRegexp($path);
                if (!preg_match($regexp, $this->request->getPath(), $matches)) {
                    continue;
                }

                if (isset($matches[self::MULTI_PARAMS_PATTERN])) {
                    $multiParams = array_values(array_filter(explode('/', $matches[self::MULTI_PARAMS_PATTERN])));
                    $nOfEle = count($multiParams);
                    if ($nOfEle % 2 !== 0) {
                        unset($multiParams[--$nOfEle]);
                    }

                    for ($index = 0; $index < $nOfEle - 1; $index += 2) {
                        $this->request->appendGet($multiParams[$index], $multiParams[$index + 1]);
                    }

                    unset($matches[self::MULTI_PARAMS_PATTERN]);
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
     * @param string $routeName
     * @param array  $params
     * @param array  $query
     *
     * @return string|null
     */
    public function makePath(string $routeName, array $params = [], array $query = []): ?string
    {
        if (!isset($this->rules[$routeName])) {
            return null;
        }

        $path = $this->rules[$routeName]['path'];

        foreach ($params as $name => $value) {
            if (is_array($value)) {
                continue;
            }

            $path = preg_replace('/{' . $name . '(?::([a-zA-Z]+))?}/', $value, $path, 1);
        }

        if (isset($params[self::MULTI_PARAMS_PATTERN]) && is_array($params[self::MULTI_PARAMS_PATTERN])) {
            $multiParams = [];
            foreach ($params[self::MULTI_PARAMS_PATTERN] as $name => $value) {
                $multiParams[] = $name;
                $multiParams[] = $value;
            }

            $multiParams = implode('/', $multiParams);
            $path = str_replace('{' . self::MULTI_PARAMS_PATTERN . '}', '/' . $multiParams, $path);
        }

        $path = preg_replace('/\[[^[]*?[{.*}]\]/', '', $path);
        $path = preg_replace('/{.*?}/', '', $path);
        $path = str_replace(['[', ']'], ['', ''], $path);

        if (!empty($query)) {
            $path .= '?' . http_build_query($query);
        }

        return $this->validate($routeName, $path) ? $path : null;
    }

    /**
     * Uruchamia routing
     *
     * @return array
     */
    public function run(): array
    {
        $class = $method = null;
        if (($routingRuleName = $this->analyzePath()) !== null) {
            $class = !empty($this->rules[$routingRuleName]['class']) ? $this->rules[$routingRuleName]['class'] : null;
            $method = !empty($this->rules[$routingRuleName]['method']) ? $this->rules[$routingRuleName]['method'] : self::DEFAULT_METHOD;
        }

        return [$class, $method];
    }
}
