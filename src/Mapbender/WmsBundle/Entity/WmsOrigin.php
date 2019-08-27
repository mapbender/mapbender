<?php


namespace Mapbender\WmsBundle\Entity;


use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\ManagerBundle\Form\Model\HttpOriginModel;

/**
 * @deprecated remove in v3.1 use HttpOriginModel or implement HttpOriginInterface
 */
class WmsOrigin extends HttpOriginModel
{
    public function __construct($url, $userName, $password)
    {
        // no idea why we trim, mirrors old logic from RepositoryController
        $this->setOriginUrl(trim($url));
        $parts = \parse_url($url);
        if (!empty($parts['user'])) {
            $userName = \urldecode($parts['user']);
            $password = \urldecode(ArrayUtil::getDefault($parts, 'pass', ''));
        }
        $this->setUsername($userName);
        $this->setPassword($password);
    }

    public function getUrl()
    {
        return $this->getOriginUrl();
    }
}
