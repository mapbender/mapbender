<?php


namespace Mapbender\CoreBundle\Command;


use Doctrine\ORM\EntityManagerInterface;
use Mapbender\Component\Transformer\BaseUrlTransformer;
use Mapbender\Component\Transformer\ChangeTrackingTransformer;
use Mapbender\Component\Transformer\OneWayTransformer;
use Mapbender\Component\Transformer\Target\MutableUrlTarget;
use Mapbender\CoreBundle\Entity\Source;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class CreateTranslationTwigCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('mapbender:translationtwig');
        $this->setDescription("Creates redundant but necessary .json.twig file for providing translations");
        $this->addArgument('file_name', InputArgument::REQUIRED);

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument('file_name');
        if (!is_file($file)) {
            throw new \Exception("Argument does not specify valid path to file");
        }


        $content = file_get_contents($file);
        $yaml = Yaml::parse($content);
        $this->flatten($yaml);
        $arr = array();
        $str = "{\n";
        foreach($yaml as $key => $value) {
            $arr[] = "   \"{$key}\": \"{{ \"{$key}\"|trans }}\"";
        }
        $str .= implode(",\n",$arr)."\n";
        $str .= "}\n";
        echo $str;
    }



    private function flatten(array &$messages, array $subnode = null, $path = null)
    {
        if (null === $subnode) {
            $subnode = &$messages;
        }
        foreach ($subnode as $key => $value) {
            if (\is_array($value)) {
                $nodePath = $path ? $path.'.'.$key : $key;
                $this->flatten($messages, $value, $nodePath);
                if (null === $path) {
                    unset($messages[$key]);
                }
            } elseif (null !== $path) {
                $messages[$path.'.'.$key] = $value;
            }
        }
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        return $em;
    }
}

