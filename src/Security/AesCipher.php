<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AesCipher
{
    private const OPENSSL_CIPHER_NAME = "aes-128-cbc";
    private const CIPHER_KEY_LEN = 16;

    private string $hashkey;
    private string $initVector;

    public function __construct(ParameterBagInterface $params)
    {
        $this->hashkey = $params->get('menu_ent_hashkey');
        $this->initVector = $params->get('menu_ent_init_vector');
    }

    public function encrypt(string $plaintText): string
    {
        $encryptedText = openssl_encrypt($plaintText,
            AesCipher::OPENSSL_CIPHER_NAME,
            $this->hashkey,
            OPENSSL_RAW_DATA,
            $this->initVector);

        return base64_encode($encryptedText);
    }
}