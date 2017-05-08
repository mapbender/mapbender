<?php
namespace Mapbender\CoreBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Entity;
use Symfony\Component\Form\DataTransformerInterface;

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
     * @var string  an entity class name
     */
    private $className;

    /**
     * Creates an instance.
     * 
     * @param ObjectManager $om        an object manager
     * @param string        $className an entity class name
     */
    public function __construct(ObjectManager $om, $className)
    {
        $this->om        = $om;
        $this->className = $className;
    }

    /**
     * Transforms id/ids to an object/objects.
     *
     * @param mixed $data id(string) | array with ids
     * @return ArrayCollection|null|Entity|object object
     */
    public function transform($data)
    {
        if (!$data) {
            return null;
        }

        $objectRepository = $this->getObjectRepository();

        if (is_array($data)) {
            $qb         = $objectRepository->createQueryBuilder('obj');
            $qb->select('obj')->where($qb->expr()->in('obj.id', $data));
            $result = $qb->getQuery()->getResult();
            return new ArrayCollection($result);
        } else {
            return $objectRepository->findOneBy(array('id' => $data));
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

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function getObjectRepository()
    {
        return $this->om->getRepository($this->className);
    }

}
