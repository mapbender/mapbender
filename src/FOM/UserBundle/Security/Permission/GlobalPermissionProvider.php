<?php

namespace FOM\UserBundle\Security\Permission;

interface GlobalPermissionProvider
{
    /**
     * returns all permission categories for this provider
     * @return array keys: unique string aliases for the category. values: translation keys with a human-readable value
     * @example ["digitizer" => "mb.digitizer.permission.category"]
     */
    public function getCategories(): array;

    /**
     * returns all permissions for this provider
     * @return array keys: unique string aliases for the permission. values: array with the keys `group` (string alias for
     * the category, see self::getCategories), `cssClass` (optional): see AbstractResourceDomain::getCssClassForAction, `label`: translation label,
     * `help` (optional): translation label for help text
     * @example ["digitizer.create" => [
     *     "category": "digitizer",
     *     "cssClass": AbstractResourceDomain::CSS_CLASS_WARNING,
     *     "label": "mb.digitizer.permission.create",
     *     "help": "mb.digitizer.permission.create_help",
     * ]]
     */
    public function getPermissions(): array;
}
