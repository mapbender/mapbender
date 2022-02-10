<?php
namespace Mapbender\ManagerBundle\Form\Type;

use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;


class ApplicationType extends AbstractType
{
    protected $forcedMapEngine;

    public function __construct($forcedMapEngine)
    {
        $this->forcedMapEngine = $forcedMapEngine;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'maxFileSize' => 2097152,
            'screenshotHeight' => 200,
            'screenshotWidth' => 200,
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$options['data']->getId()) {
            // allow template choice only for new Application
            $builder->add('template', 'Mapbender\ManagerBundle\Form\Type\Application\TemplateChoiceType', array(
                'label' => 'mb.manager.admin.application.template',
                'label_attr' => array(
                    'title' => 'The HTML template used for this application.',
                ),
            ));
        }
        $builder
            ->add('title', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'mb.manager.admin.application.title',
                'attr' => array(
                    'title' => 'The application title, as shown in the browser '
                    . 'title bar and in lists.',
                ),
            ))
            ->add('slug', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'mb.manager.admin.application.url.title',
                'attr' => array(
                    'title' => 'The URL title (slug) is based on the title and used in the '
                    . 'application URL.',
                ),
            ))
            ->add('description', 'Symfony\Component\Form\Extension\Core\Type\TextareaType', array(
                'required' => false,
                'label' => 'mb.manager.admin.application.description',
                'attr' => array(
                    'title' => 'The description is used in overview lists.',
                ),
            ))

            ->add('screenshotFile', 'Symfony\Component\Form\Extension\Core\Type\FileType', array(
                'label' => 'mb.manager.admin.application.screenshot',
                'mapped' => false,
                'required' => false,
                'attr' => array(
                    'accept'=>'image/*',
                    'data-max-size' => $options['maxFileSize'],
                    'data-min-width' => $options['screenshotWidth'],
                    'data-min-height' => $options['screenshotHeight'],
                ),
                'constraints' => array(
                    new Constraints\Image(array(
                        'maxSize' => $options['maxFileSize'],
                        'mimeTypesMessage' => 'mb.core.entity.app.screenshotfile.format_error',
                        'minWidth' => $options['screenshotWidth'],
                        'minHeight' => $options['screenshotHeight'],
                    )),
                ),
            ))
            ->add('removeScreenShot', 'Symfony\Component\Form\Extension\Core\Type\HiddenType',array(
                'mapped' => false,
            ))
        ;
        if ($this->forcedMapEngine) {
            $builder->add('map_engine_code', 'Symfony\Component\Form\Extension\Core\Type\HiddenType', array(
                'empty_data' => $this->forcedMapEngine,
            ));
        } else {
            $builder->add('map_engine_code', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'choices' => array(
                    'mb.manager.admin.application.map_engine_code.current' => Application::MAP_ENGINE_CURRENT,
                    'mb.manager.admin.application.map_engine_code.ol2' => Application::MAP_ENGINE_OL2,
                ),
                'label' => 'mb.manager.admin.application.map_engine_code',
                'required' => true,
                'empty_data' => Application::MAP_ENGINE_OL2,
            ));
        }
        $builder
            ->add('persistentView', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.manager.application.persistentView',
            ))
            ->add('custom_css', 'Symfony\Component\Form\Extension\Core\Type\TextareaType', array(
                'required' => false,
            ))
            ->add('published', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType',
                array(
                'required' => false,
                'label' => 'mb.manager.admin.application.security.public',
            ))
        ;
        /** @var Application $application */
        $application = $options['data'];
        $builder->add('regionProperties', 'Mapbender\ManagerBundle\Form\Type\Application\RegionPropertiesType', array(
            'application' => $application,
        ));
    }
}
