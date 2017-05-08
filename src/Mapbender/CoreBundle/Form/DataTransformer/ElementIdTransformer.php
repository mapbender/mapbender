<?php
namespace Mapbender\CoreBundle\Form\DataTransformer;

use Doctrine\Common\Persistence\ObjectManager;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * 
 */
class ElementIdTransformer implements DataTransformerInterface
{

    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * @param ObjectManager $om
     */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    /**
     * Transforms an object (element) to a string (id).
     *
     * @param int|string $id
     * @return string
     */
    public function transform($id)
    {
        if (!$id) {
            return null;
        }

        $element = $this->om
            ->getRepository('MapbenderCoreBundle:Element')
            ->findOneBy(array('id' => $id));
        return $element;
    }

    /**
     * Transforms a string (id) to an object (element).
     *
     * @param Element|null $element
     * @return Element|null|string
     */
    public function reverseTransform($element)
    {
        if (null === $element) {
            return "";
        }
        return (string) $element->getId();
    }

}
