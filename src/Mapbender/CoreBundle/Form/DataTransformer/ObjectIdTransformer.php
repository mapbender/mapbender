<?php
namespace Mapbender\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class ObjectIdTransformer transforms a value between different representations
 * 
 * @author Paul Schmidt
 */
class ObjectIdTransformer implements DataTransformerInterface
{
    /**
     * @var ObjectManager an object manager
     */
    private $om;
    /**
     *
     * @var string  an entity class name
     */
    private $classname;

    /**
     * Creates an instance.
     * 
     * @param ObjectManager $om an object manager
     * @param string $classname an entity class name
     */
    public function __construct(ObjectManager $om, $classname)
    {
        $this->om = $om;
        $this->classname = $classname;
    }

    /**
     * Transforms id/ids to an object/objects.
     *
     * @param mixed $data id(string) | array with ids
     * @return mixed ArrayCollection | Entity object
     */
    public function transform($data)
    {
        if (!$data) {
            return null;
        }
        if (is_array($data)) {
            $repository = $this->om->getRepository($this->classname);
            $qb = $repository->createQueryBuilder('obj');
            $qb->select('obj')->where($qb->expr()->in('obj.id', $data));
            $result = $qb->getQuery()->getResult();
            return new ArrayCollection($result);
        } else {
            $result = $this->om->getRepository($this->classname)->findOneBy(array('id' => $data));
            return $result;
        }
    }

    /**
     * Transforms a object to id/ids.
     *
     * @param  mixed $data object to reverse transform ArrayCollection | array | Entity object
     * @return mixed string | array id/ids from $data
     */
    public function reverseTransform($data)
    {
        if (null === $data) {
            return "";
        }
        if ($data instanceof ArrayCollection) {
            $result = array();
            foreach ($data as $item) {
                $result[] = (string) $item->getId();
            }
            return $result;
        } elseif (is_array($data)) {
            return $data;
        } else {
            return (string) $data->getId();
        }
    }

}
