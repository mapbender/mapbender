<?php
namespace Mapbender\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class ApplicationType extends AbstractType
{

    public function getName()
    {
        return 'application';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'available_templates' => array(),
            'available_properties' => array(),
            'maxFileSize' => 0,
            'screenshotHeight' => 0,
            'screenshotWidth' => 0
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'text', array(
                'label' => 'mb.manager.admin.application.title',
                'attr' => array(
                    'title' => 'The application title, as shown in the browser '
                    . 'title bar and in lists.',
                ),
            ))
            ->add('slug', 'text', array(
                'label' => 'mb.manager.admin.application.url.title',
                'attr' => array(
                    'title' => 'The URL title (slug) is based on the title and used in the '
                    . 'application URL.',
                ),
            ))
            ->add('description', 'textarea', array(
                'required' => false,
                'label' => 'mb.manager.admin.application.description',
                'attr' => array(
                    'title' => 'The description is used in overview lists.',
                ),
            ))
            ->add('template', 'choice', array(
                'choices' => $options['available_templates'],
                'attr' => array(
                    'title' => 'The HTML template used for this application.')))
            ->add('screenshotFile', 'file', array(
                'label' => 'Screenshot',
                'attr' => array(
                    'required' => false,
                    'accept'=>'image/*')))
            ->add('removeScreenShot', 'hidden',array(
                'mapped' => false))
            ->add('uploadScreenShot', 'hidden',array(
                'mapped' => false))
            ->add('maxFileSize', 'hidden',array(
                'mapped' => false,
                'data' => $options['maxFileSize']))
            ->add('screenshotWidth', 'hidden',array(
                'mapped' => false,
                'data' => $options['screenshotWidth']))
            ->add('screenshotHeight', 'hidden',array(
                'mapped' => false,
                'data' => $options['screenshotHeight']))
            ->add('custom_css', 'textarea', array(
                'required' => false))

            // Security
            ->add('published', 'checkbox',
                array(
                'required' => false,
                'label' => 'Published'));

        $app = $options['data'];
        foreach ($options['available_properties'] as $region => $properties) {
            $data = "";
            foreach ($app->getRegionProperties() as $key => $regProps) {
                if ($regProps->getName() === $region) {
                    $help = $regProps->getProperties();
                    if (array_key_exists('name', $help)) {
                        $data = $help['name'];
                    }
                }
            }
            $choices = array();
            foreach ($properties as $values) {
                $choices[$values['name']] = $values['label'];
            }
            $builder->add($region, 'choice', array(
                'property_path' => '[' . $region . ']',
                'required' => false,
                'mapped' => false,
                'expanded' => true,
                'data' => $data,
                'choices' => $choices
            ));
        }

        // Security
        $builder->add('acl', 'acl', array(
            'mapped' => false,
            'data' => $options['data'],
            'permissions' => 'standard::object'));
    }

}
