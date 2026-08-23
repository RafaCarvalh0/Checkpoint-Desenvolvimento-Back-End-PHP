<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ProductImage;
use App\Repository\ProductImageRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ProductImageStorage
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public')] private readonly string $publicDir,
        private readonly ProductImageRepository $images,
    ) {
    }

    public function upload(UploadedFile $file): string
    {
        $relativeDirectory = 'uploads/products/'.date('Y/m');
        $directory = $this->publicDir.'/'.$relativeDirectory;
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Não foi possível criar o diretório de imagens.');
        }
        $extension = match ($file->getMimeType()) {
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
            default => throw new \InvalidArgumentException('Formato de imagem inválido.'),
        };
        $filename = bin2hex(random_bytes(16)).'.'.$extension;
        $file->move($directory, $filename);
        return '/'.$relativeDirectory.'/'.$filename;
    }

    public function generateThumbnail(ProductImage $image): ?string
    {
        if (!str_starts_with($image->getUrl(), '/uploads/products/')) {
            return null;
        }
        $source = $this->publicDir.$image->getUrl();
        if (!is_file($source) || !function_exists('imagecreatefromstring')) {
            return null;
        }
        $content = file_get_contents($source);
        $original = $content === false ? false : imagecreatefromstring($content);
        if ($original === false) {
            return null;
        }
        $width = imagesx($original);
        $height = imagesy($original);
        $scale = min(320 / $width, 320 / $height, 1);
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        imagecopyresampled($thumbnail, $original, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        $pathInfo = pathinfo($source);
        if (function_exists('imagewebp')) {
            $thumbnailPath = $pathInfo['dirname'].'/'.$pathInfo['filename'].'-thumb.webp';
            $saved = imagewebp($thumbnail, $thumbnailPath, 82);
        } elseif (function_exists('imagepng')) {
            $thumbnailPath = $pathInfo['dirname'].'/'.$pathInfo['filename'].'-thumb.png';
            $saved = imagepng($thumbnail, $thumbnailPath, 6);
        } elseif (function_exists('imagejpeg')) {
            $thumbnailPath = $pathInfo['dirname'].'/'.$pathInfo['filename'].'-thumb.jpg';
            $saved = imagejpeg($thumbnail, $thumbnailPath, 82);
        } else {
            $saved = false;
            $thumbnailPath = '';
        }
        imagedestroy($thumbnail);
        imagedestroy($original);
        return $saved ? str_replace($this->publicDir, '', $thumbnailPath) : null;
    }

    public function cleanupOrphans(): int
    {
        $root = $this->publicDir.'/uploads/products';
        if (!is_dir($root)) {
            return 0;
        }
        $used = $this->images->usedUrls();
        $removed = 0;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile() || str_starts_with($file->getFilename(), '.')) {
                continue;
            }
            $url = str_replace($this->publicDir, '', $file->getPathname());
            if (!isset($used[$url]) && unlink($file->getPathname())) {
                ++$removed;
            }
        }
        return $removed;
    }
}
