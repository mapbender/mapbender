<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Mapbender\CoreBundle\Form\DataTransformer\ObjectIdTransformer;

/**
 * 
 */
class LayersetType extends AbstractType
{
    /**
     *
     * @var type 
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
        return 'app_layerset';
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
            'class' => 'MapbenderCoreBundle:Layerset',
            'property' => 'title',
            'query_builder' => function(Options $options) use ($type) {
                $repository = $type->getContainer()->get('doctrine')->getRepository($options['class']);
                 return $repository->createQueryBuilder('ls')
                        ->select('ls')
                        ->where('ls.application = :appl')
                        ->setParameter('appl', $options['application']);
            }));
        
//        $resolver->setDefaults(array(
//            'application' => null,
//            'element_class' => null,
//            'class' => 'MapbenderCoreBundle:Element',
//            'property' => 'title',
//            'query_builder' => function(Options $options) use ($type) {
//                $builderName = preg_replace("/[^\w]/", "", $options['property_path']);
//                $repository = $type->getContainer()->get('doctrine')->getRepository($options['class']);
//                $qb = $repository->createQueryBuilder($builderName);
//                if(is_integer(strpos($options['element_class'], "%"))){
//                    $filter = $qb->expr()->andX(
//                        $qb->expr()->eq($builderName . '.application', $options['application']->getId()),
//                        $qb->expr()->like($builderName . '.class', ':class')
//                    );
//                    $qb->where($filter);
//                    $qb->setParameter('class', $options['element_class']);
//                } else {
//                    $filter = $qb->expr()->andX(
//                        $qb->expr()->eq($builderName . '.application', $options['application']->getId()),
//                        $qb->expr()->eq($builderName . '.class', ':class')
//                    );
//                    $qb->where($filter);
//                    $qb->setParameter('class', $options['element_class']);
//                }
//                return $qb;
//            }
//        ));
    }
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityManager = $this->container->get('doctrine')->getEntityManager();
        $transformer = new ObjectIdTransformer($entityManager, 'MapbenderCoreBundle:Layerset');
        $builder->addModelTransformer($transformer);
    }
}