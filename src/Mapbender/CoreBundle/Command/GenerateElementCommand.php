<?php

namespace Mapbender\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenerateElementCommand
 *
 * @deprecated should be removed in release/3.0.7
 */
class GenerateElementCommand extends ContainerAwareCommand {
    private $generator;

    protected function getGenerator() {
        if($this->generator === null) {
            $this->generator = new ElementGenerator();
        }
        return $this->generator;
    }
    protected function configure() {
        $this->setDefinition(array(
                new InputArgument('bundle', InputArgument::REQUIRED, 'The bundle namespace of the Element to create'),
                new InputArgument('classname', InputArgument::REQUIRED, 'The classname of the Element to create'),
                new InputArgument('dir', InputArgument::REQUIRED, 'The directory where to find the bundle'),
                new InputOption('type', '', InputOption::VALUE_REQUIRED, 'Type of Element to create (general, map-click, map-box)', 'general')
            ))
            ->setHelp(<<<EOT
The <info>mapbender:generate:element</info> command generates a new Mapbender element with a basic skeleton.

<info>./app/console/ mapbender:generate:element "Vendor\HelloBundle" MyElement src </info>

The generated Element class will be Vendor\HelloBundle\Element\MyElement.
EOT
            )
            ->setName('mapbender:generate:element')
            ->setDescription('Generates a Mapbender element');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $bundleNamespace = $input->getArgument('bundle');
        $className = $input->getArgument('classname');
        $type = $input->getOption('type');

        if(!in_array($type, array(
            'general',
            'map-click',
            'map-box'))) {
            throw new \RuntimeException(sprintf('The element type "%s" is not supported.', $type));
        }

        //TODO: Type validation
        //TODO: Does this work?
        if (preg_match('[^A-Za-z0-9]', $className)) {
            throw new \InvalidArgumentException('The classname contains invalid characters.');
        }
        // validate namespace
        $bundleNamespace = strtr($bundleNamespace, '/', '\\');
        if (preg_match('/[^A-Za-z0-9_\\\-]/', $bundleNamespace)) {
            throw new \InvalidArgumentException('The bundle namespace contains invalid characters.');
        }

        // validate that the namespace is at least one level deep
        if (false === strpos($bundleNamespace, '\\')) {
            $msg = array();
            $msg[] = sprintf('The namespace must contain a vendor namespace (e.g. "VendorName\%s" instead of simply "%s").', $bundleNamespace, $bundleNamespace);
            $msg[] = 'If you\'ve specified a vendor namespace, did you forget to surround it with quotes (mapbender:generate:element "Acme\BlogBundle")?';

            throw new \InvalidArgumentException(implode("\n\n", $msg));
        }

        $dir = $input->getArgument('dir');

        // add trailing / if necessary
        $dir = '/' === substr($dir, -1, 1) ? $dir : $dir.'/';
        $bundleDir = $dir.strtr($bundleNamespace, '\\', '/');
        $bundle = strtr($bundleNamespace, array('\\' => ''));

        if (!file_exists($bundleDir)) {
            throw new \RuntimeException(sprintf('Bundle directory "%s" does not exist.', $bundleDir));
        }

        $files = $this->getGenerator()->create($this->getContainer(),
            $bundle, $bundleDir, $bundleNamespace, $className, $type);

        $output->writeln('<comment>Summary of actions</comment>');
        $output->writeln(sprintf('- Your element %s\Element\%s has been created.', $bundle, $className));
        $output->writeln('- The following files have been created:');
        foreach($files as $k => $v) {
            $output->writeLn(sprintf('  - %s (%s)', $k, $v));
        }
        $output->writeln('');
        $output->writeln('<comment>Follow up actions</comment>');
        $output->writeln('Read about adapting your bare-bone element at <info>http://doc.mapbender3.org/en/book/development/element_generate.html</info>');
    }
}

