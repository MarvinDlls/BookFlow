<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UploaderService
{

    public function __construct(readonly private ParameterBagInterface $parameterBag){}

    /**
     * Téléversement d'un fichier
     * @param UploadedFile $file
     * @return string
     */
    public function uploadFile(UploadedFile $file, string $actualImage): string
    {
        try {
            $this->deleteFile($actualImage);
            $fileName = uniqid() . '.' . $file->guessExtension();
            $file->move($this->parameterBag->get('upload_users_images'), $fileName);

            return $fileName;
        } catch (\Exception $e) {
            throw new \Exception('Une erreur est survenue lors de l\'envoi du fichier : ' . $e->getMessage());
        }
    }

    /**
     * Suppression d'un fichier
     * @param string $fileName
     */
    public function deleteFile(string $fileName): void
    {
        if ($fileName === 'default.png') {
            return;
        }
        try {
            $filePath = $this->parameterBag->get('upload_users_images') . '/' . $fileName;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        } catch (\Exception $e) {
            throw new \Exception('Une erreur est survenue lors de la suppression du fichier : ' . $e->getMessage());
        }
    }

}