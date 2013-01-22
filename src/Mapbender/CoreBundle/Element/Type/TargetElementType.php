<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Mapbender\CoreBundle\Form\DataTransformer\ElementIdTransformer;

class TargetElementType extends AbstractType
{
    protected $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    public function getContainer()
    {
        return $this->container;
    }
    
    public function getName()
    {
        return 'target_element';
    }
    
    public function getParent()
    {
        return 'entity';
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $type = $this;
        
        $resolver->setDefaults(array(
            'application' => null,
            'element_class' => null,
            'class' => 'MapbenderCoreBundle:Element',
            'property' => 'title',
            'query_builder' => function(Options $options) use ($type) {
                $builderName = preg_replace("/[^\w]/", "", $options['property_path']);
                $repository = $type->getContainer()->get('doctrine')->getRepository($options['class']);
                $qb = $repository->createQueryBuilder($builderName);
                if(is_integer(strpos($options['element_class'], "%"))){
                    $filter = $qb->expr()->andX(
                        $qb->expr()->eq($builderName . '.application', $options['application']->getId()),
                        $qb->expr()->like($builderName . '.class', ':class')
                    );
                    $qb->where($filter);
                    $qb->setParameter('class', $options['element_class']);
                } else {
                    $filter = $qb->expr()->andX(
                        $qb->expr()->eq($builderName . '.application', $options['application']->getId()),
                        $qb->expr()->eq($builderName . '.class', ':class')
                    );
                    $qb->where($filter);
                    $qb->setParameter('class', $options['element_class']);
                }
                return $qb;
            }
        ));
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityManager = $this->container->get('doctrine')->getEntityManager();
        $transformer = new ElementIdTransformer($entityManager);
        $builder->addModelTransformer($transformer);
    }
}