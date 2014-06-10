<?php
namespace Mapbender\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Mapbender\CoreBundle\Entity\Element;

/**
 * 
 */
class ObjectIdTransformer implements DataTransformerInterface
{
    /**
     * @var ObjectManager
     */
    private $om;
    private $classname;

    /**
     * @param ObjectManager $om
     */
    public function __construct(ObjectManager $om, $classname)
    {
        $this->om = $om;
        $this->classname = $classname;
    }

    /**
     * Transforms an object (element) to a string (id).
     *
     * @param  Element|null $element
     * @return string
     */
    public function transform($id)
    {
        if (!$id) {
            return null;
        }

        if (is_array($id)) {
            $em = $this->om->getRepository($this->classname)->createQueryBuilder('si')->getManager();
            $query = $em->createQuery("SELECT ob FROM " . $this->classname . " ob WHERE ob.id IN("
                . implode(',', $id) . ") ORDER BY ob.id ASC");
            $result = $query->getResult();
            return new ArrayCollection($result);
        } else {
            $result = $this->om->getRepository($this->classname)->findOneBy(array(
                'id' => $id));
            return $result;
        }
    }

    /**
     * Transforms a string (id) to an object (element).
     *
     * @param  string $id
     * @return Element|null
     * @throws TransformationFailedException if object (element) is not found.
     */
    public function reverseTransform($element)
    {
        if (null === $element) {
            return "";
        }
        if ($element instanceof ArrayCollection) {
            $result = array();
            foreach ($element as $item) {
                $result[] = (string) $item->getId();
            }
            return $result;
        } else {
            return (string) $element->getId();
        }
    }

}
