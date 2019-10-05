<?php

namespace App\Response;


class Stream extends AbstractResponse implements DownloadableInterface
{
    /**
     * @var \Generator
     */
    private $streamGenerator;

    public function __construct(string $filename, \Generator $streamGenerator, ?string $contentType = null)
    {
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $this->streamGenerator = $streamGenerator;

        if (is_string($contentType)) {
            $this->setContentType($contentType);
        } else {
            $this->setContentType(Type::APPLICATION_OCTET_STREAM);
        }
    }

    /**
     * Wysyła plik
     */
    public function send(): void
    {
        session_write_close();
        foreach ($this->streamGenerator as $buffer) {
            if (connection_aborted() === CONNECTION_ABORTED) {
                return;
            }

            set_time_limit(0);
            echo $buffer;
            flush();
        }
    }
}
