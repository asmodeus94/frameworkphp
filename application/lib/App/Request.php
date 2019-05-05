<?php

namespace App;


use App\Cookie\Cookie;

class Request
{
    /**
     * @var Request
     */
    private static $instance;

    /**
     * @var string|null
     */
    private $requestMethod = null;

    /**
     * @var string|null
     */
    private $path = null;

    /**
     * @var array|null
     */
    private $get = null;

    /**
     * @var Cookie[]|null
     */
    private $cookies = null;

    /**
     * @var array|null
     */
    private $post = null;

    const ALLOWED_HTTP_METHODS = ['GET', 'POST'];


    private function __construct()
    {
        $requestMethod = strtoupper((string)$_SERVER['REQUEST_METHOD']);
        $this->requestMethod = in_array($requestMethod, self::ALLOWED_HTTP_METHODS) ? $requestMethod : null;
        $this->collectData();
    }

    /**
     * @return Request
     */
    public static function getInstance(): Request
    {
        if (!isset(self::$instance)) {
            self::$instance = new Request();
        }

        return self::$instance;
    }

    /**
     * Wypełnia tablice get i post parametrami
     */
    private function collectData(): void
    {
        if ($this->requestMethod === 'POST' && !empty($_POST)) {
            $this->post = $_POST;
        }

        if (!empty($_COOKIE)) {
            $this->cookies = [];
            foreach ($_COOKIE as $name => $content) {
                $this->cookies[$name] = new Cookie($name, $content);
            }
        }

        if (!isset($_GET)) {
            return;
        }

        $this->path = !empty($_GET['_path_']) ? $_GET['_path_'] : null;
        unset($_GET['_path_']);

        if (empty($_GET)) {
            return;
        }

        $this->get = [];

        foreach ($_GET as $param => $value) {
            $this->get[$param] = $value;
        }
    }

    /**
     * Dodaje parametr wraz z wartością do tablicy get
     *
     * @param string $param
     * @param string $value
     */
    public function appendGet(string $param, string $value): void
    {
        $this->get[$param] = $value;
    }

    /**
     * @return string
     */
    public function getRequestMethod(): string
    {
        return $this->requestMethod;
    }

    /**
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @return array|null
     */
    public function get(): ?array
    {
        return $this->get;
    }

    /**
     * @return array|null
     */
    public function post(): ?array
    {
        return $this->post;
    }

    /**
     * @return Cookie[]|null
     */
    public function cookies(): ?array
    {
        return $this->cookies;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter(string $name)
    {
        if (isset($this->get[$name])) {
            return $this->get[$name];
        }

        if (isset($this->post[$name])) {
            return $this->post[$name];
        }

        return null;
    }
}
