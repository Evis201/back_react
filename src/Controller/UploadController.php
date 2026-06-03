<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/upload', name: 'upload_')]
class UploadController extends AbstractController
{
    #[Route('/cv', name: 'cv', methods: ['POST'])]
    public function uploadCv(Request $request): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file) {
            return new JsonResponse(['success' => false, 'error' => 'No file provided.'], 400);
        }

        if (!$file->isValid()) {
            $errorCode = $file->getError();
            $msg = ($errorCode === \UPLOAD_ERR_INI_SIZE || $errorCode === \UPLOAD_ERR_FORM_SIZE)
                ? 'File exceeds server upload limit (max 2 MB by default). Ask admin to raise upload_max_filesize.'
                : 'File upload failed (error ' . $errorCode . ').';
            return new JsonResponse(['success' => false, 'error' => $msg], 422);
        }

        $allowedMimes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!in_array($file->getMimeType(), $allowedMimes, true)) {
            return new JsonResponse(['success' => false, 'error' => 'Only PDF and Word documents are allowed.'], 422);
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return new JsonResponse(['success' => false, 'error' => 'File must be under 5 MB.'], 422);
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/cv';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = $file->guessExtension() ?? 'pdf';
        $filename = Uuid::v4()->toRfc4122() . '.' . $ext;

        $file->move($uploadDir, $filename);

        $baseUrl = $request->getSchemeAndHttpHost();

        return new JsonResponse([
            'success' => true,
            'url' => $baseUrl . '/uploads/cv/' . $filename,
        ], 201);
    }
}
