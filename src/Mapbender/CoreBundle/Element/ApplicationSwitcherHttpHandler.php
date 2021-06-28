<?php


namespace Mapbender\CoreBundle\Element;


use Doctrine\ORM\EntityManagerInterface;
use Mapbender\Component\Element\ElementHttpHandlerInterface;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Entity;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ApplicationSwitcherHttpHandler implements ElementHttpHandlerInterface
{
    /** @var AuthorizationCheckerInterface */
    protected $authChecker;
    /** @var \Doctrine\Persistence\ObjectRepository */
    protected $dbRepository;
    /** @var ApplicationYAMLMapper */
    protected $yamlRepository;

    public function __construct(AuthorizationCheckerInterface $authChecker,
                                EntityManagerInterface $em,
                                ApplicationYAMLMapper $yamlRepository)
    {
        $this->authChecker = $authChecker;
        $this->dbRepository = $em->getRepository('Mapbender\CoreBundle\Entity\Application');
        $this->yamlRepository = $yamlRepository;
    }

    /**
     * @param Entity\Element $element
     * @param Request $request
     * @return Response
     */
    public function handleRequest(Entity\Element $element, Request $request)
    {
        switch ($request->attributes->get('action')) {
            default:
                throw new NotFoundHttpException();
            case 'granted':
                return $this->getGrantedResponse($element);
        }
    }

    protected function getGrantedResponse(Entity\Element $element)
    {
        $slugsConfigured = ArrayUtil::getDefault($element->getConfiguration(), 'applications', array());
        $slugsOut = array();
        // We must load the actual entities to perform the grants checks
        foreach ($slugsConfigured as $slug) {
            $application = $this->getApplication($slug);
            if ($application && $this->authChecker->isGranted('VIEW', $application)) {
                $slugsOut[] = $slug;
            }
        }
        return new JsonResponse(array_values($slugsOut));
    }

    /**
     * @param string $slug
     * @return Entity\Application|null
     */
    protected function getApplication($slug)
    {
        $app = $this->yamlRepository->getApplication($slug);
        if (!$app) {
            /** @var Entity\Application|null $app */
            $app = $this->dbRepository->findOneBy(array('slug' => $slug));
        }
        return $app ?: null;
    }
}
