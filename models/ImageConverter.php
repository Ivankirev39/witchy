<?php

class ImageConverter
{
    // Maximum output dimension (longest side).
    private const MAX_DIMENSION = 2560;

    // Image compression settings.
    private const JPEG_QUALITY = 85;
    private const WEBP_QUALITY = 82;
    private const PNG_COMPRESSION = 6;

    // Estimated memory required per pixel, including processing overhead.
    private const MEMORY_PER_PIXEL = 10;

    // Additional memory reserved for PHP and GD operations.
    private const MEMORY_RESERVE = 16 * 1024 * 1024;

    /**
     * Convert an uploaded image and save it to the upload directory.
     *
     * @return array Converted filename, format and file size.
     */
    public function convert(
        string $sourcePath,
        string $uploadDirectory
    ): array {
        if (!is_file($sourcePath) || !is_readable($sourcePath)) {
            throw new RuntimeException("Invalid image file.");
        }

        if (!extension_loaded("gd")) {
            throw new RuntimeException("GD is not available.");
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($sourcePath);

        $allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp",
            "image/gif"
        ];

        if (!in_array($mimeType, $allowedTypes, true)) {
            throw new RuntimeException("Unsupported image format.");
        }

        $imageInfo = @getimagesize($sourcePath);

        if ($imageInfo === false) {
            throw new RuntimeException("Invalid image.");
        }

        $width = (int) $imageInfo[0];
        $height = (int) $imageInfo[1];

        if (
            $width < 1 ||
            $height < 1 ||
            $width > 6000 ||
            $height > 6000 ||
            ($width * $height) > 24000000
        ) {
            throw new RuntimeException("Image dimensions are too large.");
        }

        if (
            !is_dir($uploadDirectory) &&
            !mkdir($uploadDirectory, 0755, true) &&
            !is_dir($uploadDirectory)
        ) {
            throw new RuntimeException("Upload directory unavailable.");
        }

        // Preserve GIF files without changing their animation.
        if ($mimeType === "image/gif") {
            return $this->copyUnprocessedImage(
                $sourcePath,
                $uploadDirectory,
                "gif"
            );
        }

        // Preserve WebP when GD can read it but cannot write WebP output.
        if ($mimeType === "image/webp" && !function_exists("imagewebp")) {
            return $this->copyUnprocessedImage(
                $sourcePath,
                $uploadDirectory,
                "webp"
            );
        }

        // Load the image using the appropriate GD function.
        $loaders = [
            "image/jpeg" => "imagecreatefromjpeg",
            "image/png" => "imagecreatefrompng",
            "image/webp" => "imagecreatefromwebp"
        ];

        $loader = $loaders[$mimeType];

        // WebP decoding might not be available in local XAMPP.
        // In that case, preserve the validated file without processing.
        if (!function_exists($loader)) {
            if ($mimeType === "image/webp") {
                return $this->copyUnprocessedImage(
                    $sourcePath,
                    $uploadDirectory,
                    "webp"
                );
            }

            throw new RuntimeException("Image format cannot be processed.");
        }

        // Check available PHP memory before loading the image into GD.
        $this->checkMemoryLimit($width, $height);

        $sourceImage = @$loader($sourcePath);

        if ($sourceImage === false) {
            throw new RuntimeException("Could not load image.");
        }

        $outputImage = null;
        $outputPath = null;

        try {

            // Apply EXIF orientation for JPEG images.
            if ($mimeType === "image/jpeg") {
                $sourceImage = $this->applyJpegExifOrientation($sourceImage, $sourcePath);
                $width = imagesx($sourceImage);
                $height = imagesy($sourceImage);
            }

            // Check whether the image needs resizing.
            $wasResized = max($width, $height) > self::MAX_DIMENSION;

            // Resize while preserving the original aspect ratio.
            $scale = min(
                1,
                self::MAX_DIMENSION / max($width, $height)
            );

            $newWidth = max(1, (int) round($width * $scale));
            $newHeight = max(1, (int) round($height * $scale));

            $outputImage = imagecreatetruecolor(
                $newWidth,
                $newHeight
            );

            if ($outputImage === false) {
                throw new RuntimeException("Could not create output image.");
            }

            // Preserve transparency for PNG and WebP.
            if (in_array($mimeType, ["image/png", "image/webp"], true)) {
                imagealphablending($outputImage, false);
                imagesavealpha($outputImage, true);

                $transparent = imagecolorallocatealpha(
                    $outputImage,
                    0,
                    0,
                    0,
                    127
                );

                imagefill($outputImage, 0, 0, $transparent);
            }

            if (!imagecopyresampled(
                $outputImage,
                $sourceImage,
                0,
                0,
                0,
                0,
                $newWidth,
                $newHeight,
                $width,
                $height
            )) {
                throw new RuntimeException("Image resizing failed.");
            }

            // Use WebP when the server supports it.
            if (function_exists("imagewebp")) {
                $format = "webp";
                $extension = "webp";
            } else {
                // Keep the original format locally.
                $format = str_replace("image/", "", $mimeType);

                $extension = [
                    "jpeg" => "jpg",
                    "png" => "png",
                    "webp" => "webp"
                ][$format];
            }

            $fileName = bin2hex(random_bytes(16)) . "." . $extension;

            $outputPath = rtrim($uploadDirectory, "/\\")
                . DIRECTORY_SEPARATOR
                . $fileName;

            $saved = false;

            if ($format === "webp") {
                $saved = imagewebp(
                    $outputImage,
                    $outputPath,
                    self::WEBP_QUALITY
                );
            } elseif ($format === "jpeg") {
                // JPEG does not support transparency.
                $whiteBackground = imagecreatetruecolor(
                    $newWidth,
                    $newHeight
                );

                if ($whiteBackground === false) {
                    throw new RuntimeException("Could not create JPEG background.");
                }

                try {
                    $white = imagecolorallocate(
                        $whiteBackground,
                        255,
                        255,
                        255
                    );

                    imagefill($whiteBackground, 0, 0, $white);

                    imagecopy(
                        $whiteBackground,
                        $outputImage,
                        0,
                        0,
                        0,
                        0,
                        $newWidth,
                        $newHeight
                    );

                    $saved = imagejpeg(
                        $whiteBackground,
                        $outputPath,
                        self::JPEG_QUALITY
                    );
                } finally {
                    imagedestroy($whiteBackground);
                }
            } elseif ($format === "png") {
                $saved = imagepng(
                    $outputImage,
                    $outputPath,
                    self::PNG_COMPRESSION
                );
            }

            if (!$saved || !is_file($outputPath)) {
                throw new RuntimeException("Could not save converted image.");
            }

            // Keep the original if conversion makes the image larger
            // and resizing was not necessary.
            $originalFileSize = filesize($sourcePath);
            $processedFileSize = filesize($outputPath);

            if (
                !$wasResized &&
                $originalFileSize !== false &&
                $processedFileSize !== false &&
                $processedFileSize > $originalFileSize
            ) {
                // Remove the larger converted file.
                if (!unlink($outputPath)) {
                    throw new RuntimeException("Could not remove larger converted image.");
                }

                $outputPath = null;

                // Preserve the original image format.
                $originalFormat = str_replace("image/", "", $mimeType);

                $originalExtension = [
                    "jpeg" => "jpg",
                    "png" => "png",
                    "webp" => "webp"
                ][$originalFormat];

                return $this->copyUnprocessedImage(
                    $sourcePath,
                    $uploadDirectory,
                    $originalExtension,
                    $originalFormat
                );
            }

            return [
                "file" => $fileName,
                "format" => $format,
                "file_size" => (int) filesize($outputPath)
            ];
        } catch (Throwable $e) {
            if ($outputPath !== null && is_file($outputPath)) {
                unlink($outputPath);
            }

            throw $e;
        } finally {
            if ($outputImage !== null && $outputImage !== false) {
                imagedestroy($outputImage);
            }

            if ($sourceImage !== null && $sourceImage !== false) {
                imagedestroy($sourceImage);
            }
        }
    }


