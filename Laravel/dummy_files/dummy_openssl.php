<?php

if (!function_exists("openssl_cipher_iv_length")) {
    function openssl_cipher_iv_length(string $method)
    {
        return 32;
    }
}

if (!function_exists("openssl_encrypt")) {
    function openssl_encrypt(string $data, string $method, string $key, int $options = 0, string $iv = "", string &$tag = null, string $aad = "", int $tag_length = 16): string
    {
        return $data;
    }
}

if (!function_exists("openssl_decrypt")) {
    function openssl_decrypt(string $data, string $method, string $key, int $options = 0, string $iv = "", string $tag = "", string $aad = ""): string
    {
        return $data;
    }
}