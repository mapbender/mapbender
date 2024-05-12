<?php


namespace Mapbender\CoreBundle\Entity;


use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'mb_core_viewmanager_state')]
#[ORM\Index(columns: ['slug', 'user_id'])]
class ViewManagerState
{
    /**
     * @var integer
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected $id;

    /**
     * @see Application::$slug
     * @var string
     * NOTE: cannot use id (Yaml applications have no ids)
     */
    #[ORM\Column(name: 'slug', type: 'string', length: 255)]
    protected $applicationSlug;

    /**
     * @var string|null
     * NOTE: cannot use id (Yaml applications have no ids)
     * NOTE: cannot use relation (Yaml applications not visible to Doctrine)
     */
    #[ORM\Column(name: 'user_id', type: 'string', length: 255, nullable: true)]
    protected $userId;

    /**
     * Date and time of last modification
     *
     * @var \DateTime|null
     */
    #[ORM\Column(type: 'datetime')]
    protected $mtime;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 63)]
    protected $title;

    /**
     * Scalar encoded view parameters (center + scale + srs + rotation)
     * Same as url fragment in frontend
     *
     * @var string
     */
    #[ORM\Column(name: 'view_params', type: 'string', length: 47)]
    protected $viewParams;

    /**
     * @var mixed[]
     */
    #[ORM\Column(name: 'layerset_diffs', type: 'array')]
    protected $layersetDiffs;

    /**
     * @var mixed[]
     */
    #[ORM\Column(name: 'source_diffs', type: 'array')]
    protected $sourceDiffs;

    public function __construct()
    {
        $this->layersetDiffs = array();
        $this->sourceDiffs = array();
        $this->mtime = new \DateTime();
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getApplicationSlug()
    {
        return $this->applicationSlug;
    }

    /**
     * @param string $applicationSlug
     */
    public function setApplicationSlug($applicationSlug)
    {
        $this->applicationSlug = $applicationSlug;
    }

    /**
     * @param string|null $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return string|null
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return \DateTime
     */
    public function getMtime()
    {
        return $this->mtime;
    }

    /**
     * @param \DateTime $mtime
     */
    public function setMtime(\DateTime $mtime)
    {
        $this->mtime = $mtime;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getViewParams()
    {
        return $this->viewParams;
    }

    /**
     * @param string $viewParams
     */
    public function setViewParams(string $viewParams)
    {
        $this->viewParams = $viewParams;
    }

    /**
     * @return mixed[]
     */
    public function getSourceDiffs()
    {
        return $this->sourceDiffs;
    }

    /**
     * @param mixed[] $sourceDiffs
     */
    public function setSourceDiffs(array $sourceDiffs)
    {
        $this->sourceDiffs = $sourceDiffs;
    }

    /**
     * @return mixed[]
     */
    public function getLayersetDiffs()
    {
        return $this->layersetDiffs;
    }

    /**
     * @param mixed[] $layersetDiffs
     */
    public function setLayersetDiffs(array $layersetDiffs)
    {
        $this->layersetDiffs = $layersetDiffs;
    }

    /**
     * @return mixed[]
     */
    public function encode()
    {
        return array(
            'viewParams' => $this->getViewParams(),
            'layersets' => $this->getLayersetDiffs(),
            'sources' => $this->getSourceDiffs(),
        );
    }
}
