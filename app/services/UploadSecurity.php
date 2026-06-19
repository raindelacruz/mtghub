<?php

final class UploadSecurity
{
    public const MAX_IMAGE_SIZE = 5242880;

    public static function validatePaymentProof(array $file, bool $requireHttpUpload = true): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !isset($file['tmp_name'], $file['size'], $file['name'])) throw new RuntimeException('Choose a payment-proof image to upload.');
        if ((int) $file['size'] < 1 || (int) $file['size'] > self::MAX_IMAGE_SIZE || ($requireHttpUpload && !is_uploaded_file($file['tmp_name']))) throw new RuntimeException('Payment proof must be a valid image no larger than 5 MB.');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $dimensions = @getimagesize($file['tmp_name']);
        if (!isset($allowed[$mime]) || $dimensions === false || $dimensions[0] < 1 || $dimensions[1] < 1 || $dimensions[0] > 8000 || $dimensions[1] > 8000) throw new RuntimeException('Only valid JPEG, PNG, or WebP images up to 8,000 pixels per side are accepted.');
        return ['tmp_name' => $file['tmp_name'], 'size' => (int) $file['size'], 'name' => mb_substr(basename(str_replace('\\', '/', $file['name'])), 0, 255), 'mime_type' => $mime, 'extension' => $allowed[$mime], 'width' => (int) $dimensions[0], 'height' => (int) $dimensions[1]];
    }
}
