<?php
// Encryption key configuration
// IMPORTANT: In production, store this key securely (e.g., environment variable, key management service)
// For development, generate a strong random key and keep it secret
define('ENCRYPTION_KEY', 'MPLC7l9UHbSCjgWOg19TtBj4VPz2leQb'); // 32-character key for development

// Initialize encryption with the key
require_once __DIR__ . '/../utils/encryption.php';
Encryption::setKey(ENCRYPTION_KEY);
?>
