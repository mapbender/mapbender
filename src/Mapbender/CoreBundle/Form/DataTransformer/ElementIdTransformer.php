<?php
namespace Mapbender\CoreBundle\Form\DataTransformer;

use Doctrine\Persistence\ObjectRepository;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\Form\DataTransformerInterface;

class ElementIdTransformer implements DataTransformerInterface
{
    /**
     * @param ObjectRepository $repository
     */
    public function __construct(private readonly ObjectRepository $repository)
    {
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
        $element = $this->repository->findOneBy([
            'id' => $value,
        ]);
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
