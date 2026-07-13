<?php


namespace Mapbender\FrameworkBundle\Component;


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
    public function __construct(protected TokenStorageInterface $tokenStorage)
    {
    }

    /**
     * Must returns a cleanly JSON-serializable array
     *
     * @return mixed[]
     */
    public function getValues(): array
    {
        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            return [
                'name' => null,
                'roles' => [],
                'isAnonymous' => true,
            ];
        } else {
            return [
                'name' => $token->getUserIdentifier(),
                'roles' => $token->getRoleNames(),
                'isAnonymous' => false,
            ];
        }
    }
}
