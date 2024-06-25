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
     * @param int|string $value
     */
    public function transform($value): ?Element
    {
        if (!$value) {
            return null;
        }
        /** @var Element|null $element */
        $element = $this->repository->findOneBy(array(
            'id' => $value,
        ));
        return $element;
    }

    /**
     * @param $value ?Element
     */
    public function reverseTransform($value): string
    {
        if (null === $value) {
            return "";
        }
        return (string) $value->getId();
    }

}
