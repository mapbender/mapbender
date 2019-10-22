<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\ContainingKeyword;

/**
 * @author Paul Schmidt
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_core_keyword")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discriminator", type="string")
 */
abstract class Keyword
{

    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $title The source title
     * @ORM\Column(type="string", nullable=false)
     */
    protected $value;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set value
     *
     * @param  string  $value
     * @return $this
     */
    public function setValue($value)
    {
        if (mb_strlen($value) > 255) {
            $value = mb_substr($value, 0, 255);
        }
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->id;
    }

    /**
     * Get sourceclass
     *
     * @return mixed
     */
    abstract public function getReferenceObject();

    /**
     * Set reference object
     *
     * @param ContainingKeyword $reference
     * @return $this
     */
    abstract public function setReferenceObject(ContainingKeyword $reference);
}
