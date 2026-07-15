<?php

namespace Mapbender\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author Paul Schmidt
 */
#[ORM\Entity]
#[ORM\Table(name: 'mb_core_regionproperties')]
class RegionProperties
{

    /**
     * @var integer $id
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * @var Application The configuration entity for the application
     */
    #[ORM\ManyToOne(targetEntity: Application::class, inversedBy: 'regionProperties')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    protected $application;

    /**
     * @var string $title The element title
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 128)]
    protected $name;

    /**
     * @var array $properties The region properties
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: 'json', nullable: true)]
    protected $properties;

    public function __construct()
    {
        $this->properties = [];
    }

    public function setId(string|int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string|int
    {
        return $this->id;
    }

    public function setName($name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setApplication(Application $application): static
    {
        $this->application = $application;

        return $this;
    }

    public function getApplication(): Application
    {
        return $this->application;
    }

    public function setProperties(array $properties = []): static
    {
        $this->properties = $properties === null || !is_array($properties) ? [] : $properties;

        return $this;
    }

    public function getProperties(): array
    {
        $properties = $this->properties;
        return $this->convertLegacyBooleanFlagsToResponsiveOptions($properties, ['generate_button_menu', 'closed']);
    }

    private function convertLegacyBooleanFlagsToResponsiveOptions(array $properties, array $legacyParameters): array
    {
        foreach ($legacyParameters as $param) {
            if (array_key_exists($param, $properties)) {
                $value = $properties[$param];
                if ($value === true || $value === 1 || $value === '1') {
                    $properties[$param] = 'yes';
                } elseif (!in_array($value, ['no', 'yes', 'only_mobile', 'only_desktop'])) {
                    $properties[$param] = 'no';
                }
            }
        }
        return $properties;
    }

    public static function responsiveOptions(): array
    {
        return [
            'mb.manager.responsive_options.no' => 'no',
            'mb.manager.responsive_options.only_desktop' => 'only_desktop',
            'mb.manager.responsive_options.only_mobile' => 'only_mobile',
            'mb.manager.responsive_options.yes' => 'yes',
        ];
    }
}
