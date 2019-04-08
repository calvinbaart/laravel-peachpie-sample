<?php

interface SessionUpdateTimestampHandlerInterface
{
    /* Methods */
    function updateTimestamp(string $key, string $val) : bool;
    function validateId(string $key) : bool;
}

function openssl_cipher_iv_length(string $method)
{
    return 32;
}

function openssl_encrypt(string $data, string $method, string $key, int $options = 0, string $iv = "", string &$tag = null, string $aad = "", int $tag_length = 16): string
{
    return $data;
}

function openssl_decrypt(string $data, string $method, string $key, int $options = 0, string $iv = "", string $tag = "", string $aad = ""): string
{
    return $data;
}

// namespace PhpSpec;

// class ObjectBehavior
// {
// }