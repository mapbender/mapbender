<?php
namespace FOM\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

class ACEType extends AbstractType
{
    /** @var DataTransformerInterface */
    protected $modelTransformer;

    /**
     * @param DataTransformerInterface $modelTransformer
     */
    public function __construct(DataTransformerInterface $modelTransformer)
    {
        $this->modelTransformer = $modelTransformer;
    }

    public function getBlockPrefix()
    {
        return 'ace';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->modelTransformer);

        $builder->add('sid', 'Symfony\Component\Form\Extension\Core\Type\HiddenType', array(
            'required' => true,
            'label' => false,
            'attr' => array(
                'autocomplete' => 'off',
                'readonly' => true,
            ),
        ));
        for ($bit = 0; $bit <= 7; ++$bit) {
            if ($options['mask'] & (1 << $bit)) {
                $name = $this->getPermissionName(1 << $bit);
                $builder
                    ->add('permission_' . $bit, 'FOM\ManagerBundle\Form\Type\TagboxType', array(
                        'property_path' => '[permissions][' . ($bit + 1) . ']',
                        'attr' => array(
                            'class' => $name
                        ),
                    ))
                ;
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'mask' => array_sum(array(
                MaskBuilder::MASK_VIEW,
                MaskBuilder::MASK_CREATE,
                MaskBuilder::MASK_EDIT,
                MaskBuilder::MASK_DELETE,
                MaskBuilder::MASK_OPERATOR,
                MaskBuilder::MASK_MASTER,
                MaskBuilder::MASK_OWNER,
            )),
        ));
    }

    protected static function getPermissionName($value)
    {
        switch ($value) {
            default:
                throw new \InvalidArgumentException("Unsupported value " . print_r($value, true));
            case MaskBuilder::MASK_VIEW:
                return 'view';
            case MaskBuilder::MASK_CREATE:
                return 'create';
            case MaskBuilder::MASK_EDIT:
                return 'edit';
            case MaskBuilder::MASK_DELETE:
                return 'delete';
            case MaskBuilder::MASK_OPERATOR:
                return 'operator';
            case MaskBuilder::MASK_MASTER:
                return 'master';
            case MaskBuilder::MASK_OWNER:
                return 'owner';
        }
    }
}
