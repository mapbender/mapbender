<?php
namespace Mapbender\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Doctrine\Common\Persistence\ObjectManager;
use Mapbender\CoreBundle\Entity\Element;

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
     * @param  Element|null $element
     * @return string
     */
//    public function transform($element)
        public function transform($id)
    {
        if (!$id) {
            return null;
        }

        $element = $this->om
            ->getRepository('MapbenderCoreBundle:Element')
            ->findOneBy(array('id' => $id))
        ;

        if (null === $element) {
            throw new TransformationFailedException(sprintf(
                'An element with id "%s" does not exist!',
                $id
            ));
        }

        return $element;
//        if (null === $element) {
//            return "";
//        }
//
//        return (string) $element->getId();
    }

    /**
     * Transforms a string (id) to an object (element).
     *
     * @param  string $id
     * @return Element|null
     * @throws TransformationFailedException if object (element) is not found.
     */
//    public function reverseTransform($id)
    public function reverseTransform($element)
    {
        if (null === $element) {
            return "";
        }

        return (string) $element->getId();
//        if (!$id) {
//            return null;
//        }
//
//        $element = $this->om
//            ->getRepository('MapbenderCoreBundle:Element')
//            ->findOneBy(array('id' => $id))
//        ;
//
//        if (null === $element) {
//            throw new TransformationFailedException(sprintf(
//                'An element with id "%s" does not exist!',
//                $id
//            ));
//        }
//
//        return $element;
    }
}
