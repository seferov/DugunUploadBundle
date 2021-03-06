<?php

namespace Dugun\UploadBundle\Service;

use Dugun\UploadBundle\Contracts\DugunUploadInterface;
use Dugun\UploadBundle\Service\Upload\AWSUploadService;
use Dugun\UploadBundle\Service\Upload\DugunImageMicroserviceUploadService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DugunUploadService
{
    /**
     * @var array
     */
    private $parameters;

    /**
     * @var DugunUploadInterface
     */
    private $uploaderService;

    public function __construct($parameters)
    {
        $this->parameters = $parameters;
        $this->setUploaderService($this->parameters['upload_service_name']);
    }

    public function setUploaderService($serviceName)
    {
        if ($serviceName == 'aws') {
            $this->uploaderService = new AWSUploadService(
                $this->parameters['credentials'][$serviceName]
            );
        } elseif ($serviceName == 'dugun_image_microservice') {
            $this->uploaderService = new DugunImageMicroserviceUploadService(
                $this->parameters['credentials'][$serviceName]
            );
        }
    }

    public function download($filePath)
    {
        if (!$this->uploaderService) {
            return false; //throw
        }
        $tmp_dir = $this->parameters['temporary_path'].time().'_'.basename($filePath);

        return $this->uploaderService->download($filePath, $tmp_dir);
    }

    public function upload($file, $destinationFile, $delete = false, $overwrite = false)
    {
        if (!$this->uploaderService) {
            return false; //throw
        }
        if ($file instanceof UploadedFile) {
            $filePath = $file->getRealPath();
        } elseif ($file instanceof \Intervention\Image\Image) {
            $filePath = $file->dirname.'/'.$file->basename;
        } elseif (is_string($file)) {
            $filePath = $file;
        }
        if (isset($filePath)) {
            $result = $this->uploaderService->upload($filePath, $destinationFile, $overwrite);
            if ($result['success'] == true) {
                if ($delete) {
                    unlink($filePath);
                }
                $result['url'] = $this->uploaderService->getFileFullUrl($destinationFile);
            }

            return $result;
        }
    }

    public function doesObjectExist($destinationFile)
    {
        if (!$this->uploaderService) {
            return false; //throw
        }

        return $this->uploaderService->doesObjectExist($destinationFile);
    }

    /**
     * @param null|string $type
     *
     * @return array
     */
    public function getValidMimeTypes($type = null)
    {
        if ($type === 'image') {
            return [
                'image/jpeg',
                'image/png',
            ];
        } else {
            return [
                'image/jpeg',
                'image/png',
                'application/excel',
                'text/plain',
                'application/pdf',
                'application/msword',
            ];
        }
    }

    /**
     * @param $filename
     *
     * @return bool
     */
    protected function isFilenameValid($filename)
    {
        return strpos(pathinfo($filename, PATHINFO_DIRNAME), '..') === false;
    }
}
