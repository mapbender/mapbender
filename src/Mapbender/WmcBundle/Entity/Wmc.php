<?php

namespace Mapbender\WmcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="wmc")
 */
class Wmc {
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $owner;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="boolean")
     */
    private $public;

    /**
     * @ORM\Column(type="text")
     */
    private $document;

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set owner
     *
     * @param string $owner
     */
    public function setOwner($owner) {
        $this->owner = $owner;
    }

    /**
     * Get owner
     *
     * @return string $owner
     */
    public function getOwner() {
        return $this->owner;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string $title
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set public
     *
     * @param boolean $public
     */
    public function setPublic($public) {
        $this->public = $public;
    }

    /**
     * Get public
     *
     * @return boolean $public
     */
    public function getPublic() {
        return $this->public;
    }

    /**
     * Set document
     *
     * @param text $document
     */
    public function setDocument($document) {
        $this->document = $document;
    }

    /**
     * Get document
     *
     * @return text $document
     */
    public function getDocument() {
        return $this->document;
    }
}

