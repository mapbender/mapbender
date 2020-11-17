# Extension configuration
FOM UserBundle evaluates the extension configuration node `fom_user`.
The defaults are:
```yaml
fom_user:
    mail_from_address: ~
    mail_from_name: ~

    # Self-service password reset
    reset_password: true
    ## Minimum time (in hours) between resets
    max_reset_time: 24

    # Public account registration
    selfregister: false
    ## Maximum time (in hours) before registration token expires
    max_registration_time: 24
    ## Titles of groups self-registered users will be assigned to
    self_registration_groups: []

    # User metadata customization
    ## PHP class name of user profile entity; use false for no profile
    profile_entity: FOM\UserBundle\Entity\BasicProfile
    ## PHP class name of user profile form type
    profile_formtype: FOM\UserBundle\Form\Type\BasicProfileType
    ## Twig resource path to user profile template; obsolete. Any form
    ## type will render properly with just the default template.
    profile_template: FOMUserBundle:User:basic_profile.html.twig

    # Artificial login delay after repeated failed attempts
    ## Login delay (in seconds) for repeated failed attempts
    login_delay_after_fail: 2
    ## Allowed login failures without adding delays
    login_attempts_before_delay: 3
    ## Time window for remembering past login failurs (PHP DateTimeInterval format)
    login_check_log_time: "-5 minutes"
```

A non-empty `mail_from_adress` is a prerequisite for sending system mails. Password reset and public
registration both use the `mail_from_adress`. If `mail_from_adress` is empty, these features cannot
be activated and the associated controller routes will emit `HTTP 404 - Not Found` errors.

Groups referenced by `self_registration_groups` will _not_ be added to the system automatically.
Nonexisting groups will be skipped, producing only a log message. If you want the assignments to work,
you will need to add the groups to the system backend first.

# Privilege assignments
Access privileges can be assigned to concrete objects or globally to an entire class of objects.
There are a number of parameters to control _who_ these privileges can be assigned _to_:

`fom.acl_assignment.show_users` (boolean; default true), if true, offers individual user accounts
when assigning privileges.

`fom.acl_assignment.show_groups` (boolean; default true), if true, offers user groups
when assigning privileges.

`fom.acl_assignment.show_authenticated` (boolean; default true), if true, offers the
pseudo-group of all logged-in users when assigning privileges (NOTE that this is independent
of the `show_groups`)

`fom.acl_assignment.show_anonymous` (boolean; default false), if true, offers the
pseudo-group of effectively everyone, including guest visitors with no account,
when assigning privileges (NOTE that this is independent of the `show_groups`).
This is a legacy option. Assigning privileges to effectively everyone should never sensibly
be required.

## Preexisting assignments
Note that all of the above options only control who is offered for new assingments of privileges. Existing,
stored privilege assignments are not affected in any way, and will still be shown, still take effect, and can
of course still be modified or deleted.

# LDAP parameters
Certain access privileges can be assigned not only to users maintained in the local database,
but also to LDAP users.

`ldap_host` (string or null; default empty) sets the host name or ip of the LDAP server.
If this value is empty, none of the following parameters take any effect.

`ldap_port` (integer or null; default empty) sets a non-default LDAP server port number.
If empty, the protocol standard port 389 will be used.

`ldap_version` (integer; default 3) sets the protocol version.

`ldap_bind_dn` (string or null; default null) is the dn of the LDAP user with
sufficient privileges to load other users' information. You can only leave
this empty if your LDAP server allows an anonymous user to query arbitrary user objects.

`ldap_bind_pwd` (string or null; default null) is the password complementing `ldap_bind_dn`.

`ldap_user_base_dn` (string or null; default null) is the root [dn](https://ldap.com/ldap-dns-and-rdns/) of the user directory.

`ldap_user_filter` (string or null; default null) is an optional [LDAP filter expression](https://ldap.com/ldap-filters/)
to apply to the user query. This lets you, among other things, restrict the
objectclass, e.g. by using `(|(objectclass=user)(objectclass=person))`.

`ldap_user_name_attribute` (string; default "cn") lets you specify the primary identifying
attribute of the retrieved LDAP user objects.
