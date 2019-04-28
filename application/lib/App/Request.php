<?php

namespace App;


use App\Cookie\Cookie;

class Request
{
    /**
     * @var string
     */
    private $requestMethod;

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

    private static $instance;

    private function __construct()
    {
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
     *
     */
    private function collectData(): void
    {
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->initParams();
    }

    /**
     *
     */
    private function initParams(): void
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
     * @param string $param
     * @param string $value
     */
    public function appendGet(string $param, string $value): void
    {
        $this->get[$param] = $value;
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
}
