<?php


namespace Mapbender\ManagerBundle\Form\Type;


use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationChoiceType extends AbstractType
{
    /** @var \Doctrine\Persistence\ObjectRepository */
    protected $dbRepository;
    /** @var ApplicationYAMLMapper */
    protected $yamlRepository;

    public function __construct(EntityManagerInterface $em, ApplicationYAMLMapper $yamlRepository)
    {
        $this->dbRepository = $em->getRepository('Mapbender\CoreBundle\Entity\Application');
        $this->yamlRepository = $yamlRepository;
    }

    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $type = $this;
        $resolver->setDefaults(array(
            'choices' => function (Options $options) use ($type) {
                return $type->loadChoices();
            },
        ));
        if (Kernel::MAJOR_VERSION < 3) {
            $resolver->setDefault('choices_as_values', true);
        }
    }

    /**
     * @return string[]
     */
    protected function loadChoices()
    {
        $apps = $this->yamlRepository->getApplications();
        $apps = array_merge($apps, $this->dbRepository->findBy(array(), array(
            'title' => 'ASC',
        )));
        $choices = array();
        /** @var Application[] $applications */
        foreach ($apps as $application) {
            $choices[$application->getTitle()] = $application->getSlug();
        }
        return $choices;
    }
}
