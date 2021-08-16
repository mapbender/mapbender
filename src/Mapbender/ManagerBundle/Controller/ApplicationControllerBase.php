<?php


namespace Mapbender\ManagerBundle\Controller;


use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class ApplicationControllerBase extends AbstractController
{
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
            $repository = $this->getDoctrine()->getRepository(Layerset::class);
            $layerset = $repository->find($id);
        }
        if (!$layerset) {
            throw $this->createNotFoundException("No such layerset");
        }
        return $layerset;
    }

    /**
     * @param string $slug
     * @return Application
     */
    protected function requireDbApplication($slug)
    {
        /** @var Application|null $application */
        $application = $this->getDoctrine()->getRepository(Application::class)->findOneBy(array(
            'slug' => $slug,
        ));
        if (!$application) {
            throw $this->createNotFoundException("No such application");
        }
        return $application;
    }
}