    private function applyJpegExifOrientation($image, string $sourcePath)
    {
        if (!function_exists("exif_read_data")) {
            return $image;
        }

        $metadata = @exif_read_data($sourcePath, "IFD0", true);
        $orientation = $metadata["IFD0"]["Orientation"] ?? null;

        if (!is_numeric($orientation)) {
            return $image;
        }

        $angle = null;
        $flipMode = null;

        switch ((int) $orientation) {
            case 2:
                $flipMode = IMG_FLIP_HORIZONTAL;
                break;
            case 3:
                $angle = 180;
                break;
            case 4:
                $flipMode = IMG_FLIP_VERTICAL;
                break;
            case 5:
                $angle = -90;
                $flipMode = IMG_FLIP_HORIZONTAL;
                break;
            case 6:
                $angle = -90;
                break;
            case 7:
                $angle = -90;
                $flipMode = IMG_FLIP_VERTICAL;
                break;
            case 8:
                $angle = 90;
                break;
            default:
                return $image;
        }

        if ($angle === null) {
            if (!function_exists("imageflip") || !imageflip($image, $flipMode)) {
                throw new RuntimeException("Could not correct image orientation.");
            }

            return $image;
        }

        if (!function_exists("imagerotate")) {
            throw new RuntimeException("Could not correct image orientation.");
        }

        $rotatedImage = imagerotate($image, $angle, 0);

        if ($rotatedImage === false) {
            throw new RuntimeException("Could not rotate image.");
        }

        if (
            $flipMode !== null &&
            (!function_exists("imageflip") || !imageflip($rotatedImage, $flipMode))
        ) {
            imagedestroy($rotatedImage);
            throw new RuntimeException("Could not correct image orientation.");
        }

        imagedestroy($image);

        return $rotatedImage;
    }

