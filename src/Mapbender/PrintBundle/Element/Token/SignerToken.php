<?php

namespace Mapbender\PrintBundle\Element\Token;

use JMS\Serializer\Annotation\Type;

/**
 * Class SignerToken
 *
 * @package   Mapbender\PrintBundle\Element\Token
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2014 by WhereGroup GmbH & Co. KG
 */
class SignerToken {

    /**
     * @Type("array")
     */
    protected $data;

    public function __construct(array $data = null){
        if($data){
            $this->setData($data);
        }
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

}