<?php

namespace FOM\UserBundle\Command;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use FOM\UserBundle\Component\UserHelperService;
use FOM\UserBundle\Entity\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;


/**
 * Reset root account.
 *
 * @author Christian Wygoda
 */
#[AsCommand('fom:user:resetroot', help: <<<'TXT'
The <info>fom:user:resetroot</info> command can be used to create or update
the root user account. This account is identified by id 1. Username, e-mail
and password can be set. The password can be set in multiple ways (in priority order):
- option --password
- Flag --generate-password (will generate a 12-digit password with alphanumeric letters/digits and common special chars)
- ENV variable MAPBENDER_ROOT_PASSWORD
- console input (unless --no-interaction is set)
TXT
)]
class ResetRootAccountCommand extends Command
{
    protected ?ObjectManager $entityManager;
    /** @var EntityRepository */
    protected ObjectRepository $userRepository;
    protected string $userEntityClass;

    public function __construct(ManagerRegistry             $managerRegistry,
                                protected UserHelperService $userHelper,
                                                            $userEntityClass)
    {
        parent::__construct('fom:user:resetroot');
        $this->userRepository = $managerRegistry->getRepository($userEntityClass);
        $this->entityManager = $managerRegistry->getManagerForClass($userEntityClass);
        $this->userEntityClass = $userEntityClass;
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputOption('username', '', InputOption::VALUE_REQUIRED, 'The username to use for the root account'),
                new InputOption('email', '', InputOption::VALUE_REQUIRED, 'The e-mail address for the root account'),
                new InputOption('password', '', InputOption::VALUE_OPTIONAL, 'The password to set for the root account. Alternative methods of supplying password: Flag --generate-password, ENV variable MAPBENDER_ROOT_PASSWORD, console input'),
                new InputOption('generate-password', '', InputOption::VALUE_NONE, 'Auto-generates the password if password option is not provided.'),
                new InputOption('no-interaction', 'n', InputOption::VALUE_NONE, 'Do not ask any interactive question'),
            ])
            ->setDescription('Resets the root account')
        ;
    }

    protected ?string $passwordNotice = null;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $root = $this->getRoot();

        if (!$root) {
            $userClass = $this->userEntityClass;
            /** @var User $root */
            $root = new $userClass();
            $root->setId(1);
            $mode = 'created';
            foreach (['username', 'email'] as $option) {
                if (!$input->getOption($option)) {
                    throw new \RuntimeException("The $option option must be provided.");
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


        $password = $this->getPassword($input, $output);
        if ($password) {
            $this->userHelper->setPassword($root, $password);
        }

        // if the user entity was just created we need to set a password. If it is not supplied and the
        // generated flag is not set, just set to "root"
        if ($mode === 'created' && !$password) {
            $this->userHelper->setPassword($root, 'root');
            $this->passwordNotice = 'root user password set to "root". Please change as soon as possible.';
        }

        $this->entityManager->persist($root);
        $this->entityManager->flush();

        $output->writeln("User {$root->getUserIdentifier()} {$mode}.");
        if ($this->passwordNotice) $output->writeln($this->passwordNotice);
        return 0;
    }

    /**
     * Reads the supplied password in this priority order
     * - option --password
     * - Flag --generate-password
     * - ENV variable MAPBENDER_ROOT_PASSWORD
     * - console input
     */
    protected function getPassword(InputInterface $input, OutputInterface $output): ?string
    {
        $this->passwordNotice = null;

        if ($input->getOption('password')) {
            return $input->getOption('password');
        }

        if ($input->getOption('generate-password')) {
            $password = static::randomStr(12);
            $this->passwordNotice = "Generated password: $password";
            return $password;
        }

        $password = \getenv('MAPBENDER_ROOT_PASSWORD')
            ?: $_ENV['MAPBENDER_ROOT_PASSWORD']
            ?? $_SERVER['MAPBENDER_ROOT_PASSWORD']
            ?? null;

        if ($password) {
            $this->passwordNotice = "Password set to value of MB_ROOT_PASSWORD env variable";
            return $password;
        }

        if (!$input->getParameterOption('--no-interaction')) {
            /** @var QuestionHelper $questionHelper */
            $questionHelper = $this->getHelper('question');
            $question = new Question('Enter the password to use for the root account: ', null);
            return $questionHelper->ask($input, $output, $question);
        }

        return null;
    }

    private static function randomStr(
        $length,
        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-.*+_'
    )
    {
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');
        $root = $this->getRoot();

        if (!$input->getOption('username')) {
            $default = $root ? $root->getUserIdentifier() : 'root';
            $question = new Question("Enter the username to use for the root account [{$default}]: ", $default);
            $input->setOption('username', $questionHelper->ask($input, $output, $question));
        }
        if (!$input->getOption('email')) {
            $default = $root ? $root->getEmail() : '';
            $question = new Question("Enter the e-mail adress to use for the root account [{$default}]: ", $default);
            $input->setOption('email', $questionHelper->ask($input, $output, $question));
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
