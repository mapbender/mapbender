<?php


namespace Mapbender\WmtsBundle\Component;


use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmtsBundle\Form\Type\TmsInstanceType;

class InstanceFactoryTms extends InstanceFactoryCommon
{
    public function getFormTemplate(SourceInstance $instance)
    {
        return '@MapbenderWmts/Repository/instance-tms.html.twig';
    }

    public function getFormType(SourceInstance $instance)
    {
        return TmsInstanceType::class;
    }
}
