<?php


namespace Mapbender\ManagerBundle\Form\Type;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ApplicationChoiceType extends AbstractType
{
    protected ObjectRepository $dbRepository;

    public function __construct(EntityManagerInterface                  $em,
                                protected ApplicationYAMLMapper         $yamlRepository,
                                protected AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->dbRepository = $em->getRepository('Mapbender\CoreBundle\Entity\Application');
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $type = $this;
        $resolver->setDefaults(array(
            'choices' => function (Options $options) use ($type) {
                return $type->loadChoices($options['required_grant'], $options['sort_first']);
            },
            'required_grant' => null,
            'sort_first' => [],
        ));
    }

    /**
     * @return string[]
     */
    protected function loadChoices(?string $requiredGrant, array $sortFirst): array
    {
        $apps = $this->yamlRepository->getApplications();
        $apps = array_merge($apps, $this->dbRepository->findBy(array(), array(
            'title' => 'ASC',
        )));
        $choices = array();
        /** @var Application[] $applications */
        foreach ($apps as $application) {
            if (!$requiredGrant || $this->authorizationChecker->isGranted($requiredGrant, $application)) {
                $choices[$application->getTitle()] = $application->getSlug();
            }
        }

        // make sure to sort previously selected items to the start
        uasort($choices, function ($a, $b) use ($sortFirst) {
           $indexA = array_search($a, $sortFirst);
           $indexB = array_search($b, $sortFirst);
           if ($indexA === false && $indexB === false) return 0;
           if ($indexA === false) return 1;
           if ($indexB === false) return -1;
           return $indexA - $indexB;
        });

        return $choices;
    }
}
