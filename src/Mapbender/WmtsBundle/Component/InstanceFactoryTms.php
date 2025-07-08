<?php


namespace Mapbender\WmtsBundle\Component;


use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmtsBundle\Form\Type\TmsInstanceType;

class InstanceFactoryTms extends InstanceFactoryCommon
{
    public function getFormTemplate(SourceInstance $instance): string
    {
        return '@MapbenderWmts/Repository/instance-tms.html.twig';
    }

    public function getFormType(SourceInstance $instance): string
    {
        return TmsInstanceType::class;
    }
}
