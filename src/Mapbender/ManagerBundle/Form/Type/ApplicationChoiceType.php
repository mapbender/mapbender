<?php


namespace Mapbender\ManagerBundle\Form\Type;


use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ApplicationChoiceType extends AbstractType
{
    /** @var \Doctrine\Persistence\ObjectRepository */
    protected $dbRepository;
    /** @var ApplicationYAMLMapper */
    protected $yamlRepository;
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    public function __construct(EntityManagerInterface $em,
                                ApplicationYAMLMapper $yamlRepository,
                                AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->dbRepository = $em->getRepository('Mapbender\CoreBundle\Entity\Application');
        $this->yamlRepository = $yamlRepository;
        $this->authorizationChecker = $authorizationChecker;
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
                return $type->loadChoices($options['required_grant']);
            },
            'required_grant' => null,
        ));
    }

    /**
     * @param string|null $requiredGrant
     * @return string[]
     */
    protected function loadChoices($requiredGrant)
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
        return $choices;
    }
}
