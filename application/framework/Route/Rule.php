<?php

namespace App\Route;


use App\Request;

class Rule
{
    /**
     * @var string|null
     */
    private $class = null;

    /**
     * @var string|null
     */
    private $method = null;

    /**
     * @var array
     */
    private $allowedHttpMethods = Request::ALLOWED_HTTP_METHODS;

    /**
     * @var array
     */
    private $paths = [];

    /**
     * @var bool
     */
    private $allowedCli = false;

    /**
     * @var bool
     */
    private $allowedLoggedOnly = false;

    /**
     * @var bool
     */
    private $allowedForAll = false;

    /**
     * @var array
     */
    private $roles = [];

    /**
     * Rule constructor.
     *
     * @param string|null $path
     * @param string|null $class
     * @param string|null $method
     */
    public function __construct(?string $path = null, ?string $class = null, ?string $method = null)
    {
        if ($path !== null) {
            $this->setPath($path);
        }

        if ($class !== null) {
            $this->setClass($class);
        }

        if ($method !== null) {
            $this->setMethod($method);
        }
    }

    /**
     * @return string|null
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * @return array
     */
    public function getAllowedHttpMethods(): array
    {
        return $this->allowedHttpMethods;
    }

    /**
     * @return bool
     */
    public function isAllowedCli(): bool
    {
        return $this->allowedCli;
    }

    /**
     * @param string $class
     *
     * @return $this
     */
    public function setClass(string $class): Rule
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @param string $method
     *
     * @return $this
     */
    public function setMethod(string $method): Rule
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @param array $allowedHttpMethods
     *
     * @return $this
     */
    public function setAllowedHttpMethods(array $allowedHttpMethods): Rule
    {
        $this->allowedHttpMethods =
            empty(array_diff($allowedHttpMethods, Request::ALLOWED_HTTP_METHODS))
                ? $allowedHttpMethods : Request::ALLOWED_HTTP_METHODS;

        return $this;
    }

    /**
     * @param array $paths
     *
     * @return $this
     */
    public function setPaths(array $paths): Rule
    {
        $this->paths = $paths;

        return $this;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setPath(string $path): Rule
    {
        $this->paths[] = $path;

        return $this;
    }

    /**
     * @param bool $allowedCli
     *
     * @return $this
     */
    public function allowedCli(bool $allowedCli = true): Rule
    {
        $this->allowedCli = $allowedCli;

        return $this;
    }

    /**
     * @return $this
     */
    public function allowedOnlyCli(): Rule
    {
        return $this->setAllowedHttpMethods([])->allowedCli();
    }

    /**
     * @param array $roles
     *
     * @return $this
     */
    public function setAssignedRoles(array $roles): Rule
    {
        $this->roles = $roles;

        return $this->allowedLoggedOnly();
    }

    /**
     * @param string $role
     *
     * @return $this
     */
    public function setAssignedRole(string $role): Rule
    {
        $this->roles[] = $role;

        return $this->allowedLoggedOnly();
    }

    /**
     * @return array
     */
    public function getAssignedRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param bool $allowedLoggedOnly
     *
     * @return $this
     */
    public function allowedLoggedOnly(bool $allowedLoggedOnly = true): Rule
    {
        $this->allowedLoggedOnly = $allowedLoggedOnly;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowedLoggedOnly(): bool
    {
        return $this->allowedLoggedOnly;
    }

    /**
     * @return bool
     */
    public function isAllowedForEveryone(): bool
    {
        return $this->allowedForAll;
    }

    /**
     * @param bool $allowedForAll
     *
     * @return $this
     */
    public function setAllowedForEveryone(bool $allowedForAll = true): Rule
    {
        $this->allowedForAll = $allowedForAll;

        return $this;
    }
}
