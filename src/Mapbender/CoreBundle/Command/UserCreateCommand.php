<?php


namespace Mapbender\CoreBundle\Command;


use FOM\UserBundle\Entity\User;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UserCreateCommand extends AbstractUserCommand
{
    protected function configure()
    {
        $this->setName('mapbender:user:create');
        $this->setHelp('Create a new local user, or optionally (with --update) modify an existing user');
        $this->addArgument('name', InputArgument::REQUIRED);
        $this->addOption('update', null, InputOption::VALUE_NONE, 'Allow update of existing user');
        $this->addOption('password', null, InputOption::VALUE_REQUIRED);
        $this->addOption('email', null, InputOption::VALUE_REQUIRED);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        if (!$input->getArgument('name')) {
            throw new \InvalidArgumentException("Argument 'name' cannot be empty");
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('name');
        /** @var User|null $user */
        $user = $this->getRepository()->findOneBy(array(
            'username' => $username,
        ));
        $em = $this->getEntityManager();
        if (!$user) {
            $user = new User();
            $em->persist($user);
            $user->setUsername($username);
            try {
                $this->initializeUser($user, $input);
            } catch (\RuntimeException $e) {
                $output->writeln("Error: {$e->getMessage()}");
                return 1;
            }
            $output->writeln("User " . print_r($username, true) . " created");
        } else {
            if (!$input->getOption('update')) {
                $output->writeln("Error: user " . print_r($username, true) . " already exists; use --update to modify");
                return 1;
            }
            $em->persist($user);
            $modifications = $this->updateUser($user, $input);
            if ($modifications) {
                $output->writeln("User " . print_r($username, true) . " updated");
            } else {
                $output->writeln("Warning: no modifications made to user " . print_r($username, true));
                return 0;
            }
        }
        $em->flush();
        return 0;
    }

    protected function updateUser(User $user, InputInterface $input)
    {
        $mods = 0;
        if ($input->getOption('email')) {
            $user->setEmail($input->getOption('email'));
            ++$mods;
        }
        if ($input->getOption('password')) {
            $this->getUserHelper()->setPassword($user, $input->getOption('password'));
            ++$mods;
        }
        return $mods;
    }

    protected function initializeUser(User $user, InputInterface $input)
    {
        if (!$input->getOption('password')) {
            throw new \RuntimeException("Option --password is required for new user");
        }
        if (!$input->getOption('email')) {
            throw new \RuntimeException("Option --email is required for new user");
        }
        $user->setRegistrationTime(new \DateTime());
        $this->updateUser($user, $input);
        // must flush to generate autoincrement id before assigning ACLs
        $em = $this->getEntityManager();
        $em->flush();
        $em->persist($user);
        // Add default privileges (VIEW and EDIT on own information)
        $this->getUserHelper()->giveOwnRights($user);
    }
}
