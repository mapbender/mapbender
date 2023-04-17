<?php


namespace Mapbender\CoreBundle\Element;


use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use FOM\UserBundle\Entity\User;
use Mapbender\Component\Element\ElementHttpHandlerInterface;
use Mapbender\CoreBundle\Entity;
use Mapbender\CoreBundle\Entity\ViewManagerState;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig;

class ViewManagerHttpHandler implements ElementHttpHandlerInterface
{
    /** @var Twig\Environment */
    protected $templating;
    /** @var EntityManagerInterface */
    protected $em;
    /** @var TokenStorageInterface */
    protected $tokenStorage;
    protected CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(Twig\Environment $templating, EntityManagerInterface $em, TokenStorageInterface $tokenStorage, CsrfTokenManagerInterface  $csrfTokenManager)
    {
        $this->templating = $templating;
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * @param Entity\Element $element
     * @param Request $request
     * @return Response
     * @throws HttpException
     */
    public function handleRequest(Entity\Element $element, Request $request)
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
        }
    }

    /**
     * @param Entity\Element $element
     * @param Request $request
     * @return Response
     */
    protected function getListingResponse(Entity\Element $element, Request $request)
    {
        $config = $element->getConfiguration();
        $vars = array(
            'records' => $this->loadListing($element->getApplication(), $config),
            'showDate' => $config['showDate'],
            'dateFormat' => $this->getDateFormat($request),
            'grants' => $this->getGrantsVariables($config),
            'row_template' => $this->getRowTemplate(),
        );
        $content = $this->templating->render('MapbenderCoreBundle:Element:view_manager-listing.html.twig', $vars);
        return new Response($content);
    }

    /**
     * @param Entity\Application $application
     * @param array $config
     * @return ViewManagerState[]
     */
    protected function loadListing(Entity\Application $application, array $config)
    {
        $showPublic = !!$config['publicEntries'];
        $showPrivate = !!$config['privateEntries'] && !$this->isCurrentUserAnonymous();
        $criteria = Criteria::create()->where(Criteria::expr()->eq('applicationSlug', $application->getSlug()));

        if ($showPublic && !$showPrivate) {
            $criteria->andWhere(Criteria::expr()->isNull('userId'));
        } else {
            $privateExpression = new CompositeExpression(CompositeExpression::TYPE_AND, array(
                Criteria::expr()->eq('userId', $this->getUserId()),
            ));
            if ($showPrivate && !$showPublic) {
                $criteria->andWhere($privateExpression);
            } else {
                assert($showPrivate && $showPublic);
                $criteria->andWhere(new CompositeExpression(CompositeExpression::TYPE_OR, array(
                    Criteria::expr()->isNull('userId'),
                    Criteria::expr()->eq('userId', $this->getUserId()),
                )));
            }
        }

        $criteria->orderBy(array(
            'applicationSlug' => Criteria::ASC,
            'userId' => Criteria::ASC,
        ));
        return $this->getRepository()->matching($criteria);
    }

    protected function getSaveResponse(Entity\Element $element, Request $request)
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
        $vars = array(
            'record' => $record,
            'showDate' => $config['showDate'],
            'dateFormat' => $this->getDateFormat($request),
            'grants' => $this->getGrantsVariables($element->getConfiguration()),
        );
        $content = $this->templating->render($this->getRowTemplate(), $vars);
        return new Response($content);
    }

    protected function getDeleteResponse(Entity\Element $element, Request $request)
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
                if ($record->getUserId() !== $this->getUserId()) {
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
        $record->setViewParams($request->request->get('viewParams'));
        $record->setLayersetDiffs($request->request->get('layersetsDiff', array()));
        $record->setSourceDiffs($request->request->get('sourcesDiff', array()));
        $record->setMtime(new \DateTime());
    }

    /**
     * @param Request $request
     * @return string
     */
    protected function getDateFormat(Request $request)
    {
        // @todo: locale-dependent format
        return 'Y-m-d'; // . ' H:m:i';
    }

    /**
     * @return \Doctrine\Persistence\ObjectRepository
     */
    protected function getRepository()
    {
        /** @var EntityRepository */
        $repository = $this->em->getRepository('Mapbender\CoreBundle\Entity\ViewManagerState');
        return $repository;
    }

    protected function isCurrentUserAnonymous()
    {
        $token = $this->tokenStorage->getToken();
        return !$token || ($token instanceof AnonymousToken);
    }

    protected function getUserId()
    {
        $token = $this->tokenStorage->getToken();
        if ($token && !($token instanceof AnonymousToken)) {
            return $token->getUser()->getUsername();
        }
        return null;
    }

    protected function isAdmin()
    {
        $token = $this->tokenStorage->getToken();
        if ($token && !($token instanceof AnonymousToken)) {
            $user = $token->getUser();
            if (\is_object($user) && ($user instanceof User)) {
                return $user->isAdmin();
            }
        }
        return false;
    }

    public function getGrantsVariables($config)
    {
        $isAdmin = $this->isAdmin();
        $isAnon = !$isAdmin && $this->isCurrentUserAnonymous();
        $saveDefault = $isAnon ? $config['allowAnonymousSave'] : true;
        return array(
            'savePublic' => $config['publicEntries'] && ($isAdmin || ($saveDefault && \in_array($config['publicEntries'], array(
                ViewManager::ACCESS_READWRITE,
                ViewManager::ACCESS_READWRITEDELETE,
            )))),
            'savePrivate' => !$isAnon && $config['privateEntries'],
            'deletePublic' => $isAdmin || !$isAnon && ($config['publicEntries'] === ViewManager::ACCESS_READWRITEDELETE),
        );
    }

    protected function checkGrant(Entity\Element $element, $operation)
    {
        $grantsVariables = $this->getGrantsVariables($element->getConfiguration());
        switch ($operation) {
            default:
                return false;
            case 'deletePublic':
                return $grantsVariables['deletePublic'];
            case 'savePublic':
                return $grantsVariables['savePublic'];
            case 'savePrivate':
                return $grantsVariables['savePrivate'];
        }
    }

    protected function getRowTemplate()
    {
        return 'MapbenderCoreBundle:Element:view_manager-listing-row.html.twig';
    }
}
