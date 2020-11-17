<?php
namespace FOM\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

    public function getName()
    {
        return 'ace';
    }

    public function getBlockPrefix()
    {
        return 'ace';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->modelTransformer);

        $builder->add('sid', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
            'required' => true,
            'label' => 'Role or user',
            'attr' => array(
                'autocomplete' => 'off',
                'readonly' => true,
            ),
        ));

        $permissions = $options['available_permissions'];

        foreach ($permissions as $bit => $perm){
            $name = strtolower($perm);
            $builder->add('permission_' . $bit, 'FOM\ManagerBundle\Form\Type\TagboxType', array(
                'property_path' => '[permissions][' . $bit . ']',
                'attr' => array("class"=>$name)));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'available_permissions' => array(),
        ));
    }
}
