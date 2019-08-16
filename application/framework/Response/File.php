<?php

namespace App\Response;


class File extends AbstractResponse implements DownloadableInterface
{
    /**
     * @var resource
     */
    private $file;

    /**
     * Rozmiar pliku
     *
     * @var int
     */
    private $size;

    /**
     * Rozmiar pobieranej zawartości
     *
     * @var int
     */
    private $length;

    /**
     * Numer bajta początku
     *
     * @var int
     */
    private $start;

    /**
     * Numer bajta konca
     *
     * @var int
     */
    private $end;

    /**
     * Czy jest możliwe wysłanie pliku?
     *
     * @var bool
     */
    private $sendFile = true;

    /**
     * Domyślna maksymalna przepustowość
     */
    private const DEFAULT_BANDWIDTH = 1024 * 1024;

    const BANDWIDTH_NO_LIMIT = -1;

    /**
     * Maksymalna przepustowość pobierania
     *
     * @var int|null
     */
    private $bandwidth = self::DEFAULT_BANDWIDTH;

    const CONTEXT_INLINE = 'inline';
    const CONTEXT_ATTACHMENT = 'attachment';

    public function __construct(string $filepath, ?string $contentType = null, string $context = self::CONTEXT_INLINE)
    {
        if (!file_exists($filepath)) {
            throw new \RuntimeException(sprintf('File %s doesn\'t exist', basename($filepath)));
        }

        $this->file = @fopen($filepath, 'rb');
        $this->size = filesize($filepath);
        $this->length = $this->size;
        $this->start = 0;
        $this->end = $this->size - 1;

        $context = in_array($context, [self::CONTEXT_INLINE, self::CONTEXT_ATTACHMENT]) ? $context : self::CONTEXT_INLINE;
        header('Content-Disposition: ' . $context . '; filename="' . basename($filepath) . '"');

        if (is_string($contentType)) {
            $this->setContentType($contentType);
        } else {
            $this->setContentType(Type::APPLICATION_OCTET_STREAM);
        }

        header('Accept-Ranges: bytes');
        $this->checkRange();
    }

    /**
     * Reagujemy na wybór zakresu pliku poprzez ustawienie wskaźnika pliku na odpowiedniej pozycji
     */
    private function checkRange(): void
    {
        if (!isset($_SERVER['HTTP_RANGE'])) {
            return;
        }

        $cursorEnd = $this->end;
        [, $range] = explode('=', $_SERVER['HTTP_RANGE'], 2);
        if (strpos($range, ',') !== false) {
            $this->rangeIsNotSatisfiable();
            return;
        }
        if ($range === '-') {
            $cursorStart = $this->size - substr($range, 1);
        } else {
            $range = explode('-', $range);
            $cursorStart = $range[0];
            $cursorEnd = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $this->size;
        }

        $cursorEnd = ($cursorEnd > $this->end) ? $this->end : $cursorEnd;
        if ($cursorStart > $cursorEnd || $cursorStart > $this->size - 1 || $cursorEnd >= $this->size) {
            $this->rangeIsNotSatisfiable();
            return;
        }

        $this->start = $cursorStart;
        $this->end = $cursorEnd;
        $this->length = $this->end - $this->start + 1;
        fseek($this->file, $this->start);
        $this->setCode(Code::PARTIAL_CONTENT);
    }

    /**
     * Oznaczamy wybór zakresu pliku jako nieprawidłowy
     */
    private function rangeIsNotSatisfiable()
    {
        $this->sendFile = false;

        $this->setCode(Code::RANGE_NOT_SATISFIABLE);
        header("Content-Range: bytes $this->start-$this->end/$this->size");
    }

    /**
     * Ustawia przepustowość wyr. w bajtach
     *
     * @param int $bandwidth
     *
     * @return $this
     */
    public function setBandwidth(int $bandwidth = self::BANDWIDTH_NO_LIMIT): File
    {
        $this->bandwidth = $bandwidth;

        return $this;
    }

    /**
     * Wysyła plik
     */
    public function send(): void
    {
        if (!$this->sendFile) {
            return;
        }

        header("Content-Range: bytes $this->start-$this->end/$this->size");
        header("Content-Length: " . $this->length);

        if ($noLimit = ($this->bandwidth === self::BANDWIDTH_NO_LIMIT)) {
            $buffer = self::DEFAULT_BANDWIDTH;
        } else {
            $buffer = $this->bandwidth;
        }

        session_write_close();
        while (connection_aborted() !== CONNECTION_ABORTED && !feof($this->file) && ($position = ftell($this->file)) <= $this->end) {
            if ($position + $buffer > $this->end) {
                $buffer = $this->end - $position + 1;
            }

            set_time_limit(0);
            echo fread($this->file, $buffer);
            flush();

            if (!$noLimit) {
                sleep(1);
            }
        }

        fclose($this->file);
    }
}
