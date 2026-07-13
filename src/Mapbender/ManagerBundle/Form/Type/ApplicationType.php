<?php
namespace Mapbender\ManagerBundle\Form\Type;

use Mapbender\ManagerBundle\Form\Type\Application\TemplateChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Mapbender\ManagerBundle\Form\Type\Application\RegionPropertiesType;
use Mapbender\CoreBundle\Element\Type\MapbenderTypeTrait;
use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;


class ApplicationType extends AbstractType
{
    use MapbenderTypeTrait;
    public function __construct(private TranslatorInterface $trans) {

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'maxFileSize' => 2097152,
            'screenshotHeight' => 200,
            'screenshotWidth' => 200,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['data']->getId()) {
            // allow template choice only for new Application
            $builder->add('template', TemplateChoiceType::class, [
                'label' => 'mb.manager.admin.application.template',
                'label_attr' => [
                    'title' => 'The HTML template used for this application.',
                ],
            ]);
        }
        $builder
            ->add('title', TextType::class, [
                'label' => 'mb.manager.admin.application.title',
                'attr' => [
                    'title' => 'The application title, as shown in the browser '
                    . 'title bar and in lists.',
                ],
            ])
            ->add('slug', TextType::class, [
                'label' => 'mb.manager.admin.application.url.title',
                'attr' => [
                    'title' => 'The URL title (slug) is based on the title and used in the '
                    . 'application URL.',
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'mb.manager.admin.application.description',
                'attr' => [
                    'title' => 'The description is used in overview lists.',
                ],
            ])

            ->add('screenshotFile', FileType::class, [
                'label' => 'mb.manager.admin.application.screenshot',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept'=>'image/*',
                    'data-max-size' => $options['maxFileSize'],
                    'data-min-width' => $options['screenshotWidth'],
                    'data-min-height' => $options['screenshotHeight'],
                ],
                'constraints' => [
                    new Image(maxSize: $options['maxFileSize'], mimeTypesMessage: 'mb.core.entity.app.screenshotfile.format_error', minWidth: $options['screenshotWidth'], minHeight: $options['screenshotHeight']),
                ],
            ])
            ->add('removeScreenShot', HiddenType::class,[
                'mapped' => false,
            ])
            ->add('persistentView', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.manager.application.persistentView',
            ])
            ->add('splashscreen', CheckboxType::class, $this->createInlineHelpText([
                'label' => 'mb.manager.application.splashscreen',
                //'help' => "If true, while an application is loading, a splashscreen is shown using the branding logo and the application's title and description"
            ], $this->trans))
            ->add('custom_css', TextareaType::class, [
                'required' => false,
            ])
        ;
        /** @var Application $application */
        $application = $options['data'];
        $builder->add('regionProperties', RegionPropertiesType::class, [
            'application' => $application,
        ]);
    }
}
