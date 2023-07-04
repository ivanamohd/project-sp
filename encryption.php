<?php
// Encrypt data using AES encryption
function encryptData($data, $key)
{
    // Generate an initialization vector (IV)
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

    // Encrypt the data with the key and IV
    $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

    // Combine the encrypted data and IV into a single string
    $encryptedString = base64_encode($iv . $encryptedData);

    return $encryptedString;
}

// Decrypt data using AES encryption
function decryptData($encryptedString, $key)
{
    // Decode the encrypted string and extract the IV and encrypted data
    $decodedString = base64_decode($encryptedString);
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($decodedString, 0, $ivLength);
    $encryptedData = substr($decodedString, $ivLength);

    // Decrypt the data with the key and IV
    $decryptedData = openssl_decrypt($encryptedData, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

    return $decryptedData;
}