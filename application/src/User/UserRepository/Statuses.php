<?php

namespace User\UserRepository;


class Statuses
{
    const SUCCESS = 1;

    const ERROR_PASSWORDS_MISMATCH = 2;
    const ERROR_PASSWORD_LENGTH = 4;
    const ERROR_PASSWORD_WHITESPACES = 8;
    const ERROR_PASSWORD_WEAK = 16;

    const ERROR_EMAIL_INVALID = 32;

    const ERROR_LOGIN_ILLEGAL_CHARACTERS = 64;
    const ERROR_LOGIN_LENGTH = 128;

    const ERROR_NICK_ILLEGAL_CHARACTERS = 256;
    const ERROR_NICK_LENGTH = 512;
}
