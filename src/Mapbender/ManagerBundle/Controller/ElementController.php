<?php
namespace Mapbender\ManagerBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use FOM\UserBundle\Component\AclManager;
use Mapbender\CoreBundle\Component\ElementBase\MinimalInterface;
use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\FrameworkBundle\Component\ElementEntityFactory;
use Mapbender\ManagerBundle\Component\ElementFormFactory;
use Mapbender\ManagerBundle\Utils\WeightSortedCollectionUtil;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

/**
 * Class ElementController
 *
 * @package Mapbender\ManagerBundle\Controller
 *
 * @author  Christian Wygoda <christian.wygoda@wheregroup.com>
 * @author  Andreas Schmitz <andreas.schmitz@wheregroup.com>
 * @author  Paul Schmidt <paul.schmidt@wheregroup.com>
 * @author  Andriy Oblivantsev <andriy.oblivantsev@wheregroup.com>
 */
class ElementController extends ApplicationControllerBase
{
    /** @var ElementInventoryService */
    protected $inventory;
    /** @var ElementEntityFactory */
    protected $factory;
    /** @var ElementFormFactory */
    protected $elementFormFactory;
    /** @var AclManager */
    protected $aclManager;

    public function __construct(ElementInventoryService $inventory,
                                ElementEntityFactory $factory,
                                ElementFormFactory $elementFormFactory,
                                AclManager $aclManager)
    {
        $this->inventory = $inventory;
        $this->factory = $factory;
        $this->elementFormFactory = $elementFormFactory;
        $this->aclManager = $aclManager;
    }

    /**
     * Show element class selection
     *
     * @ManagerRoute("/application/{slug}/element/select", methods={"GET", "POST"})
     * @param Request $request
     * @param string $slug
     * @return Response
     */
    public function selectAction(Request $request, $slug)
    {
        $application = $this->requireDbApplication($slug);
        $region      = $request->get('region');

        $classNames = $this->inventory->getActiveInventory();

        $elements   = array();

        /** @var MinimalInterface|string $elementClassName */
        foreach ($classNames as $elementClassName) {
            if (!$this->checkRegionCompatibility($elementClassName, $region)) {
                continue;
            }
            $elements[] = array(
                'class' => $elementClassName,
                'title' => $elementClassName::getClassTitle(),
                'description' => $elementClassName::getClassDescription(),
            );
        }

        return $this->render('@MapbenderManager/Element/select.html.twig', array(
            'elements' => $elements,
            'region' => $region,
        ));
    }

    /**
     * @param string $className
     * @param string $regionName
     * @return bool
     */
    private function checkRegionCompatibility($className, $regionName)
    {
        if (false === strpos($regionName, 'content')) {
            return !\is_a($className, 'Mapbender\CoreBundle\Component\ElementBase\FloatingElement', true);
        }
        return true;
    }

    /**
     * Shows form for creating new element
     *
     * @ManagerRoute("/application/{slug}/element/new", methods={"GET", "POST"})
     * @param Request $request
     * @param string $slug
     * @return Response
     */
    public function newAction(Request $request, $slug)
    {
        $application = $this->requireDbApplication($slug);
        $class = $request->query->get('class');
        $region = $request->query->get('region');

        $element = $this->factory->newEntity($class, $region, $application);
        $application = $element->getApplication();
        $this->denyAccessUnlessGranted('EDIT', $application);

        $formInfo = $this->elementFormFactory->getConfigurationForm($element);
        /** @var FormInterface $form */
        $form = $formInfo['form'];
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // calculate weight (append at end of region)
            $sameRegionCriteria = Criteria::create()->where(Criteria::expr()->eq('region', $element->getRegion()));
            $regionSiblings = $application->getElements()->matching($sameRegionCriteria);
            $newWeight = $regionSiblings->count();
            $element->setWeight($newWeight);

            $application->setUpdated(new \DateTime('now'));
            $em = $this->getEntityManager();
            $em->persist($application);
            $em->persist($element);
            $em->flush();
            $this->addFlash('success', 'Your element has been saved.');

            return new Response('', 201);
        }

