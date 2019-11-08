<?php


namespace Mapbender\ManagerBundle\Form\Type\Application;


use Mapbender\CoreBundle\Component\MapbenderBundle;
use Mapbender\CoreBundle\Component\Template;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TemplateChoiceType extends AbstractType
{
    protected $choices = array();

    public function __construct($bundleClassNames)
    {
        foreach ($bundleClassNames as $bundleClassName) {
            if (\is_a($bundleClassName, 'Mapbender\CoreBundle\Component\MapbenderBundle', true)) {
                /** @var MapbenderBundle $bundle */
                $bundle = new $bundleClassName();
                $bundleTemplateClasses = $bundle->getTemplates();
                foreach ($bundleTemplateClasses as $templateClass) {
                    /** @var string|Template $templateClass */
                    $title = $templateClass::getTitle();
                    $this->choices[$title] = $templateClass;
                }
            }
        }
        ksort($this->choices);
    }

    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'choices' => $this->choices,
        ));
        if (\Symfony\Component\HttpKernel\Kernel::MAJOR_VERSION < 3) {
            $resolver->setDefault('choices_as_values', true);
        }
    }
}
