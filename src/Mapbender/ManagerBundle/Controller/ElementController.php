<?php
namespace Mapbender\ManagerBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Mapbender\CoreBundle\Component\ElementFactory;
use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\ManagerBundle\Component\ElementFormFactory;
use Mapbender\ManagerBundle\Utils\WeightSortedCollectionUtil;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOM\ManagerBundle\Configuration\Route as ManagerRoute;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        $application = $this->requireApplication($slug);
        $template    = $application->getTemplate();
        $region      = $request->get('region');

        /** @var ElementInventoryService $inventoryService */
        $inventoryService = $this->container->get('mapbender.element_inventory.service');
        $classNames = $inventoryService->getActiveInventory();

        // Dirty hack for deprecated Responsive template
        if (method_exists($template, 'getElementWhitelist')) {
            $regionWhitelist = $template::getElementWhitelist();
            $classNames = array_intersect(array_values($regionWhitelist[$region]), $classNames);
        }

        $trans      = $this->container->get('translator');
        $elements   = array();

        foreach ($classNames as $elementClassName) {
            $title = $trans->trans($elementClassName::getClassTitle());
            $elements[$title] = array(
                'class' => $elementClassName,
                'title' => $title,
                'description' => $trans->trans($elementClassName::getClassDescription()),
            );
        }

        ksort($elements, SORT_LOCALE_STRING);
        return $this->render('@MapbenderManager/Element/select.html.twig', array(
            'elements' => $elements,
            'region' => $region,
        ));
    }

    /**
     * Shows form for creating new element
     *
     * @ManagerRoute("/application/{slug}/element/new", methods={"GET"})
     * @param Request $request
     * @param string $slug
     * @return Response
     */
    public function newAction(Request $request, $slug)
    {
        $application = $this->requireApplication($slug);
        $class       = $request->get('class'); // Get class for element

        $region = $request->query->get('region');
        $element = $this->getFactory()->newEntity($class, $region, $application);
        $formFactory = $this->getFormFactory();
        $formInfo = $formFactory->getConfigurationForm($element);
        /** @var FormInterface $form */
        $form = $formInfo['form'];
        return $this->render('@MapbenderManager/Element/edit.html.twig', array(
            'form' => $form->createView(),
            'theme' => $formInfo['theme'],
            'formAction' => $this->generateUrl('mapbender_manager_element_create', array(
                'slug' => $slug,
            )),
        ));
    }

    /**
     * Create a new element from POSTed data
     *
     * @ManagerRoute("/application/{slug}/element/new", methods={"POST"})
     * @param Request $request
     * @param string $slug
     * @return Response
     */
    public function createAction(Request $request, $slug)
    {
        $application = $this->requireApplication($slug);

        $data = $request->get('form');
        $element = $this->getFactory()->newEntity($data['class'], $data['region'], $application);
        $formFactory = $this->getFormFactory();
        $formInfo = $formFactory->getConfigurationForm($element);

        /** @var FormInterface $form */
        $form = $formInfo['form'];
        $form->submit($request);

        if ($form->isValid()) {
            $sameRegionCriteria = Criteria::create()->where(Criteria::expr()->eq('region', $element->getRegion()));
            $regionSiblings = $application->getElements()->matching($sameRegionCriteria);
            $newWeight = $regionSiblings->count();
            $element->setWeight($newWeight);
            $application = $element->getApplication();
            $application->setUpdated(new \DateTime('now'));
            $em = $this->getEntityManager();
            $em->persist($application);
            $em->persist($element);
            $em->flush();
            $this->addFlash('success', 'Your element has been saved.');

            return new Response('', 201);
        } else {
            return $this->render('@MapbenderManager/Element/edit.html.twig', array(
                'form' => $form->createView(),
                'theme' => $formInfo['theme'],
                'formAction' => $this->generateUrl('mapbender_manager_element_create', array(
                    'slug' => $slug,
                )),
            ));
        }
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
        $formFactory = $this->getFormFactory();
        $formInfo = $formFactory->getConfigurationForm($element);
        /** @var FormInterface $form */
        $form = $formInfo['form'];
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getEntityManager();
            $application = $element->getApplication();
            $em->persist($application->setUpdated(new \DateTime('now')));
            $em->persist($element);
            $em->flush();

            $this->addFlash('success', 'Your element has been saved.');

            return new Response('', 205);
        }
        return $this->render('@MapbenderManager/Element/edit.html.twig', array(
            'form' => $form->createView(),
            'theme' => $formInfo['theme'],
            'formAction' => $this->generateUrl('mapbender_manager_element_edit', array(
                'slug' => $slug,
                'id' => $id,
            )),
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
        $entityManager->detach($element); // prevent element from being stored with default config/stored again

        $application = $this->requireApplication($slug);
        $form = $this->createForm('acl', $element, array(
            'mapped' => false,
            'create_standard_permissions' => false,
            'permissions' => array(
                1 => 'View',
            ),
        ));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->beginTransaction();
            try {
                $aclManager  = $this->getAclManager();
                $application->setUpdated(new \DateTime('now'));
                $entityManager->persist($application);
                $aclManager->setObjectACEs($element, $form->get('ace')->getData());
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
    public function deleteAction($slug, $id)
    {
        /** @var Element|null $element */
        $element = $this->getRepository()->find($id);

        if (!$element) {
            throw $this->createNotFoundException('The element with the id "'
                . $id . '" does not exist.');
        }
        $application = $element->getApplication();

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

        $enabled = $request->get("enabled");
        if (!$element) {
            return new JsonResponse(array(
                /** @todo: use http status codes to communicate error conditions */
                'error' => 'An element with the id "' . $id . '" does not exist.',
            ));

        } else {
            $enabled_before = $element->getEnabled();
            $enabled = $enabled === "true" ? true : false;
            $element->setEnabled($enabled);
            $em = $this->getEntityManager();
            $application = $element->getApplication();
            $application->setUpdated(new \DateTime('now'));
            $em->persist($application);
            $em->persist($element);
            $em->flush();
            return new JsonResponse(array(
                'success' => array(         // why?
                    "id" => $element->getId(),
                    "type" => "element",
                    "enabled" => array(
                        'before' => $enabled_before,
                        'after' => $enabled,
                    ),
                ),
            ));
        }
    }

    /**
     * @return ElementFactory
     */
    protected function getFactory()
    {
        /** @var ElementFactory $service */
        $service = $this->get('mapbender.element_factory.service');
        return $service;
    }

    /**
     * @return ElementFormFactory
     */
    protected function getFormFactory()
    {
        /** @var ElementFormFactory $service */
        $service = $this->get('mapbender.manager.element_form_factory.service');
        return $service;
    }

    /**
     * @return EntityRepository
     */
    protected function getRepository()
    {
        /** @var EntityRepository $repository */
        $repository = $this->getEntityManager()->getRepository('MapbenderCoreBundle:Element');
        return $repository;
    }
}
