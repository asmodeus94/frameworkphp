<?php

namespace App\Response;


use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class View extends AbstractResponse
{
    /**
     * @var string
     */
    private $layout;

    /**
     * @var array
     */
    private $variables;

    /***
     * @var Environment
     */
    private $twig;

    private const BASE_LAYOUT = 'base.twig';

    public function __construct(string $layout = null, array $variables = [])
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

    private function loadFunctions(): void
    {
        $this->twig->addFunction(new TwigFunction('asset', function ($asset) {
            return sprintf('/public/%s', ltrim($asset, '/'));
        }));
    }

    /**
     * @param string $fileName
     *
     * @return $this
     */
    public function setLayout(string $fileName = self::BASE_LAYOUT): View
    {
        $this->layout = $fileName;

        return $this;
    }

    /**
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
     * @param string $key
     * @param mixed  $variable
     */
    public function appendVariable(string $key, $variable): void
    {
        $this->variables[$key] = $variable;
    }

    /**
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
