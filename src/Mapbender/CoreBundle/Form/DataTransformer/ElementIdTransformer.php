<?php
namespace Mapbender\CoreBundle\Form\DataTransformer;

use Doctrine\Persistence\ObjectRepository;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\Form\DataTransformerInterface;

class ElementIdTransformer implements DataTransformerInterface
{
    /**
     * @var ObjectRepository
     */
    private $repository;

    /**
     * @param ObjectRepository $repository
     */
    public function __construct(ObjectRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param int|string $id
     * @return Element|null
     */
    public function transform($id)
    {
        if (!$id) {
            return null;
        }
        /** @var Element|null $element */
        $element = $this->repository->findOneBy(array(
            'id' => $id,
        ));
        return $element;
    }

    /**
     * @param Element|null $element
     * @return string
     */
    public function reverseTransform($element)
    {
        if (null === $element) {
            return "";
        }
        return (string) $element->getId();
    }

}
