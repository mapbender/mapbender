<?php //
//
//namespace Mapbender\CoreBundle\Entity;
//
//use Doctrine\Common\Collections\ArrayCollection;
//use Doctrine\ORM\EntityManager;
//use Doctrine\ORM\Mapping as ORM;
//
///**
// * Source entity
// *
// * @author Paul Schmidt
// *
// * @#ORM\Entity
// * @#ORM\Table(name="mb_core_applicationstate")
// * @#ORM\InheritanceType("JOINED")
// * @#ORM\DiscriminatorColumn(name="discr", type="string")
// * #ORM\DiscriminatorMap({"mb_core_source" = "Source"})
// */
//class ApplicationState
//{
//
//    /**
//     * @var integer $id
//     * @ORM\Id
//     * @ORM\Column(type="integer")
//     * @ORM\GeneratedValue(strategy="AUTO")
//     */
//    protected $id;
//
//    /**
//     * @var string $title The source title
//     * @ORM\Column(type="string", nullable=true)
//     */
//    protected $window;
//
//    /**
//     * @var string $bboxMax the max bbox 
//     * @ORM\Column(type="string", nullable=true)
//     */
//    protected $bboxMax;
//
//    /**
//     * @var string $bbox the max bbox 
//     * @ORM\Column(type="string", nullable=true)
//     */
//    protected $bbox;
//    
//    public function __construct()
//    {
//        $this->window = new Size();
//    }
//    
//    /**
//     * Get id
//     *
//     * @return integer 
//     */
//    public function getId()
//    {
//        return $this->id;
//    }
//
//    /**
//     * Set a window
//     *
//     * @param Size $size
//     * @return ApplicationState
//     */
//    public function setWindow($size)
//    {
//        $this->window = $size;
//        return $this;
//    }
//
//    /**
//     * Returns a window
//     *
//     * @return Size 
//     */
//    public function getWindow()
//    {
//        return $this->window;
//    }
//
//    /**
//     * Set a bbox
//     *
//     * @param BoundingBox $bbox
//     * @return ApplicationState
//     */
//    public function setBbox($bbox)
//    {
//        $this->bbox = $bbox;
//        return $this;
//    }
//
//    /**
//     * Returns a bbox
//     *
//     * @return BoundingBox 
//     */
//    public function getBbox()
//    {
//        return $this->bbox;
//    }
//    
//    /**
//     * Set a bboxMax
//     *
//     * @param BoundingBox $bbox
//     * @return ApplicationState
//     */
//    public function setBboxMax($bbox)
//    {
//        $this->bboxMax = $bbox;
//        return $this;
//    }
//
//    /**
//     * Returns a bboxMax
//     *
//     * @return BoundingBox 
//     */
//    public function getBboxMax()
//    {
//        return $this->bboxMax;
//    }
//
//    /**
//     * Returns a Source as String
//     * 
//     * @return String Source as String
//     */
//    public function __toString()
//    {
//        return (string) $this->id;
//    }
//
//}
