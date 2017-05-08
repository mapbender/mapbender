<?php
namespace Mapbender\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenerateTemplateCommand
 *
 * @deprecated should be removed in release/3.0.7
 */
class GenerateTemplateCommand extends ContainerAwareCommand {

    /** @var  TemplateGenerator */
    protected $generator;

    /**
     * @return TemplateGenerator
     */
    protected function getGenerator() {
        if($this->generator === null) {
            $this->generator = new TemplateGenerator();
        }
        return $this->generator;
    }
    protected function configure() {
        $this->setDefinition(array(
                new InputArgument('bundle', InputArgument::REQUIRED, 'The bundle namespace of the Template to create'),
                new InputArgument('classname', InputArgument::REQUIRED, 'The classname of the Template to create'),
                new InputArgument('dir', InputArgument::REQUIRED, 'The directory where to find the bundle'),
            ))
            ->setHelp(<<<EOT
The <info>mapbender:generate:template</info> command generates a new Mapbender application template.

<info>./app/console/ mapbender:generate:template "Vendor\HelloBundle" MyTemplate src </info>

The generated Element class will be Vendor\HelloBundle\Template\MyElement.
EOT
            )
            ->setName('mapbender:generate:template')
            ->setDescription('Generates a Mapbender application template');
    }

    /**
     * Execute command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bundleNamespace = $input->getArgument('bundle');
        $className       = $input->getArgument('classname');
        $type            = $input->getOption('type');

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
            $bundle, $bundleDir, $bundleNamespace, $className);

        $output->writeln('<comment>Summary of actions</comment>');
        $output->writeln(sprintf('- Your element %s\Template\%s has been created.', $bundle, $className));
        $output->writeln('- The following files have been created:');
        foreach($files as $k => $v) {
            $output->writeLn(sprintf('  - %s (%s)', $k, $v));
        }
        $output->writeln('');
        $output->writeln('<comment>Follow up actions</comment>');
        $output->writeln('Read about adapting your bare-bone element at <info>http://mapbender.org/3/cookbook/template-from-skeleton</info>');
    }
}

