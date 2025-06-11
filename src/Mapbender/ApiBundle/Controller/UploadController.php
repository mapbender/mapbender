<?php

namespace Mapbender\ApiBundle\Controller;

use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use OpenApi\Attributes as OA;

class UploadController extends AbstractController
{

    public function __construct(private string $uploadDir)
    {
        // Ensure the upload directory always ends with a directory separator
        $this->uploadDir = rtrim($this->uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    #[Route('/api/upload/zip', name: 'api_upload_zip', methods: ['POST'])]
    #[OA\Tag(name: 'file-upload')]
    #[OA\Post(
        description: "Uploads a ZIP file to the server and extracts its contents into the upload directory,
                        which is configured using the 'api_upload_dir' parameter.
                        \nUsers must have the 'access api' and 'upload files' permissions",
        summary: 'Upload a ZIP file',
        requestBody: new OA\RequestBody(
            description: 'The ZIP file to upload',
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'file',
                            type: 'string',
                            format: 'binary'
                        )
                    ],
                    type: 'object'
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'ZIP file uploaded and extracted successfully',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'success', type: 'boolean'),
                            new OA\Property(property: 'message', type: 'string'),
                            new OA\Property(property: 'upload_dir', description: 'The upload directory path as configured in the mapbender settings where the uploaded contents were extracted into', type: 'string'),
                        ],
                        type: 'object'
                    )
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid request, e.g., no file uploaded or wrong file type',
            ),
            new OA\Response(
                response: 500,
                description: 'Server error, e.g., failed to move or extract the file',
            )
        ]
    )]
    public function uploadZipAction(Request $request): JsonResponse
    {
        $missingPermissions = [];
        if (!$this->isGranted(ResourceDomainInstallation::ACTION_ACCESS_API)) {
            $missingPermissions[] = 'Access API';
        }
        if (!$this->isGranted(ResourceDomainInstallation::ACTION_UPLOAD_FILES)) {
            $missingPermissions[] = 'Upload Files';
        }

        if (!empty($missingPermissions)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Access Denied: Missing permissions - ' . implode(', ', $missingPermissions),
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $file = $request->files->get('file');

        if (!$file) {
            return new JsonResponse([
                'success' => false,
                'error' => 'No file uploaded',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Validate the MIME type
        $validMimeTypes = ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'];
        if (!in_array($file->getMimeType(), $validMimeTypes, true)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Only ZIP files are allowed',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }

        $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $zipFilePath = $this->uploadDir . $originalFileName . '.zip';

        try {
            $file->move($this->uploadDir, $originalFileName . '.zip');
        } catch (FileException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to upload file: ' . $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Extract the ZIP file
        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath) === true) {
            $zip->extractTo($this->uploadDir);
            $zip->close();

            unlink($zipFilePath);

            return new JsonResponse([
                'success' => true,
                'message' => 'ZIP file uploaded and extracted successfully',
                'upload_dir' => $this->uploadDir,
            ], JsonResponse::HTTP_OK);
        } else {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to extract ZIP file',
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
