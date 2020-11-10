<?php

namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @author Paul Schmidt
 *
 * @ORM\Entity
 * @ORM\Table(name="mb_core_contact")
 */
class Contact
{

    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $person;

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $position;

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $organization;

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $voiceTelephone;

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $facsimileTelephone;

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $electronicMailAddress;

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $address;

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $addressType;

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $addressCity;

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $addressStateOrProvince;

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $addressPostCode;

    /**
     * @ORM\Column(type="string",nullable=true)
     */
    protected $addressCountry;

    /**
     * Set person
     *
     * @param  string  $person
     * @return $this
     */
    public function setPerson($person)
    {
        $this->person = $person;

        return $this;
    }

    /**
     * Get person
     *
     * @return string
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * Set position
     *
     * @param  string  $position
     * @return $this
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set organization
     *
     * @param  string  $organization
     * @return $this
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return string
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set voiceTelephone
     *
     * @param  string  $voiceTelephone
     * @return $this
     */
    public function setVoiceTelephone($voiceTelephone)
    {
        $this->voiceTelephone = $voiceTelephone;

        return $this;
    }

    /**
     * Get voiceTelephone
     *
     * @return string
     */
    public function getVoiceTelephone()
    {
        return $this->voiceTelephone;
    }

    /**
     * Set facsimileTelephone
     *
     * @param  string  $facsimileTelephone
     * @return $this
     */
    public function setFacsimileTelephone($facsimileTelephone)
    {
        $this->facsimileTelephone = $facsimileTelephone;

        return $this;
    }

    /**
     * Get facsimileTelephone
     *
     * @return string
     */
    public function getFacsimileTelephone()
    {
        return $this->facsimileTelephone;
    }

    /**
     * Set electronicMailAddress
     *
     * @param  string  $electronicMailAddress
     * @return $this
     */
    public function setElectronicMailAddress($electronicMailAddress)
    {
        $this->electronicMailAddress = $electronicMailAddress;

        return $this;
    }

    /**
     * Get electronicMailAddress
     *
     * @return string
     */
    public function getElectronicMailAddress()
    {
        return $this->electronicMailAddress;
    }

    /**
     * Set address
     *
     * @param  string  $address
     * @return $this
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set addressType
     *
     * @param  string  $addressType
     * @return $this
     */
    public function setAddressType($addressType)
    {
        $this->addressType = $addressType;

        return $this;
    }

    /**
     * Get addressType
     *
     * @return string
     */
    public function getAddressType()
    {
        return $this->addressType;
    }

    /**
     * Set addressCity
     *
     * @param  string  $addressCity
     * @return $this
     */
    public function setAddressCity($addressCity)
    {
        $this->addressCity = $addressCity;

        return $this;
    }

    /**
     * Get addressCity
     *
     * @return string
     */
    public function getAddressCity()
    {
        return $this->addressCity;
    }

    /**
     * Set addressStateOrProvince
     *
     * @param  string  $addressStateOrProvince
     * @return $this
     */
    public function setAddressStateOrProvince($addressStateOrProvince)
    {
        $this->addressStateOrProvince = $addressStateOrProvince;

        return $this;
    }

    /**
     * Get addressStateOrProvince
     *
     * @return string
     */
    public function getAddressStateOrProvince()
    {
        return $this->addressStateOrProvince;
    }

    /**
     * Set addressPostCode
     *
     * @param  string  $addressPostCode
     * @return $this
     */
    public function setAddressPostCode($addressPostCode)
    {
        $this->addressPostCode = $addressPostCode;

        return $this;
    }

    /**
     * Get addressPostCode
     *
     * @return string
     */
    public function getAddressPostCode()
    {
        return $this->addressPostCode;
    }

    /**
     * Set addressCountry
     *
     * @param  string  $addressCountry
     * @return $this
     */
    public function setAddressCountry($addressCountry)
    {
        $this->addressCountry = $addressCountry;

        return $this;
    }

    /**
     * Get addressCountry
     *
     * @return string
     */
    public function getAddressCountry()
    {
        return $this->addressCountry;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

}
