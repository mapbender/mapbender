<?php


namespace FOM\UserBundle\Component;


use FOM\UserBundle\Entity\User;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints;

/**
 * Default implementation for service with id fom.user_helper.service
 * Provides password hashing and encoding, password constraints, and some
 * default privileges for new users.
 */
class UserHelperService
{
    /** @var MutableAclProviderInterface */
    protected $aclProvider;
    /** @var EncoderFactoryInterface */
    protected $encoderFactory;
    /** @var mixed[]; from collection parameter fom_user.user_own_permissions */
    protected $permissionsOnSelf;

    /**
     * @param MutableAclProviderInterface $aclProvider
     * @param EncoderFactoryInterface $encoderFactory
     * @param mixed[] $permissionsOnSelf
     */
    public function __construct(MutableAclProviderInterface $aclProvider,
                                EncoderFactoryInterface $encoderFactory,
                                $permissionsOnSelf)
    {
        $this->aclProvider = $aclProvider;
        $this->encoderFactory = $encoderFactory;
        $this->permissionsOnSelf = $permissionsOnSelf;
    }

    /**
     * Set salt, encrypt password and set it on the user object
     *
     * @param User $user User object to manipulate
     * @param string $password Password to encrypt and store
     */
    public function setPassword(User $user, $password)
    {
        $encoder = $this->encoderFactory->getEncoder($user);

        $salt = $this->generateSalt(32);

        $encryptedPassword = $encoder->encodePassword($password, $salt);

        $user
            ->setPassword($encryptedPassword)
            ->setSalt($salt)
        ;
    }

    /**
     * @param int $length
     * @return string
     */
    public function generateSalt($length)
    {
        if ($length <= 0) {
            throw new \InvalidArgumentException("Invalid $length " . \var_export($length, true));
        }
        $salt = '';
        while ($length) {
            $chunk = min($length, 40);
            // Use password_hash to generate secure random salt
            // Strip insecure (~constant) algorithm prefix
            $salt .= substr(\password_hash('', PASSWORD_DEFAULT), -$chunk);
            $length -= $chunk;
        }
        return $salt;
    }

    /**
     * @return Constraint[]
     */
    public function getPasswordConstraints()
    {
        return array(
            new Constraints\Length(array(
                'min' => 8,
            )),
        );
    }

    /**
     * @param UserInterface $user
     * @param mixed[] $permissions
     */
    public function addPermissionsOnSelf(UserInterface $user, $permissions)
    {
        $maskBuilder = new MaskBuilder();

        $usid = UserSecurityIdentity::fromAccount($user);
        $uoid = ObjectIdentity::fromDomainObject($user);
        foreach ($permissions as $permission) {
            $maskBuilder->add($permission);
        }
        $umask = $maskBuilder->get();

        try {
            $acl = $this->aclProvider->findAcl($uoid);
        } catch(\Exception $e) {
            $acl = $this->aclProvider->createAcl($uoid);
        }
        $acl->insertObjectAce($usid, $umask);
        $this->aclProvider->updateAcl($acl);
    }

    /**
     * Gives a user the right to edit himself.
     * @param UserInterface $user
     */
    public function giveOwnRights(UserInterface $user)
    {
        $this->addPermissionsOnSelf($user, $this->permissionsOnSelf);
    }
}
