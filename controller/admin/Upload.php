<?php declare(strict_types=1);

namespace modules\subjects\controller\admin;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Upload extends Controller
{
    private $uploadDir = __DIR__ . '/../../upload';

    public function __invoke(Request $request, Response $response, $args)
    {
        $directory = $this->uploadDir;
        $this->makeUploadDir();
        $uploadedFiles = $request->getUploadedFiles();

        $uploadedFile = $uploadedFiles['subjects'];
        if (!$uploadedFile) {
            return $this->redirectWithMessage($response, 'subjects-admin', "error", [
                $this->container->lang->g('error-notsent', 'admin-upload')
            ]);
        }

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            return $this->redirectWithMessage($response, 'subjects-admin', "error", [
                $this->container->lang->g('error-failed', 'admin-upload')
            ]);
        }
        
        $filename = $this->moveUploadedFile($directory, $uploadedFile);
        $parsed = $this->validateFile($directory . "/" . $filename);
        \unlink($directory . "/" . $filename);

        if (!\is_array($parsed)) {
            return $this->redirectWithMessage($response, 'subjects-admin', "error", [
                $this->container->lang->g('error-syntax-title', 'admin-upload'),
                $this->container->lang->g('error-syntax-message', 'admin-upload', ['line' => $parsed])
            ]);
        }
        
        return $parsed;
    }

    private function makeUploadDir()
    {
        if (!\is_dir($this->uploadDir)) {
            \mkdir($this->uploadDir);
        }
    }

    private function validateFile($filename)
    {
        $f = \fopen($filename, "r");
        if (!$f) {
            return 0;
        }
        $list = [];
        $line = 0;
        while (($data = \fgetcsv($f, 0, ";")) !== false) {
            if (\count($data) != 3) {
                return $line + 1;
            }
            if (!\is_numeric($data[0]) ||
                !\is_string($data[1]) ||
                !\is_string($data[2])
            ) {
                return $line + 1;
            }
            $list[$line] = filter_var_array($data, \FILTER_SANITIZE_STRING);
            $line++;
        }
        return $list;
    }

    private function moveUploadedFile($directory, \Slim\Http\UploadedFile $uploadedFile)
    {
        $extension = \pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = \bin2hex(\random_bytes(8));
        $filename = \sprintf('%s.%0.8s', $basename, $extension);
    
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
    
        return $filename;
    }
}
