<?php


namespace Mapbender\CoreBundle\Component\Source;


class CustomParameter
{
    public $name;
    public $default;
    public $hidden = false;

    public function __unserialize(array $array)
    {
        foreach (['name', 'default', 'hidden'] as $key) {
            if (array_key_exists($key, $array)) $this->$key = $array[$key];
        }
    }

    /**
     * Set name
     *
     * @param string $value
     */
    public function setName($value): void
    {
        $this->name = $value;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param string $value
     */
    public function setDefault($value): void
    {
        $this->default = $value;
    }

    /**
     * @return bool
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * @param $hidden
     */
    public function setHidden($hidden): void
    {
        $this->hidden = $hidden;
    }
}
