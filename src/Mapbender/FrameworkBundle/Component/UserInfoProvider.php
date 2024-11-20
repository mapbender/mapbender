<?php


namespace Mapbender\FrameworkBundle\Component;


use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Default implementation for `mapbender.user_info_provider`.
 *
 * If constructor is compatible, class may be respecified using the
 * `mapbender.user_info_provider.class` container parameter.
 *
 * @since v3.2.2
 */
class UserInfoProvider
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Must returns a cleanly JSON-serializable array
     *
     * @return mixed[]
     */
    public function getValues()
    {
        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            return array(
                'name' => null,
                'roles' => array(),
                'isAnonymous' => true,
            );
        } else {
            return array(
                'name' => $token->getUserIdentifier(),
                'roles' => $token->getRoleNames(),
                'isAnonymous' => false,
            );
        }
    }
}
