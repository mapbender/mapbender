<?php


namespace Mapbender\CoreBundle\Command;


use FOM\UserBundle\Entity\User;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserListCommand extends AbstractUserCommand
{
    protected function configure()
    {
        $this->setHelp('List all users stored in the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var User[] $users */
        $users = $this->getRepository()->findBy(array(), array(
            'username' => 'ASC',
        ));
        foreach ($users as $user) {
            $sinceDt = $user->getRegistrationTime();
            if ($sinceDt) {
                /** @var \DateTime $sinceDt */
                $since = ' since ' . $sinceDt->format('Y-m-d H:m:i');
            } else {
                $since = '';
            }
            $output->writeln("User #{$user->getId()} name: " . print_r($user->getUsername(), true) . $since);
        }
    }
}
