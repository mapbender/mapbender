<?php

namespace Mapbender\CoreBundle\Command;

use Mapbender\CoreBundle\Entity\Role;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper;

/**
 * Initialize role tree in database
 *
 * @author Christian Wygoda
 */
class InitRolesCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setDescription('Initialize the roles tree in the database')
            ->setHelp(<<<EOT
The <info>mapbender:initroles</info> command initializes the role tree in the
database by inserting the AUTHENTICATED_USER role which is a parent role for
all other roles which are created later on.
EOT
            )
            ->setName('mapbender:initroles');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        //TODO: Drop all roles, but ask first!

        $em = $this->getContainer()->get('doctrine')->getEntityManager();

        $rootRole = $em->getRepository('MapbenderCoreBundle:Role')
            ->findOneByOverride('IS_AUTHENTICATED_FULLY');

        if($rootRole !== null) {
            $output->writeln(array(
                '',
                'The IS_AUTHENTICATED_FULLY role is already in the database.',
                'Nothing to do. Good bye.',
                ''));

            return;
        }

        $rootRole = new Role();
        $rootRole
            ->setTitle('Authenticated User')
            ->setDescription('')
            ->setOverride('IS_AUTHENTICATED_FULLY')
            ->setMpttLeft(1)
            ->setMpttRight(1);

        $em->persist($rootRole);
        $em->flush();

        $output->writeln(array(
            '',
            'The role system is now initialized.',
            ''));
    }
}

