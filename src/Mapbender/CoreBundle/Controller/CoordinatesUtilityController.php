<?php

namespace Mapbender\CoreBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Mapbender\CoreBundle\Entity\SRS;

class CoordinatesUtilityController
{
    /** @var ManagerRegistry|\Symfony\Bridge\Doctrine\RegistryInterface */
    protected $doctrineRegistry;

    public function __construct($doctrineRegistry)
    {
        $this->doctrineRegistry = $doctrineRegistry;
    }

    /**
     * Quantity of query results
     */
    const RESULTS_QUANTITY = 10;

    /**
     * Provide autocomplete for SRS
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route(path: '/srs-autocomplete', name: 'srs_autocomplete', options: ['expose' => true])]
    public function srsAutocompleteAction(Request $request)
    {
        $term = $request
            ->query
            ->get('term');

        // All SRS names are upper case!
        $term = \strtoupper($term);

        $repository = $this->doctrineRegistry->getRepository(SRS::class);
        $criteria = Criteria::create()
            ->where(Criteria::expr()->contains('name', $term))
            ->setMaxResults(self::RESULTS_QUANTITY)
        ;

        /** @var SRS[] $results */
        if ($repository instanceof Selectable) {
            $results = $repository->matching($criteria)->getValues();
        } else {
            $collection = new ArrayCollection($repository->findAll());
            $results = $collection->matching($criteria);
        }
        $responseData = array();
        foreach ($results as $srs) {
            $responseData[] = $srs->getName() . ' | ' . $srs->getTitle();
        }
        return new JsonResponse($responseData);
    }
}
