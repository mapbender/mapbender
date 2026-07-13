<?php


namespace Mapbender\CoreBundle\Entity;


use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\CoreBundle\Component\Source\MutableHttpOriginInterface;
use Symfony\Component\HttpFoundation\Request;


#[ORM\MappedSuperclass]
abstract class HttpParsedSource extends Source
    implements MutableHttpOriginInterface
{
    /**
     * @var string|null
     */
    #[NotBlank]
    #[Url]
    #[ORM\Column(type: 'text', nullable: true)]
    protected $originUrl = "";

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', nullable: true)] // ;
    protected $username = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', nullable: true)] // ;
    protected $password = null;

    public function getOriginUrl(): string
    {
        return $this->originUrl;
    }

    public function setOriginUrl(string $originUrl): self
    {
        $this->originUrl = $originUrl;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string|null $password
     */
    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getDisplayUrl(): ?string
    {
        return $this->getOriginUrl();
    }

    /**
     * Should resolve to an non-tunneled internal URL for the given request, or null if no internal URL is available.
     */
    public function getInternalUrl(Request $request): ?string
    {
        return null;
    }
}
