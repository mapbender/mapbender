# Permission System

Since Mapbender 4 a new permission was introduced to replaced the overly complex and deprecated ACL bundle from Symfony.
Most of the code is located at `/src/FOM/UserBundle/Security/Permission`

## Definitions

| Definition          | Explanation                                                                                                                                                                                                                      | 
|---------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------| 
| __Subject__         | The subject defines who a permission is granted to, like a specific user, a group of users or the general public. The subject can be qualified by the _subject domain_ and (optionally) an identifier like the user or group id. | 
| __Subject domain__  | A subject domain describes a class of subjects to whom permissions may be granted. In the core Mapbender, four subject domains are defined: Users, Groups, public access and all registered users                                |  
| __Resource__        | The resource describes to what area of the website a permission is granted, like a specific application. It can be qualified by the _resource domain_ and (optionally) an identifier like the application id.                    | 
| __Resource domain__ | A resource domain describes a class of objects where subjects can get access to. In the core Mapbender, three resource domains are defined: Installation (global access), applications and elements                              |  
| __Action__          | An action or right that can be performed on an object, like `view` or `edit`. The available actions depend on the resource domain.                                                                                               |  
| __Permission__      | A permission is the assignment that grants a specific subject (e.g. the user `Max`) the right to perform a specific action (e.g. `view`) on a specific resource (e.g. the application `map`)                                     |  

[↑ Back to top](#security)

[← Back to README](../README.md)
