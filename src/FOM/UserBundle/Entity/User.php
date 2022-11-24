<?php


namespace FOM\UserBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use FOM\UserBundle\EventListener\UserProfileListener;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User entity with email and (dynamic) association to a profile entity.
 *
 * @author Christian Wygoda
 * @author apour
 * @author Paul Schmidt
 *
 * @ORM\Entity
 * @UniqueEntity("email")
 * @ORM\Table(name="fom_user")
 */
class User extends AbstractUser implements EquatableInterface
{
    /**
     * @ORM\Column(type="string", nullable=false, length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    protected $email;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $registrationTime;

    /**
     * @ORM\Column(type="string", nullable=true, length=50)
     */
    protected $registrationToken;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $resetTime;

    /**
     * @ORM\Column(type="string", nullable=true, length=50)
     */
    protected $resetToken;

    /**
     * @ORM\ManyToMany(targetEntity="Group", inversedBy="users")
     * @ORM\JoinTable(name="fom_users_groups")
     */
    protected $groups;

    /**
     * Profile relation is initialized dynamically depending on config
     * @see UserProfileListener::patchUserEntity()
     */
    protected $profile;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }

    public function isEqualTo(UserInterface $user)
    {
        // Avoid automatic implicit logout after modifying group assignments or profile information
        // see https://github.com/symfony/symfony/issues/35501
        return $user->getUsername() === $this->getUsername();
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param \DateTime|null $registrationTime
     */
    public function setRegistrationTime($registrationTime)
    {
        $this->registrationTime = $registrationTime;
    }

    /**
     * @return \DateTime|null
     */
    public function getRegistrationTime()
    {
        return $this->registrationTime;
    }

    /**
     * @param string $registrationToken
     */
    public function setRegistrationToken($registrationToken)
    {
        $this->registrationToken = $registrationToken;
    }

    /**
     * @return string
     */
    public function getRegistrationToken()
    {
        return $this->registrationToken;
    }

    /**
     * Set resetTime
     *
     * @param \DateTime|null $resetTime
     */
    public function setResetTime($resetTime)
    {
        $this->resetTime = $resetTime;
    }

    /**
     * @return \DateTime|null
     */
    public function getResetTime()
    {
        return $this->resetTime;
    }

    /**
     * @param string $resetToken
     */
    public function setResetToken($resetToken)
    {
        $this->resetToken = $resetToken;
    }

    /**
     * Get resetToken
     *
     * @return string
     */
    public function getResetToken()
    {
        return $this->resetToken;
    }

    /**
     * Add to group
     *
     * @param Group $group
     * @return $this
     */
    public function addGroup(Group $group)
    {
        $this->groups[] = $group;
        return $this;
    }

    /**
     * @return Collection|Group[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        $roles = array();
        foreach ($this->getGroups() as $group) {
            $roles[] = $group->getRole();
        }
        $roles = array_merge($roles, parent::getRoles());
        return $roles;
    }

    /**
     * @return bool
     */
    public function isAccountNonExpired()
    {
        if ($this->profile && method_exists($this->profile, 'isAccountNonExpired')) {
            return $this->profile->isAccountNonExpired();
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        if ($this->profile && method_exists($this->profile, 'isEnabled')) {
            return $this->profile->isEnabled();
        }
        return $this->registrationToken === null;
    }

    /**
     * Checks if user is root. Only used to identify fallback owner identity in
     * CLI operations.
     *
     * @return bool
     * @internal
     */
    public function isAdmin()
    {
        if ($this->getId() === 1) {
            return true;
        }
        return false;
    }

    /**
     * @param BasicProfile|null $profile
     * @return $this
     */
    public function setProfile($profile)
    {
        if ($profile && \method_exists($profile, 'setUid')) {
            $profile->setUid($this);
        }
        $this->profile = $profile;
        return $this;
    }

    /**
     * @return BasicProfile|null
     */
    public function getProfile()
    {
        return $this->profile;
    }

    // why...?
    public function __toString()
    {
        return $this->getUsername() ?: '';
    }
}
