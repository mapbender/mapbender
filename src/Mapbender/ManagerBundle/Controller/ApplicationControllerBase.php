<?php


namespace Mapbender\ManagerBundle\Controller;


use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use FOM\UserBundle\Component\AclManager;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Component\UploadsManager;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Mapbender;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Translation\TranslatorInterface;

abstract class ApplicationControllerBase extends Controller
{
    /**
     * Check view access permissions for given application.
     *
     * Unpublished applications are viewable only by users who can also edit them.
     *
     * @param Application $application
     * @throws AccessDeniedException
     * @deprecated use ->isGranted / ->denyAccessUnlessGranted('VIEW', $application) directly
     *       Custom grant logic now resides in voters in namespace Mapbender\CoreBundle\Security\Voters
     */
    protected function checkApplicationAccess(Application $application)
    {
        $this->denyAccessUnlessGranted('VIEW', $application);
    }

    /**
     * @return AclManager
     */
    protected function getAclManager()
    {
        /** @var AclManager $service */
        $service = $this->get('fom.acl.manager');
        return $service;
    }

    /**
     * @return MutableAclProviderInterface
     */
    protected function getAclProvider()
    {
        /** @var MutableAclProviderInterface $service */
        $service = $this->get('security.acl.provider');
        return $service;
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
        return $em;
    }

    /**
     * @param string $slug
     * @param bool $includeYaml
     * @return Application
     */
    protected function requireApplication($slug, $includeYaml = false)
    {
        $repository = $this->getEntityManager()->getRepository('MapbenderCoreBundle:Application');
        /** @var Application|null $application */
        $application = $repository->findOneBy(array(
            'slug' => $slug,
        ));
        if (!$application && $includeYaml) {
            $application = $this->getYamlRepository()->getApplication($slug);
        }
        if (!$application) {
            throw $this->createNotFoundException("No such application");
        }
        return $application;
    }

    /**
     * @param string $id
     * @param Application|null $application
     * @return Layerset
     */
    protected function requireLayerset($id, $application = null)
    {
        if ($application) {
            $layersetCriteria = Criteria::create()->where(Criteria::expr()->eq('id', $id));
            /** @var Layerset|false $layerset */
            $layerset = $application->getLayersets()->matching($layersetCriteria)->first();
        } else {
            $repository = $this->getEntityManager()->getRepository('MapbenderCoreBundle:Layerset');
            $layerset = $repository->find($id);
        }
        if (!$layerset) {
            throw $this->createNotFoundException("No such layerset");
        }
        return $layerset;
    }

    /**
     * @param Request $request
     * @return string
     */
    protected function getBaseUrl(Request $request)
    {
        return $request->getSchemeAndHttpHost() . $request->getBasePath();
    }

    /**
     * @return UploadsManager
     */
    protected function getUploadsManager()
    {
        /** @var UploadsManager $service */
        $service = $this->get('mapbender.uploads_manager.service');
        return $service;
    }

    /**
     * @param Request $request
     * @return string
     */
    protected function getUploadsBaseUrl(Request $request)
    {
        $ulm = $this->getUploadsManager();
        return $this->getBaseUrl($request) . '/' . $ulm->getWebRelativeBasePath(true);
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        /** @var TranslatorInterface $service */
        $service = $this->get('translator');
        return $service;
    }

    /**
     * Get Mapbender core service
     * @return Mapbender
     */
    protected function getMapbender()
    {
        /** @var Mapbender $service */
        $service = $this->get('mapbender');
        return $service;
    }

    /**
     * @return ApplicationYAMLMapper
     */
    protected function getYamlRepository()
    {
        /** @var ApplicationYAMLMapper $service */
        $service = $this->get('mapbender.application.yaml_entity_repository');
        return $service;
    }
}
