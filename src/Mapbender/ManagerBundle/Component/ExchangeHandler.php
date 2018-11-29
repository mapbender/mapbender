<?php

namespace Mapbender\ManagerBundle\Component;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Description of ExportHandler
 *
 * @author Paul Schmidt
 */
abstract class ExchangeHandler
{
    /** @var EntityManagerInterface $em */
    protected $em;
    /** @var FormFactoryInterface */
    protected $formFactory;

    /**
     * @param EntityManagerInterface $entityManager
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(EntityManagerInterface $entityManager, FormFactoryInterface $formFactory)
    {
        $this->em = $entityManager;
        $this->formFactory = $formFactory;
    }

    /**
     * Creates a Job form
     * @return FormInterface
     */
    abstract public function createForm();
}
