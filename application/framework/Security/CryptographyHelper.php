<?php

namespace App\Security;


class CryptographyHelper
{
    /**
     * Stale zawierajace nazwy funkcji skrotow
     */
    const HASH_SHA256 = 'sha256';
    const HASH_SHA512 = 'sha512';

    /**
     * Tworzy skrot na podstawie przekazanego ciagu znakow.
     *
     * @param string $data      Dane
     * @param string $algorithm Nazwa algorytmu
     *
     * @return string
     */
    public static function hash($data, $algorithm = self::HASH_SHA256)
    {
        return hash($algorithm, $data);
    }

    /**
     * Tworzy skrot na podstawie zawartosci pliku
     *
     * @param string $filePath  Sciezka do pliku
     * @param string $algorithm Nazwa algorytmu
     *
     * @return string
     */
    public static function hashFile($filePath, $algorithm = self::HASH_SHA256)
    {
        return hash_file($algorithm, $filePath);
    }

    /**
     * Zwraca ciag losowych bajtow danych.
     *
     * @param int $length Dlugosc ciagu
     *
     * @return string
     */
    public static function getRandomBytes($length = 20): string
    {
        return openssl_random_pseudo_bytes($length);
    }
}
