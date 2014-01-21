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
class LayersetSourcesAdminType extends AbstractType
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
        return 'layerset_sources';
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
            'target_layerset' => null,
            'class' => 'MapbenderCoreBundle:SourceInstance',
            'property' => 'title',
            'query_builder' => function(Options $options) use ($type) {
                $repository = $type->getContainer()->get('doctrine')->getRepository($options['class']);
                return $repository->createQueryBuilder('ls')
                        ->select('ls')
                        ->where('ls.layerset = :layerset AND ls.enabled = true')
                        ->setParameter('layerset', $options['target_layerset']);
            }));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityManager = $this->container->get('doctrine')->getEntityManager();
        $transformer = new ObjectIdTransformer($entityManager,
            'MapbenderCoreBundle:SourceInstance');
        $builder->addModelTransformer($transformer);
    }

}