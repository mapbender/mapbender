<?php


namespace FOM\UserBundle\Component;


use Symfony\Component\Validator\Constraints\Length;
use FOM\UserBundle\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Default implementation for service with id fom.user_helper.service
 * Provides password hashing and encoding, password constraints, and some
 * default privileges for new users.
 */
class UserHelperService
{
    public function __construct(protected PasswordHasherFactoryInterface $passwordHasherFactory)
    {
    }

    /**
     * Set salt, encrypt password and set it on the user object
     *
     * @param User $user User object to manipulate
     * @param string $password Password to encrypt and store
     */
    public function setPassword(User $user, string $password): void
    {
        $encoder = $this->passwordHasherFactory->getPasswordHasher($user);

        $salt = $this->generateSalt(32);

        $encryptedPassword = $encoder->hash($password, $salt);

        $user
            ->setPassword($encryptedPassword)
            ->setSalt($salt)
        ;
    }

    /**
     * @param int $length
     * @return string
     */
    public function generateSalt($length): string
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
    public function getPasswordConstraints(): array
    {
        return [
            new Length(min: 8),
        ];
    }

}
