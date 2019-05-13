<?php

namespace App\Response;


use App\Helper\RouteHelper;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class View extends AbstractResponse
{
    /**
     * Wybrany szablon (względna ściezka do pliku)
     *
     * @var string
     */
    private $layout;

    /**
     * Przekazane zmienne do widoku
     *
     * @var array
     */
    private $variables;

    /***
     * @var Environment
     */
    private $twig;

    /**
     * Domyślny szablon (względna ścieżka do pliku)
     */
    private const BASE_LAYOUT = 'base.twig';

    public function __construct(?string $layout = null, array $variables = [])
    {
        $loader = new FilesystemLoader(TWIG_TEMPLATES);
        $this->twig = new Environment($loader);
        $this->loadFunctions();
        $this->setVariables($variables);

        if ($layout !== null) {
            $this->setLayout($layout);
        } else {
            $this->setLayout();
        }
    }

    /**
     * Rozszerze twiga o funkcje możliwe do wywołania w widoku
     */
    private function loadFunctions(): void
    {
        $this->twig->addFunction(new TwigFunction('asset', function (?string $asset) {
            return sprintf('/public/%s', ltrim($asset, '/'));
        }));

        $this->twig->addFunction(new TwigFunction('path', function (?string $routeName, ?array $params = [], ?array $query = []) {
            return '/' . RouteHelper::path($routeName, $params, $query);
        }));
    }

    /**
     * Ustawia layout
     *
     * @param string $filePath Względna ścieżka do pliku
     *
     * @return $this
     */
    public function setLayout(string $filePath = self::BASE_LAYOUT): View
    {
        $this->layout = $filePath;

        return $this;
    }

    /**
     * Dodaje (nadpisuje) tablicę zmiennych
     *
     * @param array $variables
     *
     * @return $this
     */
    public function setVariables(array $variables): View
    {
        $this->variables = $variables;

        return $this;
    }

    /**
     * Dodaje do tablicy zmienną dla podanego klucza
     *
     * @param string $key
     * @param mixed  $variable
     *
     * @return $this
     */
    public function appendVariable(string $key, $variable): View
    {
        $this->variables[$key] = $variable;

        return $this;
    }

    /**
     * Zwraca html
     *
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function send(): string
    {
        return $this->twig->render($this->layout, $this->variables);
    }
}
