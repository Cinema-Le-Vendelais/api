<?php

class Encrypter
{
    private string $key;

    /**
     * Génère une clé à partir d’un mot de passe
     *
     * @param string $password
     * @param string $salt
     */
    public function __construct(string $password, string $salt)
    {
        // Clé de 32 octets (pour AES-256 équivalent)
        $this->key = sodium_crypto_pwhash(
            SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
            $password,
            base64_decode($salt, true), // Stocké en base64 dans le .env
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_MODERATE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_MODERATE
        );
    }

    /**
     * Chiffre une chaîne
     *
     * @param string $plaintext
     * @return string
     */
    public function encrypt(string $plaintext): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($plaintext, $nonce, $this->key);
        // Ajout de sécurité
        return base64_encode($nonce . $cipher);
    }

    /**
     * Déchiffre une chaîne
     *
     * @param string $encrypted
     * @return string
     * @throws Exception
     */
    public function decrypt(string $encrypted): string
    {
        $decoded = base64_decode($encrypted, true);
        if ($decoded === false || strlen($decoded) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new Exception('Texte chiffré invalide.');
        }

        $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $cipher = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

        $plaintext = sodium_crypto_secretbox_open($cipher, $nonce, $this->key);
        if ($plaintext === false) {
            throw new Exception('Échec du déchiffrement.');
        }

        return $plaintext;
    }
}