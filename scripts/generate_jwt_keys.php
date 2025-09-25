<?php

echo "Génération des clés JWT...\n";

// Générer la clé privée
$privateKey = openssl_pkey_new([
    'private_key_bits' => 4096,
    'private_key_type' => OPENSSL_KEYTYPE_RSA
]);

if (!$privateKey) {
    echo "Erreur lors de la génération de la clé privée\n";
    exit(1);
}

// Exporter la clé privée
openssl_pkey_export($privateKey, $privateKeyPEM);

// Obtenir la clé publique
$publicKeyDetails = openssl_pkey_get_details($privateKey);
$publicKey = $publicKeyDetails['key'];

// Sauvegarder les clés
file_put_contents('config/jwt/private.pem', $privateKeyPEM);
file_put_contents('config/jwt/public.pem', $publicKey);

echo "Clés JWT générées avec succès !\n";
echo "- Clé privée : config/jwt/private.pem\n";
echo "- Clé publique : config/jwt/public.pem\n";

// Nettoyer
openssl_pkey_free($privateKey); 