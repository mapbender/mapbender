<?php

namespace FOM\UserBundle\Entity;
use Doctrine\ORM\Mapping as ORM;


/**
 * Basic profile entity
 *
 * @author Christian Wygoda
 * @ORM\Entity()
 * @ORM\Table(name="fom_profile_basic")
 */
class BasicProfile extends AbstractProfile
{
    const ORG_ROLE_AUTHOR =                'author';
    const ORG_ROLE_CUSTODIAN =             'custodian';
    const ORG_ROLE_DISTRIBUTOR =           'distributor';
    const ORG_ROLE_ORIGINATOR =            'originator';
    const ORG_ROLE_OWNER =                 'owner';
    const ORG_ROLE_POINTOFCONTACT =        'pointOfContact';
    const ORG_ROLE_PRINCIPALINVESTIGATOR = 'principalInvestigator';
    const ORG_ROLE_PROCESSOR =             'processor';
    const ORG_ROLE_PUBLISHER =             'publisher';
    const ORG_ROLE_RESOURCEPROVIDER =      'resourceProvider';
    const ORG_ROLE_USER =                  'user';

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $firstName;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $lastName;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $notes;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $phone;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $street;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $zipCode;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $city;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $country;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $organizationName;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $organizationRole;

    /**
     * Set organizationName
     *
     * @param string $organizationName
     * @return $this
     */
    public function setOrganizationName($organizationName)
    {
        $this->organizationName = $organizationName;

        return $this;
    }

    /**
     * Get organizationName
     *
     * @return string
     */
    public function getOrganizationName()
    {
        return $this->organizationName;
    }

    /**
     * Set organizationRole
     *
     * @param string $organizationRole
     * @return $this
     */
    public function setOrganizationRole($organizationRole)
    {
        $this->organizationRole = $organizationRole;

        return $this;
    }

    /**
     * Get organizationRole
     *
     * @return string
     */
    public function getOrganizationRole()
    {
        return $this->organizationRole;
    }

    /**
     * Get array of possible organization roles, e.g for form or validation
     * @return array Key-Value pairs of roles
     */
    static public function getOrganizationRoleChoices()
    {
        return array(
            self::ORG_ROLE_AUTHOR =>                'Author',
            self::ORG_ROLE_CUSTODIAN =>             'Custodian',
            self::ORG_ROLE_DISTRIBUTOR =>           'Distributor',
            self::ORG_ROLE_ORIGINATOR =>            'Originator',
            self::ORG_ROLE_OWNER =>                 'Owner',
            self::ORG_ROLE_POINTOFCONTACT =>        'Point of Contact',
            self::ORG_ROLE_PRINCIPALINVESTIGATOR => 'Principal Investigator',
            self::ORG_ROLE_PROCESSOR =>             'Processor',
            self::ORG_ROLE_PUBLISHER =>             'Publisher',
            self::ORG_ROLE_RESOURCEPROVIDER =>      'Resource Provider',
            self::ORG_ROLE_USER =>                  'User'
        );
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     * @return $this
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     * @return $this
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set notes
     *
     * @param string $notes
     * @return $this
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set street
     *
     * @param string $street
     * @return $this
     */
    public function setStreet($street)
    {
        $this->street = $street;

        return $this;
    }

    /**
     * Get street
     *
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Set zipCode
     *
     * @param string $zipCode
     * @return $this
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * Get zipCode
     *
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return $this
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set country
     *
     * @param string $country
     * @return $this
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }
}