        return $this->render('@MapbenderManager/Element/edit.html.twig', array(
            'form' => $form->createView(),
            'theme' => $formInfo['theme'],
        ));
    }

    /**
     * @ManagerRoute("/application/{slug}/element/{id}", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     * @param Request $request
     * @param string $slug
     * @param string $id
     * @return Response
     */
    public function editAction(Request $request, $slug, $id)
    {
        /** @var Element|null $element */
        $element = $this->getRepository()->find($id);

        if (!$element) {
            throw $this->createNotFoundException('The element with the id "'
                . $id . '" does not exist.');
        }

        $application = $element->getApplication();
        $this->denyAccessUnlessGranted('EDIT', $application);

        $formInfo = $this->elementFormFactory->getConfigurationForm($element);
        /** @var FormInterface $form */
        $form = $formInfo['form'];
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getEntityManager();
            $em->persist($application->setUpdated(new \DateTime('now')));
            $em->persist($element);
            $em->flush();

            $this->addFlash('success', 'Your element has been saved.');
            // NOTE: On Symfony >=4.4.44 (4.4.43 is fine), Chromium / Chrome will not receive Response headers
            //       when it immediately calls window.location.reload after a HTTP 205 "Reset Content".
            //       This will trigger a client-side "Save as" prompt (undefined content type) instead of
            //       rerendering the refreshed HTML page.
            //       => Prefer HTTP 204 "No Content" as a workaround
            # return new Response('', 205);
            return new Response('', Response::HTTP_NO_CONTENT);
        }
        return $this->render('@MapbenderManager/Element/edit.html.twig', array(
            'form' => $form->createView(),
            'theme' => $formInfo['theme'],
        ));
    }

    /**
     * Display and handle element access rights
     *
     * @ManagerRoute("/application/{slug}/element/{id}/security", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     * @param Request $request
     * @param $slug string Application short name
     * @param $id int Element ID
     * @return Response
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function securityAction(Request $request, $slug, $id)
    {
        /** @var Element|null $element */
        $element = $this->getRepository()->find($id);

        if (!$element) {
            throw $this->createNotFoundException("The element with the id \"$id\" does not exist.");
        }

        $entityManager = $this->getEntityManager();
        $entityManager->clear(Element::class); // prevent element from being stored with default config/stored again

        $application = $this->requireDbApplication($slug);
        $this->denyAccessUnlessGranted('EDIT', $application);

        $form = $this->createForm('Symfony\Component\Form\Extension\Core\Type\FormType', null, array(
            'label' => false,
        ));
        $form->add('acl', 'FOM\UserBundle\Form\Type\ACLType', array(
            'object_identity' => ObjectIdentity::fromDomainObject($element),
            'entry_options' => array(
                'mask' => MaskBuilder::MASK_VIEW,
            ),
        ));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->beginTransaction();
            try {
                $application->setUpdated(new \DateTime('now'));
                $entityManager->persist($application);
                $this->aclManager->setObjectACEs($element, $form->get('acl')->getData());
                $entityManager->flush();
                $entityManager->commit();
                $this->addFlash('success', "Your element's access has been changed.");
            } catch (\Exception $e) {
                $this->addFlash('error', "There was an error trying to change your element's access.");
                $entityManager->rollback();
                $entityManager->close();
            }
            return $this->redirectToRoute('mapbender_manager_application_edit', array(
                'slug' => $slug,
                '_fragment' => 'tabLayout',
            ));
        }
        return $this->render('@MapbenderManager/Element/security.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Delete element
     *
     * @ManagerRoute("application/{slug}/element/{id}/delete", methods={"POST"})
     * @param string $slug
     * @param string $id
     * @return Response
     */
    public function deleteAction(Request $request, $slug, $id)
    {
        /** @var Element|null $element */
        $element = $this->getRepository()->find($id);

        if (!$element) {
            throw $this->createNotFoundException('The element with the id "'
                . $id . '" does not exist.');
        }
        $application = $element->getApplication();
        $this->denyAccessUnlessGranted('EDIT', $application);

        if (!$this->isCsrfTokenValid('element_delete', $request->request->get('token'))) {
            throw new BadRequestHttpException();
        }

        $em = $this->getEntityManager();
        $higherWeightCriteria = Criteria::create()
            ->where(Criteria::expr()->eq('region', $element->getRegion()))
            ->andWhere(Criteria::expr()->gt('weight', $element->getWeight()))
        ;
        $higherWeightElements = $this->getRepository()->matching($higherWeightCriteria);
        foreach ($higherWeightElements as $otherElement) {
            /** @var Element $otherElement */
            $em->persist($otherElement);
            $otherElement->setWeight($otherElement->getWeight() - 1);
        }
        $em->remove($element);
        $application->setUpdated(new \DateTime('now'));
        $em->persist($application);
        $em->flush();

        $this->addFlash('success', 'Your element has been removed.');

        return new Response();
    }

    /**
     * @ManagerRoute("application/element/{id}/weight", methods={"POST"})
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function weightAction(Request $request, $id)
    {
        $em = $this->getEntityManager();
        /** @var Element|null $element */
        $element = $this->getRepository()->find($id);

        if (!$element) {
            throw $this->createNotFoundException('The element with the id "'
                . $id . '" does not exist.');
        }

        $application = $element->getApplication();
        $this->denyAccessUnlessGranted('EDIT', $application);

        if (!$this->isCsrfTokenValid('element_edit', $request->request->get('token'))) {
            throw new BadRequestHttpException();
        }

        $number = intval($request->get("number"));
        $targetRegionName = $request->get("region");
        if ($number === $element->getWeight() && $element->getRegion() === $targetRegionName) {
            return new JsonResponse(array(
                'error' => '',      // why?
                'result' => 'ok',   // why?
            ));
        }
        $application = $element->getApplication();
        $currentRegionName = $element->getRegion();
        $affectedRegionNames = array(
            $currentRegionName,
            $targetRegionName,
        );

        /** @var ArrayCollection[]|Element[][] $partitions */
        $partitions = $application->getElements()->partition(function($_, $entity) use ($affectedRegionNames) {
            /** @var Element $entity */
            return in_array($entity->getRegion(), $affectedRegionNames, true);
        });
        $affectedRegions = $partitions[0];
        $unaffectedRegions = $partitions[1];
        if ($currentRegionName === $targetRegionName) {
            WeightSortedCollectionUtil::updateSingleWeight($affectedRegions, $element, $number);
        } else {
            $partitions = $affectedRegions->partition(function($_, $entity) use ($targetRegionName) {
                /** @var Element $entity */
                return $entity->getRegion() === $targetRegionName;
            });
            // move from current region to target region, reassign weights in both
            WeightSortedCollectionUtil::moveBetweenCollections($partitions[0], $partitions[1], $element, $number);
            $element->setRegion($targetRegionName);
        }
        $rebuiltElementCollection = $unaffectedRegions;
        foreach ($affectedRegions as $elementToReAdd) {
            $rebuiltElementCollection->add($elementToReAdd);
        }
        $application->setElements($rebuiltElementCollection);
        $application->setUpdated(new \DateTime());
        $em->persist($application);
        $em->flush();
        return new JsonResponse(array(
            'error' => '',      // why?
            'result' => 'ok',   // why?
        ));
    }

    /**
     * @ManagerRoute("application/element/{id}/enable", methods={"POST"})
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function enableAction(Request $request, $id)
    {
        /** @var Element|null $element */
        $element = $this->getRepository()->find($id);
        $application = $element->getApplication();
        $this->denyAccessUnlessGranted('EDIT', $application);

        if (!$this->isCsrfTokenValid('element_edit', $request->request->get('token'))) {
            throw new BadRequestHttpException();
        }

        if (!$element) {
            throw $this->createNotFoundException();
        } else {
            $enabled = $request->request->get("enabled") === "true";
            $element->setEnabled($enabled);
            $em = $this->getEntityManager();
            $application = $element->getApplication();
            $application->setUpdated(new \DateTime('now'));
            $em->persist($application);
            $em->persist($element);
            $em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
    }

    /**
     * @ManagerRoute("/element/{element}/screentype", methods={"POST"})
     * @param Request $request
     * @param Element $element
     * @return Response
     */
    public function screentypeAction(Request $request, Element $element)
    {
        $application = $element->getApplication();
        $this->denyAccessUnlessGranted('EDIT', $application);

        if (!$this->isCsrfTokenValid('element_edit', $request->request->get('token'))) {
            throw new BadRequestHttpException();
        }

        $newValue = $request->request->get('screenType');
        $em = $this->getEntityManager();
        $em->persist($element);
        $em->persist($application);
        $application->setUpdated(new \DateTime());
        $element->setScreenType($newValue);
        $em->flush();
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @return EntityRepository
     */
    protected function getRepository()
    {
        /** @var EntityRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Element::class);
        return $repository;
    }
}