    /**
     * Check whether the estimated processing memory fits PHP's memory limit.
     */
    private function checkMemoryLimit(
        int $width,
        int $height
    ): void {
        $memoryLimit = ini_get("memory_limit");

        // A limit of -1 means PHP has no configured memory limit.
        if ($memoryLimit === false || trim($memoryLimit) === "-1") {
            return;
        }

        $memoryLimit = trim($memoryLimit);

        if (!preg_match('/^(\d+(?:\.\d+)?)\s*([KMG])?$/i', $memoryLimit, $matches)) {
            throw new RuntimeException("Could not determine PHP memory limit.");
        }

        $limitBytes = (float) $matches[1];

        $unit = strtoupper($matches[2] ?? "");

        switch ($unit) {
            case "G":
                $limitBytes *= 1024;
                // Fall through.

            case "M":
                $limitBytes *= 1024;
                // Fall through.

            case "K":
                $limitBytes *= 1024;
                break;
        }

        $estimatedMemory = ($width * $height * self::MEMORY_PER_PIXEL)
            + self::MEMORY_RESERVE;

        $currentMemory = memory_get_usage(true);

        if (($currentMemory + $estimatedMemory) > $limitBytes) {
            throw new RuntimeException(
                "Image requires too much memory to process. "
                    . "Try uploading a smaller image."
            );
        }
    }
    /**
     * Copy an image without processing it.
     * Used for animated GIFs and unsupported WebP decoding.
     */
    private function copyUnprocessedImage(
        string $sourcePath,
        string $uploadDirectory,
        string $extension,
        ?string $format = null
    ): array {
        $fileName = bin2hex(random_bytes(16)) . "." . $extension;

        $outputPath = rtrim($uploadDirectory, "/\\")
            . DIRECTORY_SEPARATOR
            . $fileName;

        if (!copy($sourcePath, $outputPath)) {
            throw new RuntimeException("Could not copy image.");
        }

        return [
            "file" => $fileName,
            "format" => $format ?? $extension,
            "file_size" => (int) filesize($outputPath)
        ];
    }
}
