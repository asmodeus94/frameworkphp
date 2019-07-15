<?php

namespace View;


class Message
{
    /**
     * @var string
     */
    private $content;

    /**
     * @var int
     */
    private $type;

    const TYPE_SUCCESS = 1;
    const TYPE_ERROR = 2;

    /**
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return $this
     */
    public function setContent(string $content): Message
    {
        $this->content = $content;

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
    public function setType(int $type): Message
    {
        $this->type = $type;

        return $this;
    }
}
