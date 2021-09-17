- symfony/http-foundation >=2.4 for [`request_stack` service](https://github.com/symfony/http-foundation/blob/master/CHANGELOG.md#240)  
  Implicitly provided by symfony/symfony >=2.4.  

- symfony/console >= 2.5 for [`QuestionHelper`](https://github.com/symfony/console/blob/master/CHANGELOG.md#250)  
  Implicitly provided by symfony/symfony >=2.5.

- symfony/security-acl ^2.4 || ^3 for `security.acl.provider` et al.  
  Implicitly provided by symfony/symfony ^2.4, but no longer implicitly provided by symfony/symfony >= 3.

- symfony/form ^2.4 || ^3  
  Implicitly provided by symfony/symfony >= 2.4  
  Incompatible with symfony/form >= 4 (must remove `choices_as_values` and form aliases)
