<?php

namespace Mapbender\CoreBundle\Element\Type;

use Doctrine\ORM\EntityRepository;
use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mapbender\CoreBundle\Form\DataTransformer\ElementIdTransformer;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Choice-style form element for picking an element's "target" element (this is used e.g. for a generic Button
 * controlling a functional Element like a FeatureInfo).
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

    /**
     * Returns the initialized query builder used for loading the target choices.
     *
     * @param Options $options
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getChoicesQueryBuilder(Options $options)
    {
        // strip the square brackets from the property_path to extract the entity attribute name
        $builderName = preg_replace("/[^\w]/", "", $options['property_path']);
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getRepository($options['class']);
        $qb = $repository->createQueryBuilder($builderName);
        $applicationFilter = $qb->expr()->eq($builderName . '.application', $options['application']->getId());
        $filter = $qb->expr()->andX();
        $filter->add($applicationFilter);

        if (!empty($options['element_class'])) {
            if (is_integer(strpos($options['element_class'], "%"))) {
                $classComparison = $qb->expr()->like($builderName . '.class', ':class');
            } else {
                $classComparison = $qb->expr()->eq($builderName . '.class', ':class');
            }
            $filter->add($classComparison);
            $qb->setParameter('class', $options['element_class']);
        } else {
            $elementIds = array();
            /** @var Application $applicationEntity */
            $applicationEntity = $options['application'];
            foreach ($applicationEntity->getElements() as $elementEntity) {
                $elementComponentClass = $elementEntity->getClass();
                if (class_exists($elementComponentClass)) {
                    if ($elementComponentClass::$ext_api) {
                        $elementEntity->getId();
                    }
                }
            }

            if (count($elementIds) > 0) {
                $filter->add($qb->expr()->in($builderName . '.id', ':elm_ids'));
                $qb->setParameter('elm_ids', $elementIds);
            }
        }
        $qb->where($filter);
        return $qb;
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
