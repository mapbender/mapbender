<?php

namespace FOM\UserBundle\Security\Permission;

interface YamlDefinedPermissionEntity
{

    /**
     * @return string[]|null
     */
    public function getYamlRoles(): ?array;

    /**
     * @param string[]|null $yamlRoles
     */
    public function setYamlRoles(?array $yamlRoles): void;

}
