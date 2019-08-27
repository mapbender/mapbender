<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mapbender\CoreBundle\Form\DataTransformer\ObjectIdTransformer;

class LayersetAdminType extends AbstractType
{
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
    public function configureOptions(OptionsResolver $resolver)
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
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityManager = $this->container->get('doctrine')->getManager();
        $transformer = new ObjectIdTransformer($entityManager,
            'MapbenderCoreBundle:Layerset');
        $builder->addModelTransformer($transformer);
    }

}