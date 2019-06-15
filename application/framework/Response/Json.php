<?php

namespace App\Response;


class Json extends AbstractResponse
{
    /**
     * @var array|string
     */
    private $data;

    /**
     * Json constructor.
     *
     * @param array|string $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Przygotowuje odpowiedź
     */
    private function prepareSkeleton(): void
    {
        $hasErrors = !empty($this->data['errors']);

        if (!isset($this->data['status'])) {
            $this->data['status'] = !$hasErrors ? 'ok' : 'error';
        }

        if (!isset($this->data['content'])) {
            $this->data['content'] = '';
        }

        if (!$hasErrors && !isset($this->data['errors'])) {
            $this->data['errors'] = [];
        }

        krsort($this->data);
    }

    /**
     * @return string
     */
    public function encode(): string
    {
        if (is_array($this->data)) {
            $this->prepareSkeleton();
            $this->data = json_encode($this->data);
        }

        return $this->data;
    }

    /**
     * @return array
     */
    public function decode(): array
    {
        if (is_string($this->data)) {
            $this->data = json_decode($this->data, true);
        }

        return $this->data;
    }

    /**
     * @return string
     */
    public function send(): string
    {
        return $this->data;
    }
}
