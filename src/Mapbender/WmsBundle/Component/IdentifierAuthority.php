<?php
namespace Mapbender\WmsBundle\Component;

/**
 * IdentifierAuthority class. An instance of the class IdentifierAuthority
 * conbines an Identifier with an Authority of a WmsLayerSource.
 *
 * @author Paul Schmidt
 */
class IdentifierAuthority
{

    /**
     * Identifier
     *
     * @var Identifier
     */
    protected $identifier;

    /**
     * Authority
     *
     * @var Authority
     */
    protected $authority;

    /**
     * Set authority
     *
     * @param Authority $authority
     * @return IdentifierAuthority
     */
    public function setAuthority(Authority $authority)
    {
        $this->authority = $authority;
        return $this;
    }

    /**
     * Get authority
     *
     * @return Authority
     */
    public function getAuthority()
    {
        return $this->authority;
    }

    /**
     * Set identifier
     *
     * @param Identifier $identifier
     * @return IdentifierAuthority
     */
    public function setIdentifier(Identifier $identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * Get identifier
     *
     * @return Identifier
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }
}
