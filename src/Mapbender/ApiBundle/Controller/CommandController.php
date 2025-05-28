<?php

namespace Mapbender\ApiBundle\Controller;

use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
use Mapbender\CoreBundle\Component\Application\ApplicationResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use OpenApi\Attributes as OA;


class CommandController extends AbstractController
{
    public function __construct(protected ApplicationResolver $applicationResolver)
    {
    }

    #[Route('/api/wms/show', name: 'api_wms_show', methods: ['GET'])]
    #[OA\Tag(name: 'wms')]
    #[OA\Get(
        path: '/api/wms/show',
        description: "Executes the console command mapbender:wms:show  \nUsers must have the 'access api' and 'view sources' permissions",
        summary: 'Displays layer information of a persisted WMS source',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Id or url of the source. If omitted, all sources are shown',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'mixed', example: 2)
            ),
            new OA\Parameter(
                name: 'json',
                description: 'if set, output is formatted as json',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', example: true)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success message with WMS data',
            )
        ]
    )]
    public function prepareWmsShowCommand(Request $request, KernelInterface $kernel): JsonResponse
    {
        $missingPermissions = [];
        if (!$this->isGranted(ResourceDomainInstallation::ACTION_ACCESS_API)) {
            $missingPermissions[] = 'Access API';
        }
        if (!$this->isGranted(ResourceDomainInstallation::ACTION_VIEW_SOURCES)) {
            $missingPermissions[] = 'View Sources';
        }

        if (!empty($missingPermissions)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Access Denied: Missing permissions - ' . implode(', ', $missingPermissions),
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $outputAsJson = filter_var($request->get('json', true), FILTER_VALIDATE_BOOLEAN);

        $inputArgs = [
            'command' => 'mapbender:wms:show',
            '--json' => $outputAsJson,
            '-v' => true, // Ignore PHP deprecated messages to reduce the output
        ];

        $id = $request->get('id', null);
        if ($id !== null) {
            $inputArgs['id'] = $id;
        }

        return $this->executeCommand($inputArgs, $kernel);
    }

    #[Route('/api/wms/reload', name: 'api_wms_reload', methods: ['GET'])]
    #[OA\Tag(name: 'wms')]
    #[OA\Get(
        path: '/api/wms/reload',
        description: "Executes the console command mapbender:wms:reload:url  \nUsers must have the 'access api' and 'refresh sources' permissions",
        summary: 'Reloads a WMS source from given url',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Id of the source',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 2)
            ),
            new OA\Parameter(
                name: 'serviceUrl',
                description: 'URL to WMS',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', example: "https://osm-demo.wheregroup.com/service")
            ),
            new OA\Parameter(
                name: 'deactivateNewLayers',
                description: 'If set, newly added layers will be deactivated in existing instances. Deactivated layers are not visible in the frontend.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', example: true)
            ),
            new OA\Parameter(
                name: 'deselectNewLayers',
                description: ' If set, newly added layers will be deselected in existing instances.
                                Deselected layers are not visible on the map by default, but appear in the layer tree and can be selected by users.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', example: true)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success message',
            )
        ]
    )]
    public function prepareWmsReloadCommand(Request $request, KernelInterface $kernel): JsonResponse
    {
        $missingPermissions = [];
        if (!$this->isGranted(ResourceDomainInstallation::ACTION_ACCESS_API)) {
            $missingPermissions[] = 'Access API';
        }
        if (!$this->isGranted(ResourceDomainInstallation::ACTION_REFRESH_SOURCES)) {
            $missingPermissions[] = 'Refresh Sources';
        }

        if (!empty($missingPermissions)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Access Denied: Missing permissions - ' . implode(', ', $missingPermissions),
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $id = $request->get('id');
        $serviceUrl = $request->get('serviceUrl');
        $deactivateNewLayers = filter_var($request->get('deactivateNewLayers', false), FILTER_VALIDATE_BOOLEAN);
        $deselectNewLayers = filter_var($request->get('deselectNewLayers', false), FILTER_VALIDATE_BOOLEAN);

        if (!$id || !$serviceUrl) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Both "id" and "serviceUrl" are required',
            ], 400);
        }

        $inputArgs = [
            'command' => 'mapbender:wms:reload:url',
            'id' => $id,
            'serviceUrl' => $serviceUrl,
            '-v' => true, // Ignore PHP deprecated messages to reduce the output
        ];

        if ($deactivateNewLayers) {
            $inputArgs['--deactivate-new-layers'] = true;
        }

        if ($deselectNewLayers) {
            $inputArgs['--deselect-new-layers'] = true;
        }

        return $this->executeCommand($inputArgs, $kernel);
    }

    #[Route('/api/wms/add', name: 'api_wms_add', methods: ['GET'])]
    #[OA\Tag(name: 'wms')]
    #[OA\Get(
        path: '/api/wms/add',
        description: "Executes the console command mapbender:wms:add  \nUsers must have the 'access api' and 'create sources' permissions",
        summary: 'Adds a new WMS source',
        parameters: [
            new OA\Parameter(
                name: 'serviceUrl',
                description: 'URL to WMS',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', example: "https://osm-demo.wheregroup.com/service")
            ),
            new OA\Parameter(
                name: 'deactivateNewLayers',
                description: 'If set, newly added layers will be deactivated in existing instances. Deactivated layers are not visible in the frontend.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', example: true)
            ),
            new OA\Parameter(
                name: 'deselectNewLayers',
                description: ' If set, newly added layers will be deselected in existing instances.
                                Deselected layers are not visible on the map by default, but appear in the layer tree and can be selected by users.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', example: true)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success message with layer description and the generated Id',
            )
        ]
    )]
    public function prepareWmsAddCommand(Request $request, KernelInterface $kernel): JsonResponse
    {
        $missingPermissions = [];
        if (!$this->isGranted(ResourceDomainInstallation::ACTION_ACCESS_API)) {
            $missingPermissions[] = 'Access API';
        }
        if (!$this->isGranted(ResourceDomainInstallation::ACTION_CREATE_SOURCES)) {
            $missingPermissions[] = 'Create Sources';
        }

        if (!empty($missingPermissions)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Access Denied: Missing permissions - ' . implode(', ', $missingPermissions),
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $serviceUrl = $request->get('serviceUrl');
        $deactivateNewLayers = filter_var($request->get('deactivateNewLayers', false), FILTER_VALIDATE_BOOLEAN);
        $deselectNewLayers = filter_var($request->get('deselectNewLayers', false), FILTER_VALIDATE_BOOLEAN);

        if (!$serviceUrl) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Parameter "serviceUrl" is required',
            ], 400);
        }

        $inputArgs = [
            'command' => 'mapbender:wms:add',
            'serviceUrl' => $serviceUrl,
            '-v' => true, // Ignore PHP deprecated messages to reduce the output
        ];

        if ($deactivateNewLayers) {
            $inputArgs['--deactivate-new-layers'] = true;
        }
        if ($deselectNewLayers) {
            $inputArgs['--deselect-new-layers'] = true;
        }

        return $this->executeCommand($inputArgs, $kernel);
    }

    #[Route('/api/wms/assign', name: 'api_wms_assign', methods: ['GET'])]
    #[OA\Tag(name: 'wms')]
    #[OA\Get(
        path: '/api/wms/assign',
        description: "Executes the console command mapbender:wms:assign  \nUsers must have the 'access api' and 'edit all applications' permissions",
        summary: 'Assigns a WMS source to an application',
        parameters: [
            new OA\Parameter(
                name: 'application',
                description: 'id or slug of the application',
                in: 'query',
                required: true,
                schema: new OA\Schema(example: "mapbender_user", oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'integer')])
            ),
            new OA\Parameter(
                name: 'source',
                description: 'id of the wms source',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer', example: "2")
            ),
            new OA\Parameter(
                name: 'layerset',
                description: 'id or name of the layerset. Defaults to "main" or the first layerset in the application',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'mixed', example: "main")
            ),
            new OA\Parameter(
                name: 'format',
                description: 'Sets the format for the GetMap-request, such as image/png',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'image/png')
            ),
            new OA\Parameter(
                name: 'infoformat',
                description: 'Sets the format for the FeatureInfo-request, such as text/html',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'text/html')
            ),
            new OA\Parameter(
                name: 'proxy',
                description: 'Decides if a proxy is used or not (one of true|false). Defaults to "false"',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', example: true)
            ),
            new OA\Parameter(
                name: 'tiled',
                description: 'Decides if the GetMap-requests are returned tiled or not (one of true|false). Defaults to "false"',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', example: true)
            ),
            new OA\Parameter(
                name: 'layerorder',
                description: 'Sets the layerorder to either standard or reverse (one of standard|reverse). Defaults to "standard"',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'standard')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success message',
            )
        ]
    )]
    public function prepareWmsAssignCommand(Request $request, KernelInterface $kernel): JsonResponse
    {
        $application = $request->get('application');
        $source = $request->get('source');
        $layerset = $request->get('layerset');

        if (!$application || !$source) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Both "application" and "source" are required',
            ], 400);
        }

        $missingPermissions = [];
        if (!$this->isGranted(ResourceDomainInstallation::ACTION_ACCESS_API)) {
            $missingPermissions[] = 'Access API';
        }

        $appEntity = $this->applicationResolver->getApplicationEntity($application);
        if (!$this->isGranted(ResourceDomainApplication::ACTION_EDIT, $appEntity)) {
            $missingPermissions[] = 'Edit Application';
        }

        if (!empty($missingPermissions)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Access Denied: Missing permissions - ' . implode(', ', $missingPermissions),
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $inputArgs = [
            'command' => 'mapbender:wms:assign',
            'application' => $application,
            'source' => $source,
            'layerset' => $layerset,
            '--format' => $request->get('format'),
            '--infoformat' => $request->get('infoformat'),
            '--proxy' => $request->get('proxy'),
            '--tiled' => $request->get('tiled'),
            '--layerorder' => $request->get('layerorder'),
            '-v' => true, // Ignore PHP deprecated messages to reduce the output
        ];

        return $this->executeCommand($inputArgs, $kernel);
    }

    #[Route('/api/application/clone', name: 'api_application_clone', methods: ['GET'])]
    #[OA\Tag(name: 'application')]
    #[OA\Get(
        path: '/api/application/clone',
        description: 'Executes the console command mapbender:application:clone',
        summary: 'Creates a copy of an application',
        parameters: [
            new OA\Parameter(
                name: 'slug',
                description: 'slug of the application',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', example: "mapbender_user")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success message with the new slug',
            )
        ]
    )]
    public function prepareApplicationCloneCommand(Request $request, KernelInterface $kernel): JsonResponse
    {
        $slug = $request->get('slug');

        if (!$slug) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Parameter "slug" is required',
            ], 400);
        }

        $missingPermissions = [];
        if (!$this->isGranted(ResourceDomainInstallation::ACTION_ACCESS_API)) {
            $missingPermissions[] = 'Access API';
        }
        if (!$this->isGranted(ResourceDomainInstallation::ACTION_CREATE_APPLICATIONS)) {
            $missingPermissions[] = 'Create Applications';
        }

        $appEntity = $this->applicationResolver->getApplicationEntity($slug);
        if (!$this->isGranted(ResourceDomainApplication::ACTION_VIEW, $appEntity)) {
            $missingPermissions[] = 'View Application';
        }

        if (!empty($missingPermissions)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Access Denied: Missing permissions - ' . implode(', ', $missingPermissions),
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $inputArgs = [
            'command' => 'mapbender:application:clone',
            'slug' => $slug,
            '-v' => true, // Ignore PHP deprecated messages to reduce the output
        ];

        return $this->executeCommand($inputArgs, $kernel);
    }

    function executeCommand($inputArgs, $kernel)
    {
        $application = new Application($kernel);
        // Deactivate AutoExit so that it does not terminate PHP execution
        $application->setAutoExit(false);

        $input = new ArrayInput($inputArgs);
        $output = new BufferedOutput();

        try {
            $exitCode = $application->run($input, $output);
            $commandOutput = $output->fetch();

            if (isset($inputArgs['--json']) && $inputArgs['--json']) {
                $commandOutput = json_decode($commandOutput, true);
            }

            if ($exitCode === 0) {
                return new JsonResponse([
                    'success' => true,
                    'message' => $commandOutput,
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'error' => $commandOutput,
                ], $exitCode);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
