<?php

namespace View\Alerts;


class Alert
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var int
     */
    private $type;

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @param string $content
     *
     * @return $this
     */
    public function setMessage(string $content): Alert
    {
        $this->message = $content;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getType(): ?int
    {
        return $this->type;
    }

    /**
     * @param int $type
     *
     * @return $this
     */
    public function setType(int $type): Alert
    {
        $this->type = $type;

        return $this;
    }
}
