<?php


namespace Mapbender\CoreBundle\Element;


use Twig\Environment;
use Mapbender\CoreBundle\Entity\Element;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use FOM\UserBundle\Entity\User;
use FOM\UserBundle\Security\Permission\ResourceDomainSourceInstance;
use Mapbender\Component\Element\ElementHttpHandlerInterface;
use Mapbender\CoreBundle\Entity;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\ViewManagerState;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ViewManagerHttpHandler implements ElementHttpHandlerInterface
{
    public function __construct(
        protected Environment          $templating,
        protected EntityManagerInterface    $em,
        protected TokenStorageInterface     $tokenStorage,
        protected CsrfTokenManagerInterface $csrfTokenManager,
        protected Security                  $security,
    )
    {
    }

    /**
     * @param Entity\Element $element
     * @param Request $request
     * @return Response
     * @throws HttpException
     */
    public function handleRequest(Element $element, Request $request)
    {
        switch ($request->attributes->get('action')) {
            default:
                throw new NotFoundHttpException();
            case 'listing':
                return $this->getListingResponse($element, $request);
            case 'save':
                return $this->getSaveResponse($element, $request);
            case 'delete':
                return $this->getDeleteResponse($element, $request);
            case 'csrf':
                $generatedToken = $this->csrfTokenManager->getToken('view_manager');
                return new Response($generatedToken->getValue());
            case 'getView':
                $viewId = $request->query->get('viewId');
                $config = $element->getConfiguration();
                $records = $this->loadListing($element->getApplication(), $config, $viewId);
                $response = [];
                if (!empty($records) && count($records) === 1) {
                    $record = $records[0];
                    $idMap = $this->getInstanceIdToSourceInstanceOrAssigmnentMap($element->getApplication());
                    $response = [
                        'viewParams' => $record->getViewParams(),
                        'layersets' => $this->filterPermittedLayersets($record->getLayersetStates(), $idMap),
                        'sources' => $this->filterPermittedSourceStates($record->getSourceStates(), $idMap),
                    ];
                }
                return new JsonResponse($response);
        }
    }

    /**
     * @param Entity\Element $element
     * @param Request $request
     * @return Response
     */
    protected function getListingResponse(Element $element, Request $request): Response
    {
        $config = $element->getConfiguration();
        $vars = [
            'records' => $this->loadListing($element->getApplication(), $config),
            'showDate' => $config['showDate'],
            'grants' => $this->getGrantsVariables($config),
            'row_template' => $this->getRowTemplate(),
        ];
        $content = $this->templating->render('@MapbenderCore/Element/view_manager-listing.html.twig', $vars);
        return new Response($content);
    }

    /**
     * @param Entity\Application $application
     * @param array $config
     * @param int|null $viewId
     * @return ViewManagerState[]
     */
    protected function loadListing(Application $application, array $config, $viewId = null)
    {
        $showPublic = !!$config['publicEntries'];
        $showPrivate = !!$config['privateEntries'] && !$this->isCurrentUserAnonymous();
        $criteria = Criteria::create()->where(Criteria::expr()->eq('applicationSlug', $application->getSlug()));
        if (!empty($viewId)) {
            $criteria->andWhere(Criteria::expr()->eq('id', $viewId));
        }
        if ($showPublic && !$showPrivate) {
            $criteria->andWhere(Criteria::expr()->isNull('userId'));
        } else {
            $privateExpression = new CompositeExpression(CompositeExpression::TYPE_AND, [
                Criteria::expr()->eq('userId', $this->getUserId()),
            ]);
            if ($showPrivate && !$showPublic) {
                $criteria->andWhere($privateExpression);
            } else {
                assert($showPrivate && $showPublic);
                $criteria->andWhere(new CompositeExpression(CompositeExpression::TYPE_OR, [
                    Criteria::expr()->isNull('userId'),
                    Criteria::expr()->eq('userId', $this->getUserId()),
                ]));
            }
        }

        $criteria->orderBy([
            'title' => Criteria::ASC,
        ]);
        return $this->getRepository()->matching($criteria);
    }

    protected function getSaveResponse(Element $element, Request $request): Response
    {
        $token = new CsrfToken('view_manager', $request->request->get('token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new BadRequestHttpException();
        }

        if ($id = $request->query->get('id')) {
            // Update existing record
            /** @var ViewManagerState|null $record */
            $record = $this->getRepository()->find($id);
            if (!$record) {
                throw new NotFoundHttpException();
            }
            if ($newTitle = $request->request->get('title')) {
                $record->setTitle($newTitle);
            }
        } else {
            // New record
            $record = new ViewManagerState();
            $record->setApplicationSlug($element->getApplication()->getSlug());
            $record->setTitle($request->request->get('title'));
            if ($request->request->get('savePublic')) {
                $record->setUserId(null);
            } else {
                $record->setUserId($this->getUserId());
            }
        }
        $this->updateRecord($record, $request);

        $this->em->persist($record);
        $this->em->flush();

        $config = $element->getConfiguration();
        $vars = [
            'record' => $record,
            'showDate' => $config['showDate'],
            'grants' => $this->getGrantsVariables($element->getConfiguration()),
        ];
        $content = $this->templating->render($this->getRowTemplate(), $vars);
        return new Response($content);
    }

    protected function getDeleteResponse(Element $element, Request $request): Response
    {
        $token = new CsrfToken('view_manager', $request->request->get('token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new BadRequestHttpException();
        }

        $id = $request->query->get('id');
        if (!$id) {
            throw new BadRequestHttpException("Missing id");
        }
        /** @var ViewManagerState|null $record */
        $record = $this->getRepository()->find($id);
        if ($record) {
            if (!$this->isAdmin()) {
                if (!$record->getUserId() && !$this->checkGrant($element, 'deletePublic')) {
                    throw new AccessDeniedHttpException();
                }
            }

            $this->em->remove($record);
            $this->em->flush();
        }
        return new Response('', Response::HTTP_NO_CONTENT);
    }

    protected function updateRecord(ViewManagerState $record, Request $request)
    {
        // NOTE: Empty arrays do not survive jQuery Ajax post, will be stripped completely from incoming data
        $allValues = $request->request->all();
        $record->setViewParams($allValues['viewParams']);
        $record->setLayersetStates(json_decode($allValues['layersetStates'], true) ?? []);
        $record->setSourceStates(json_decode($allValues['sourcesStates'], true) ?? []);
        $record->setMtime(new \DateTime());
    }

    protected function getRepository(): ObjectRepository
    {
        /** @var EntityRepository */
        $repository = $this->em->getRepository(ViewManagerState::class);
        return $repository;
    }

    protected function isCurrentUserAnonymous(): bool
    {
        $token = $this->tokenStorage->getToken();
        return !$token || ($token instanceof NullToken);
    }

    protected function getUserId(): ?string
    {
        $token = $this->tokenStorage->getToken();
        if ($token && !($token instanceof NullToken)) {
            return $token->getUser()->getUserIdentifier();
        }
        return null;
    }

    protected function isAdmin()
    {
        $token = $this->tokenStorage->getToken();
        if ($token && !($token instanceof NullToken)) {
            $user = $token->getUser();
            if (\is_object($user) && ($user instanceof User)) {
                return $user->isAdmin();
            }
        }
        return false;
    }

    public function getGrantsVariables(array $config): array
    {
        $isAdmin = $this->isAdmin();
        $isAnon = !$isAdmin && $this->isCurrentUserAnonymous();
        $saveDefault = $isAnon ? $config['allowAnonymousSave'] : true;
        return [
            'savePublic' => $config['publicEntries'] && ($isAdmin || ($saveDefault && \in_array($config['publicEntries'], [
                            ViewManager::ACCESS_READWRITE,
                            ViewManager::ACCESS_READWRITEDELETE,
                        ]))),
            'savePrivate' => !$isAnon && $config['privateEntries'],
            'deletePublic' => $isAdmin || !$isAnon && ($config['publicEntries'] === ViewManager::ACCESS_READWRITEDELETE),
        ];
    }

    protected function checkGrant(Element $element, $operation)
    {
        $grantsVariables = $this->getGrantsVariables($element->getConfiguration());
        return match ($operation) {
            'deletePublic' => $grantsVariables['deletePublic'],
            'savePublic' => $grantsVariables['savePublic'],
            'savePrivate' => $grantsVariables['savePrivate'],
            default => false,
        };
    }

    protected function getRowTemplate(): string
    {
        return '@MapbenderCore/Element/view_manager-listing-row.html.twig';
    }

    /**
     * @return array<string|int, SourceInstance|Entity\ReusableSourceInstanceAssignment>
     */
    protected function getInstanceIdToSourceInstanceOrAssigmnentMap(Application $application): array
    {
        $idMap = [];
        foreach ($application->getSourceInstances(false) as $instance) {
            $idMap[$instance->getId()] = $instance;
        }
        // for shared instances, the actual source instance's id is saved in the viewManagerState, but we need the
        // assignment to check for access permissions
        foreach ($application->getSharedInstanceAssignments() as $assignment) {
            $idMap[$assignment->getInstance()->getId()] = $assignment;
        }
        return $idMap;
    }

    protected function filterPermittedLayersets(array $layersets, array $idMap): array
    {
        for ($layersetIndex = 0; $layersetIndex < count($layersets); $layersetIndex++) {
            $layersets[$layersetIndex]['children'] = array_values(array_filter($layersets[$layersetIndex]['children'], function (array $instanceAsArray) use ($idMap): bool {
                $instanceOrAssignment = $idMap[$instanceAsArray['id']] ?? null;
                return $instanceOrAssignment !== null && $this->security->isGranted(ResourceDomainSourceInstance::ACTION_VIEW, $instanceOrAssignment);
            }));
        }

        // remove layersets that are empty after filtering out those without access
        $nonEmptyLayersets = array_filter($layersets, fn(array $layerset): bool => count($layerset['children']) > 0);
        return array_values($nonEmptyLayersets);
    }

    protected function filterPermittedSourceStates(array $sources, array $idMap): array
    {
        return array_values(array_filter($sources, function (array $instanceAsArray) use ($idMap): bool {
            $instanceOrAssignment = $idMap[$instanceAsArray['id']] ?? null;
            return $instanceOrAssignment !== null && $this->security->isGranted(ResourceDomainSourceInstance::ACTION_VIEW, $instanceOrAssignment);
        }));
    }

}
