<?php

namespace App;


use App\Helper\ServerHelper;

class Session
{
    public function __construct()
    {
        if (!isset($_SESSION) && session_id() === '' && !ServerHelper::isCli()) {
            session_start();
        }
    }

    /**
     * Generuje i zwraca nowe id sesji
     *
     * @param bool $deleteOldSession Ustawione na true usuwa plik sesji na serwerze
     *
     * @return bool
     */
    public function refresh($deleteOldSession = true): bool
    {
        return session_regenerate_id($deleteOldSession);
    }

    /**
     * Zwraca wartość zmniennej sesyjnej
     *
     * @param string $index Klucz zmiennej
     *
     * @return mixed
     */
    public function get(string $index)
    {
        if (!isset($_SESSION[$index])) {
            return null;
        }

        return $_SESSION[$index];
    }

    /**
     * Zwraca całą zawartość tablicy sesyjnej
     *
     * @return string
     */
    public function dump(): string
    {
        ob_start();
        var_dump($_SESSION);

        return ob_get_clean();
    }

    /**
     * Ustawia wartość zmiennej sesyjnej
     *
     * @param string $index Klucz zmiennej
     * @param mixed  $value Wartość zmiennej
     */
    public function set(string $index, $value): void
    {
        $_SESSION[$index] = $value;
    }

    /**
     * Zwraca dana zmienna, a nastepnie usuwa ja z sesji
     *
     * @param string $index Klucz zmiennej
     */
    public function remove(string $index): void
    {
        unset($_SESSION[$index]);
    }

    /**
     * Usuwa zmienne sesyjne, niszczy sesje i rozpoczyna nowa
     */
    public function destroy()
    {
        if (!ServerHelper::isCli()) {
            session_unset();
            session_destroy();
            session_start();
        }
    }
}
