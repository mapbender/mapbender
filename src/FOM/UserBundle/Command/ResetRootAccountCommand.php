<?php

namespace FOM\UserBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use FOM\UserBundle\Component\UserHelperService;
use FOM\UserBundle\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;


/**
 * Reset root account.
 *
 * @author Christian Wygoda
 */
class ResetRootAccountCommand extends Command
{
    /** @var UserHelperService */
    protected $userHelper;
    /** @var EntityManagerInterface */
    protected $entityManager;
    /** @var EntityRepository */
    protected $userRepository;
    /** @var string */
    protected $userEntityClass;

    public function __construct(ManagerRegistry $managerRegistry,
                                UserHelperService $userHelper,
                                $userEntityClass)
    {
        parent::__construct('fom:user:resetroot');
        $this->userRepository = $managerRegistry->getRepository($userEntityClass);
        $this->entityManager = $managerRegistry->getManagerForClass($userEntityClass);
        $this->userHelper = $userHelper;
        $this->userEntityClass = $userEntityClass;
    }

    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputOption('username', '', InputOption::VALUE_REQUIRED, 'The username to use for the root account'),
                new InputOption('email', '', InputOption::VALUE_REQUIRED, 'The e-mail address for the root account'),
                new InputOption('password', '', InputOption::VALUE_REQUIRED, 'The password to set for the root account'),
                new InputOption('silent', '', InputOption::VALUE_NONE, 'Perform a silent reset')))
            ->setDescription('Resets the root account')
            ->setHelp(<<<EOT
The <info>fom:user:resetroot</info> command can be used to create or update
the root user account. This account is identified by id 1, username, e-mail
and password can be set.
EOT
            )
            ->setName('fom:user:resetroot');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        if ($input->getOption('silent')) {
            $input->setInteractive(false);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $root = $this->getRoot();

        if (!$root) {
            $userClass = $this->userEntityClass;
            /** @var User $root */
            $root = new $userClass();
            $root->setId(1);
            $mode = 'created';
            foreach (array('username', 'email', 'password') as $option) {
                if (!$input->getOption($option)) {
                    throw new \RuntimeException(
                        sprintf('The %s option must be provided.', $option));
                }
            }
        } else {
            $mode = 'updated';
        }
        if ($input->getOption('username')) {
            $root->setUsername($input->getOption('username'));
        }
        if ($input->getOption('email')) {
            $root->setEmail($input->getOption('email'));
        }
        if ($input->getOption('password')) {
            $this->userHelper->setPassword($root, $input->getOption('password'));
        }

        $this->entityManager->persist($root);
        $this->entityManager->flush();

        $output->writeln("User {$root->getUsername()} {$mode}.");
        return null;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');
        $root = $this->getRoot();

        if (!$input->getOption('username')) {
            $default = $root ? $root->getUsername() : 'root';
            $question = new Question("Enter the username to use for the root account [{$default}]: ", $default);
            $input->setOption('username', $questionHelper->ask($input, $output, $question));
        }
        if (!$input->getOption('email')) {
            $default = $root ? $root->getEmail() : '';
            $question = new Question("Enter the e-mail adress to use for the root account [{$default}]: ", $default);
            $input->setOption('email', $questionHelper->ask($input, $output, $question));
        }
        if (!$input->getOption('password')) {
            $question = new Question('Enter the password to use for the root account: ', null);
            $input->setOption('password', $questionHelper->ask($input, $output, $question));
        }
    }

    /**
     * @return User|null
     */
    protected function getRoot()
    {
        /** @var User|null $root */
        $root = $this->userRepository->find(1);
        return $root;
    }
}
