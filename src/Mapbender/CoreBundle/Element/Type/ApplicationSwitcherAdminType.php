<?php


namespace Mapbender\CoreBundle\Element\Type;


use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use Mapbender\ManagerBundle\Form\Type\ApplicationChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationSwitcherAdminType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'sort_first' => [],
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('applications', ApplicationChoiceType::class, array(
                'multiple' => true,
                'expanded' => true,
                'label' => 'mb.terms.application.plural',
                'attr' => array(
                    'size' => 20,
                ),
                'sort_first' => $options['sort_first'],
                'required_grant' => ResourceDomainApplication::ACTION_VIEW,
                'help' => 'mb.core.applicationSwitcher.admin.drag_to_reorder',
            ))
            ->add('open_in_new_tab', CheckboxType::class, array(
                'required' => false,
                'label' => 'mb.core.applicationSwitcher.admin.open_in_new_tab',
            ))
        ;

        // The default symfony ChoiceType reorders the choice items based on its position in the original choice list
        // we want the user to be able to reorder the items manually, so we need to redo this sorting
        // Due to sanity checks the $_POST value is not directly used but only used as order index for the sanitized data.
        $builder->get('applications')->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $unsorted = $event->getData();
            try {
                $sorted = $_POST['form']['configuration']['applications'];
            } catch(\Exception $e) {
                $sorted = [];
            }

            usort($unsorted, function ($a, $b) use ($sorted) {
                $indexA = array_search($a, $sorted);
                $indexB = array_search($b, $sorted);
                if ($indexA === false && $indexB === false) return 0;
                if ($indexA === false) return 1;
                if ($indexB === false) return -1;
                return $indexA - $indexB;
            });

            $event->setData(array_values($unsorted));
        });
    }
}
