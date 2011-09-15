<?php

namespace Mapbender\Component;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use Symfony\Component\HttpFoundation\Request;


/**
 * @Author: Karim Malhas <karim@malhas.de>
 * @package: bkg
 * This Class overides the default Doctrine ParamConverter to allow routes like this:
 * @Route(/{/{nameaId}/foo/{namebId})
 * public function (A $namea, B $namebId )
*/
class CustomDoctrineParamConverter extends DoctrineParamConverter {

    protected $configuration = null;

    public function apply(Request $request, ConfigurationInterface $configuration) {
        $this->configuration = $configuration;
        return parent::apply($request,$configuration);

    }

    
    protected function find($class, Request $request, $options) {   

        $name = $this->configuration->getName();

        if (!$request->attributes->has($name."Id")) {
            return parent::find($class,$request,$options);
        }   

        return $this->registry->getRepository($class, $options['entity_manager'])->find($request->attributes->get($name."Id"));
    }
}
