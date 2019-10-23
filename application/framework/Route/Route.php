<?php

namespace App\Route;


use App\Helper\ServerHelper;
use App\Request;
use App\Session;

class Route
{
    /**
     * @var Route
     */
    private static $instance;

    /**
     * @var Rule[]
     */
    private $rules = [];

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Session
     */
    private $session;

    /**
     * Pasująca nazwa routingu
     *
     * @var string
     */
    private $routingRuleName;

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

    /**
     * Wyrażenia regularne użytkownika wraz z predefiniowanymi
     *
     * @var array
     */
    private $patterns = [];

    private function __construct()
    {
        $this->loadRules();
        $this->loadPatterns();
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
     * @param Session $session
     *
     * @return Route
     */
    public function setSession(Session $session): Route
    {
        $this->session = $session;

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
     * @return string
     */
    public function getRoutingRuleName(): string
    {
        return $this->routingRuleName;
    }

    /**
     * Ładuje do tablicy wszystkie reguły dot. routingu
     */
    private function loadRules(): void
    {
        $routingFiles = array_diff(scandir(ROUTING_RULES), ['.', '..']);
        foreach ($routingFiles as $routingFile) {
            if (strpos($routingFile, '.php') === false) {
                continue;
            }

            $routingGroupName = str_replace('.php', '', $routingFile);
            $routingGroupRules = require ROUTING_RULES . $routingFile;

            foreach ($routingGroupRules as $routingRuleName => $routingRule) {
                $routingGroupRuleName = $routingGroupName . '-' . $routingRuleName;
                if (!($routingRule instanceof Rule)) {
                    throw new \RuntimeException(sprintf('$routingRule (%s) is not an instance of Rule class', $routingGroupRuleName));
                }

                if ($routingRule->getClass() === null) {
                    throw new \RuntimeException(sprintf('$routingRule\'s (%s) class was not provided', $routingGroupRuleName));
                }

                if ($routingRule->getMethod() === null) {
                    throw new \RuntimeException(sprintf('$routingRule\'s (%s) method was not set', $routingGroupRuleName));
                }

                if (empty($routingRule->getPaths())) {
                    throw new \RuntimeException(sprintf('$routingRule\'s (%s) paths cannot be null', $routingGroupRuleName));
                }

                $this->rules[$routingGroupRuleName] = $routingRule;
            }
        }
    }

    /**
     * Ładuje wzorce jakie mogą zostać użyte w dowolnych regułach routingu
     */
    private function loadPatterns(): void
    {
        $this->patterns = require ROUTING_PATTERNS;
        foreach (self::PREDEFINED_PATTERNS_MAP as $patternName => $regexp) {
            $this->patterns[$patternName] = $regexp;
        }
    }

    /**
     * Na podstawie reguły routingu (jej ścieżki) tworzy wyr. regularne umożliwiające sprawdzenie poprawności ścieżki
     *
     * @param string $path
     *
     * @return string
     */
    private function prepareRegexp(string $path): string
    {
        $regexp = preg_replace('/\[(.*?)]/', '(?:$1)?', str_replace(['/', '-'], ['\/', '\-'], $path));
        preg_match_all('/(?:{[a-zA-Z_][a-zA-Z0-9_]+(?::[a-zA-Z]+)?})/', $regexp, $placeholderGroups);
        $placeholderGroups = $placeholderGroupsCopy = $placeholderGroups[0];
        foreach ($placeholderGroups as &$placeholder) {
            if (!preg_match('/{([a-zA-Z_][a-zA-Z0-9_]+)(?::([a-zA-Z]+))?}/', $placeholder, $placeholderAnalyzed)) {
                continue;
            }

            $placeholderRegexp = null;
            if ($placeholderAnalyzed[1] === self::MULTI_PARAMS_PATTERN) {
                $placeholderRegexp = self::PREDEFINED_PATTERNS_MAP[self::MULTI_PARAMS_PATTERN];
            } elseif (isset($placeholderAnalyzed[2]) && isset($this->patterns[$placeholderAnalyzed[2]])) {
                $placeholderRegexp = $this->patterns[$placeholderAnalyzed[2]];
            }

            if ($placeholderRegexp === null) {
                $placeholderRegexp = '[\w-]+?';
            }

            $pattern = '(?<' . $placeholderAnalyzed[1] . '>' . $placeholderRegexp . ')';
            $placeholder = str_replace($placeholderAnalyzed[0], $pattern, $placeholder);
        }

        return '/^' . str_replace($placeholderGroupsCopy, $placeholderGroups, $regexp) . '$/';
    }

    /**
     * Sprawdza czy podana ścieżka pasuje do podanego wyrażenia regularnego
     *
     * @param string $path   Ścieżka do sprawdzenia
     * @param string $regexp Sprawdzające wyrażenie regularne
     *
     * @return bool
     */
    private function validate(string $path, string $regexp): bool
    {
        if (($strPos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $strPos);
        }

        return (bool)preg_match($regexp, $path);
    }

    /**
     * Sprawdza czy możliwe jest skorzystanie z podanej reguły
     *
     * @param Rule $rule
     *
     * @return bool
     */
    private function isAllowed(Rule $rule): bool
    {
        if (!ServerHelper::isCLI()) {
            $allowed =
                in_array($this->request->getRequestMethod(), $rule->getAllowedHttpMethods())
                && ($rule->isAllowedForEveryone() ||
                    (
                        ($rule->isAllowedLoggedOnly() === $this->session->isLogged())
                        && (empty($assignedRoles = $rule->getAssignedRoles()) || in_array($this->session->getRole(), $assignedRoles))
                    )
                );
        } else {
            $allowed = $rule->isAllowedCli();
        }

        return $allowed;
    }

    /**
     * Na podstawie przekazanej przez użytkownika ścieżki dobiera odpowiedni routing
     *
     * @return string|null Nazwa routingu lub null w przypadku niepowodzenia
     */
    private function analyzePath(): ?string
    {
        foreach ($this->rules as $routingRuleName => $routingRule) {
            if (!$this->isAllowed($routingRule)) {
                continue;
            }

            foreach ($routingRule->getPaths() as $path) {
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
     * Dla podanego routingu, parametrów oraz ew. dodatkowych parametrów (po ?) tworzy względną ścieżkę
     *
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

        foreach ($this->rules[$routeName]->getPaths() as $path) {
            $rawPath = $path;
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

            $path = str_replace(['[', ']'], ['', ''], preg_replace(['/\[[^[]*?[{.*}]]/', '/{.*?}/'], ['', ''], $path));

            if (!empty($query)) {
                $path .= '?' . http_build_query($query);
            }

            if ($this->validate($path, $this->prepareRegexp($rawPath))) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Uruchamia routing
     *
     * @return array
     */
    public function run(): array
    {
        $class = $method = null;
        if (($this->routingRuleName = $this->analyzePath()) !== null) {
            $class = $this->rules[$this->routingRuleName]->getClass();
            $method = $this->rules[$this->routingRuleName]->getMethod();
        }

        return [$class, $method];
    }
}
