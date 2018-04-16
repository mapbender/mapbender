<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mapbender\CoreBundle\Component\Application;
use Mapbender\CoreBundle\Form\DataTransformer\ElementIdTransformer;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

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
            'class' => 'MapbenderCoreBundle:Element',
            'property' => 'title',
            'empty_value' => 'Choose an option',
            'empty_data' => '',
            // Symfony does not recognize array-style callables
            'query_builder' => function(Options $options) use ($type) {
                return $type->getChoicesQueryBuilder($options);
            }
        ));
    }

    public function getChoicesQueryBuilder(Options $options)
    {
        $builderName = preg_replace("/[^\w]/", "", $options['property_path']);
        $repository = $this->getContainer()->get('doctrine')->getRepository($options['class']);
        if (isset($options['element_class']) && $options['element_class'] !== null) {
            $qb = $repository->createQueryBuilder($builderName);
            if (is_integer(strpos($options['element_class'], "%"))) {
                $filter = $qb->expr()->andX(
                        $qb->expr()->eq($builderName . '.application', $options['application']->getId()),
                                        $qb->expr()->like($builderName . '.class', ':class'));
                $qb->where($filter);
                $qb->setParameter('class', $options['element_class']);
            } else {
                $filter = $qb->expr()->andX(
                        $qb->expr()->eq($builderName . '.application', $options['application']->getId()),
                                        $qb->expr()->eq($builderName . '.class', ':class'));
                $qb->where($filter);
                $qb->setParameter('class', $options['element_class']);
            }
            return $qb;
        } else {
            $elm_ids = array();
            foreach ($options['application']->getElements() as $element_entity) {
                $class = $element_entity->getClass();
                $appl = new Application($this->getContainer(), $options['application'], array());
                $element = new $class($appl, $this->getContainer(), $element_entity);
                $elm_class = get_class($element);
                if ($elm_class::$ext_api) {
                    $elm_ids[] = $element->getId();
                }
            }
            $qb = $repository->createQueryBuilder($builderName);
            $filter = $qb->expr()->andX();
            if (count($elm_ids) > 0) {
                $filter->add($qb->expr()->in($builderName . '.id', ':elm_ids'));
                $qb->where($filter);
                $qb->setParameter('elm_ids', $elm_ids);
            } else {
                $filter->add($qb->expr()->eq($builderName . '.application', $options['application']->getId()));
                $qb->where($filter);
            }
            return $qb;
        }
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityManager = $this->container->get('doctrine')->getManager();
        $transformer = new ElementIdTransformer($entityManager);
        $builder->addModelTransformer($transformer);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var \Symfony\Component\Translation\TranslatorInterface $translator */
        $translator = $this->container->get('translator');
        $translatedLcLabels = array_map(function($element) use ($translator) {
            $transLabel = $translator->trans($element->label);
            // sorting should be case-insensitive
            return mb_strtolower($transLabel);
            }, $view->vars['choices']);
        // we use array_multisort instead of usort to avoid a bug in many
        // PHP5.x versions
        // see https://bugs.php.net/bug.php?id=50688
        array_multisort($view->vars['choices'], $translatedLcLabels);
    }

}
