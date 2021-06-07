<?php

namespace FOM\UserBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class FOMUserExtension extends Extension {
    public function load(array $configs, ContainerBuilder $container) {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        if (!$config['profile_entity']) {
            $config['profile_formtype'] = null;
            $config['profile_template'] = null;
        }

        $container->setParameter("fom_user.selfregister", $config["selfregister"]);
        $container->setParameter("fom_user.reset_password", $config["reset_password"]);
        $container->setParameter("fom_user.max_registration_time", intval($config["max_registration_time"]));
        $container->setParameter("fom_user.max_reset_time", intval($config["max_reset_time"]));
        $container->setParameter("fom_user.mail_from_name", $config["mail_from_name"]);
        $container->setParameter("fom_user.mail_from_address", $config["mail_from_address"]);

        $container->setParameter("fom_user.profile_entity", $config["profile_entity"]);
        $container->setParameter("fom_user.profile_formtype", $config["profile_formtype"]);
        $container->setParameter("fom_user.profile_template", $config["profile_template"]);

        $container->setParameter("fom_user.self_registration_groups", $config["self_registration_groups"]);
        $container->setParameter("fom_user.user_own_permissions", $config["user_own_permissions"]);

        $container->setParameter("fom_user.login_check_log_time", $config["login_check_log_time"]);
        $container->setParameter("fom_user.login_attempts_before_delay", $config["login_attempts_before_delay"]);
        $container->setParameter("fom_user.login_delay_after_fail", $config["login_delay_after_fail"]);
    }

    public function getAlias() {
        return 'fom_user';
    }
}
