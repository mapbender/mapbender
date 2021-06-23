<?php


namespace Mapbender\CoreBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Abstract base choice type for entities already related to a 'parent_object' entity.
 * Unlike EntityType, this doesn't need Doctrine injections and will not attempt to fetch more
 * objects from the database.
 *
 * Default submit value is the return of related objects' `getId` method.
 *
 * The 'choice_label' option is required and must be a callable or an attribute name (string) to avoid
 * implicit magic __toString invocations on the entity.
 *
 * The 'choice_filter' option can be a callable or a doctrine Criteria
 */
abstract class RelatedObjectChoiceType extends AbstractType
{
    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $type = $this;
        $resolver->setRequired(array(
            'choice_label',
            'parent_object',
        ));

        $resolver->setDefaults(array(
            'choices' => function (Options $options) use ($type) {
                return $type->getFilteredCollection($options['parent_object'], $options['choice_filter']);
            },
            'choice_value' => function(Options $options) use ($type) {
                $parentObject = $options['parent_object'];
                return function($choice) use ($parentObject, $type) {
                    if ($choice) {
                        if (!is_object($choice)) {
                            return $type->getSingleRelatedObject($parentObject, $choice);
                        }
                        return $type->getObjectIdentifier($choice);
                    } else {
                        return '';
                    }
                };
            },
            'choice_filter' => null,
            'placeholder' => null,
        ));

        $resolver->setAllowedTypes('choice_filter', array('null', 'callable'));
        $resolver->setAllowedTypes('parent_object', array('object'));
    }

    abstract protected function getRelatedObjectCollection($parentObject);

    /**
     * @param object $parentObject
     * @param Criteria|callable|null $filter
     * @return array|Collection
     */
    final protected function getFilteredCollection($parentObject, $filter)
    {
        $collection = $this->getRelatedObjectCollection($parentObject);
        if ($filter) {
            if ($filter instanceof Criteria) {
                if (!($collection instanceof Selectable)) {
                    $collection = new ArrayCollection($collection);
                }
                return $collection->matching($filter);
            }
            if ($collection instanceof Collection) {
                $collection = $collection->getValues();
            }
            return array_values(array_filter($collection, $filter));
        } else {
            return $collection;
        }
    }

    /**
     * @param object $parentObject
     * @param mixed $id
     * @return object|null
     */
    protected function getSingleRelatedObject($parentObject, $id)
    {
        foreach ($this->getRelatedObjectCollection($parentObject) as $obj) {
            if ($this->getObjectIdentifier($obj) == $id) {
                return $obj;
            }
        }
        return null;
    }

    /**
     * @param object $obj
     * @return mixed
     */
    protected function getObjectIdentifier($obj)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $obj->getId();
    }
}
