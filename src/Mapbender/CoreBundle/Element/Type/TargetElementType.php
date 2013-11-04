<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mapbender\CoreBundle\Component\Application;
use Mapbender\CoreBundle\Form\DataTransformer\ElementIdTransformer;

/**
 * 
 */
class TargetElementType extends AbstractType
{

    /**
     * ContainerInterface
     * @var ContainerInterface Container
     */
    protected $container;

    /**
     * @inheritdoc
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'target_element';
    }

    /**
     * @inheritdoc
     */
    public function getParent()
    {
        return 'entity';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $type = $this;

        $resolver->setDefaults(array(
            'application' => null,
            'element_class' => null,
            'not_element_class' => null,
            'class' => 'MapbenderCoreBundle:Element',
            'property' => 'title',
            'query_builder' => function(Options $options) use ($type)
            {
                $builderName = preg_replace("/[^\w]/", "",
                    $options['property_path']);
                $repository = $type->getContainer()->get('doctrine')->getRepository($options['class']);
                //find all elements at application;
                $elm_ids = array();
                foreach($options['application']->getElements() as
                        $element_entity)
                {
                    $class = $element_entity->getClass();
                    $appl = new Application($type->getContainer(),
                        $options['application'], array());
                    $element = new $class($appl, $type->getContainer(),
                        $element_entity);
                    $elm_class = get_class($element);
                    if($elm_class::$ext_api)
                    {
                        $elm_ids[] = $element->getId();
                    }
                }
                $qb = $repository->createQueryBuilder($builderName);
                $filter = $qb->expr()->andX();
                $filter->add($qb->expr()->in($builderName . '.id', ':elm_ids'));
                $qb->where($filter);
                $qb->setParameter('elm_ids', $elm_ids);
                return $qb;
            }
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityManager = $this->container->get('doctrine')->getEntityManager();
        $transformer = new ElementIdTransformer($entityManager);
        $builder->addModelTransformer($transformer);
    }

}