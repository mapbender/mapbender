<?php


namespace Mapbender\CoreBundle\Entity;


use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="mb_core_viewmanager_state", indexes={@ORM\Index(columns={"slug", "user_id"})})
 */
class ViewManagerState
{
    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @see Application::$slug
     * @var string
     * @ORM\Column(type="string", length=255, name="slug")
     * NOTE: cannot use id (Yaml applications have no ids)
     * NOTE: cannot use relation (Yaml applications not visible to Doctrine)
     */
    protected $applicationSlug;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, name="user_id", nullable=true)
     * NOTE: cannot use a relation; cannot guarantee database record for any user with LDAP / other custom user providers
     */
    protected $userId;

    /**
     * Date and time of last modification
     *
     * @var \DateTime|null
     * @ORM\Column(type="datetime")
     */
    protected $mtime;

    /**
     * @var string
     * @ORM\Column(type="string", length=63)
     */
    protected $title;

    /**
     * Scalar encoded view parameters (center + scale + srs + rotation)
     * Same as url fragment in frontend
     *
     * @var string
     * @ORM\Column(type="string", length=47, name="view_params")
     */
    protected $viewParams;

    /**
     * @var mixed[]
     * @ORM\Column(type="array", name="layerset_diffs")
     */
    protected $layersetDiffs;

    /**
     * @var mixed[]
     * @ORM\Column(type="array", name="source_diffs")
     */
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
