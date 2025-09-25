<?php

namespace App\Service;

use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;
use Spatie\ImageOptimizer\Optimizers\Pngquant;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\ImageOptimizer\Optimizers\Svgo;
use Spatie\ImageOptimizer\Optimizers\Gifsicle;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageOptimizerService
{
    private OptimizerChain $optimizerChain;

    public function __construct()
    {
        $this->optimizerChain = (new OptimizerChain)
            ->addOptimizer(new Jpegoptim([
                '-m85', // Qualité JPEG à 85%
                '--strip-all', // Supprimer les métadonnées
                '--all-progressive', // Images progressives
            ]))
            ->addOptimizer(new Pngquant([
                '--quality=65-80', // Qualité PNG entre 65% et 80%
                '--force',
            ]))
            ->addOptimizer(new Optipng([
                '-i0', // Pas d'interlacing
                '-o2', // Niveau d'optimisation 2
                '-quiet',
            ]))
            ->addOptimizer(new Svgo([
                '--disable=cleanupIDs', // Garder les IDs pour éviter les conflits
            ]))
            ->addOptimizer(new Gifsicle([
                '-b', // Optimiser en place
                '-O3', // Niveau d'optimisation maximum
            ]));
    }

    /**
     * Optimise une image uploadée
     */
    public function optimizeImage(UploadedFile $imageFile, string $targetPath): bool
    {
        try {
            // Vérifier que le fichier est une image
            if (!$this->isImageFile($imageFile)) {
                return false;
            }

            // Optimiser l'image
            $this->optimizerChain->optimize($targetPath);
            
            return true;
        } catch (\Exception $e) {
            // En cas d'erreur, on continue sans optimisation
            // Log l'erreur si nécessaire
            return false;
        }
    }

    /**
     * Vérifie si le fichier est une image
     */
    private function isImageFile(UploadedFile $file): bool
    {
        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml'
        ];

        return in_array($file->getMimeType(), $allowedMimeTypes);
    }

    /**
     * Redimensionne et optimise une image
     */
    public function resizeAndOptimize(UploadedFile $imageFile, string $targetPath, int $maxWidth = 1200, int $maxHeight = 800): bool
    {
        try {
            if (!$this->isImageFile($imageFile)) {
                return false;
            }

            // Obtenir les dimensions de l'image
            $imageInfo = getimagesize($imageFile->getPathname());
            if (!$imageInfo) {
                return false;
            }

            [$originalWidth, $originalHeight] = $imageInfo;

            // Calculer les nouvelles dimensions en gardant le ratio
            $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
            
            if ($ratio < 1) {
                $newWidth = (int) ($originalWidth * $ratio);
                $newHeight = (int) ($originalHeight * $ratio);
                
                // Redimensionner l'image
                $this->resizeImage($imageFile->getPathname(), $targetPath, $newWidth, $newHeight);
            } else {
                // Copier le fichier tel quel s'il est déjà assez petit
                copy($imageFile->getPathname(), $targetPath);
            }

            // Optimiser l'image redimensionnée
            $this->optimizerChain->optimize($targetPath);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Redimensionne une image
     */
    private function resizeImage(string $sourcePath, string $targetPath, int $newWidth, int $newHeight): void
    {
        $imageInfo = getimagesize($sourcePath);
        $mimeType = $imageInfo['mime'];

        // Créer l'image source selon le type
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($sourcePath);
                break;
            default:
                throw new \InvalidArgumentException('Type d\'image non supporté');
        }

        // Créer une nouvelle image avec les dimensions souhaitées
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Préserver la transparence pour PNG et GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefill($newImage, 0, 0, $transparent);
        }

        // Redimensionner
        imagecopyresampled(
            $newImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $imageInfo[0], $imageInfo[1]
        );

        // Sauvegarder selon le type
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($newImage, $targetPath, 85);
                break;
            case 'image/png':
                imagepng($newImage, $targetPath, 8);
                break;
            case 'image/gif':
                imagegif($newImage, $targetPath);
                break;
            case 'image/webp':
                imagewebp($newImage, $targetPath, 85);
                break;
        }

        // Libérer la mémoire
        imagedestroy($sourceImage);
        imagedestroy($newImage);
    }
}