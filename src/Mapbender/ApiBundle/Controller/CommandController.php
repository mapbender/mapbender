<?php

namespace Mapbender\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;


class CommandController extends AbstractController
{
    #[Route('/api/wms/show', name: 'api_wms_show', methods: ['GET'])]
    public function prepareWmsShowCommand(Request $request, KernelInterface $kernel): JsonResponse
    {
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
    public function prepareWmsReloadCommand(Request $request, KernelInterface $kernel): JsonResponse
    {
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
    public function prepareWmsAddCommand(Request $request, KernelInterface $kernel): JsonResponse
    {
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

        $inputArgs = [
            'command' => 'mapbender:wms:assign',
            'application' => $application,
            'source' => $source,
            'layerset' => $layerset,
            '-v' => true, // Ignore PHP deprecated messages to reduce the output
        ];

        return $this->executeCommand($inputArgs, $kernel);
    }

    #[Route('/api/application/clone', name: 'api_application_clone', methods: ['GET'])]
    public function prepareApplicationCloneCommand(Request $request, KernelInterface $kernel): JsonResponse
    {
        $slug = $request->get('slug');

        if (!$slug) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Parameter "slug" is required',
            ], 400);
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
            $application->run($input, $output);
            $commandOutput = $output->fetch();

            if (isset($inputArgs['--json']) && $inputArgs['--json']) {
                $commandOutput = json_decode($commandOutput, true);
            }

            return new JsonResponse([
                'success' => true,
                'message' => $commandOutput,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
