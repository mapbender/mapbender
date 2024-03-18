# Controllers

This pages gives a quick overview of the controllers used in a Mapbender application.

## The Front Controller - Using Routes

In Symfony, each HTTP request goes through the front end controller (app.php in the web directory) which determines the controller function to pass it to.

The mapping from request path to controller function is basically done in the configuration, where the routing.yml defines these mappings - called routes - or imports their definitions from bundles (or other files).

To get an overview off all defined routes by using the console command.

```console
 cd mapbender/application
 app/console router:debug

 [router] Current routes
 Name                                        Method Pattern
 _assetic_30d3bc4                            ANY    /css/30d3bc4.css
 _assetic_30d3bc4_0                          ANY    /css/30d3bc4_part_1_base_1.css
 _wdt                                        ANY    /_wdt/{token}
 _profiler_search                            ANY    /_profiler/search
 _profiler_purge                             ANY    /_profiler/purge
 _profiler_import                            ANY    /_profiler/import
 _profiler_export                            ANY    /_profiler/export/{token}.txt
 _profiler_search_results                    ANY    /_profiler/{token}/search/results
 _profiler                                   ANY    /_profiler/{token}
 _configurator_home                          ANY    /_configurator/
 _configurator_step                          ANY    /_configurator/step/{index}
 _configurator_final                         ANY    /_configurator/final
 mapbender_manager_layer_index               GET    /manager/layers/{page}
 mapbender_manager_group_index               GET    /manager/group/{page}
 mapbender_manager_repository_index          GET    /manager/repository/{page}
 mapbender_manager_application_index2        GET    /manager/application
 mapbender_manager_application_index         GET    /manager/applications/{page}
 mapbender_manager_application_new           GET    /manager/application/new
 mapbender_manager_application_create        POST   /manager/application
 mapbender_manager_application_view          ANY    /manager/application/{id}
 [ and so on... ]
```

The command lists all routes with their names, allowed methods and URL pattern. To get more information about a particular route, give its name to the command:

```console
 app/console router:debug mapbender_core_user_login

 [router] Route "mapbender_core_user_login"
 Name         mapbender_core_user_login
 Pattern      /user/login
 Class        Symfony\Component\Routing\CompiledRoute
 Defaults     _controller: Mapbender\CoreBundle\Controller\UserController::loginAction
 Requirements
 Options      compiler_class: Symfony\Component\Routing\RouteCompiler
 Regex        #^
                 /user/login
             $#x
```

To learn more about routing, read the [Symfony documentation](https://symfony.com/doc/current/book/index.html).

### Defining routes using annotations

In Mapbender, we use decentralized route definitions: Instead of writing each and every route in the routing.yml, we import their definition from the controller classes in the activated bundles. This has the advantage of having the definition with the controller function. This should usually be fine and can be - if need arises - easily overwritten by adapting the routing.yml.
Using the Symfony with the SE bundles like Mapbender does, routes can therefore be written using annotation comments for each controller function. You can read about the annotation syntax over at the [Symfony documentation](https://symfony.com/doc/current/book/index.html).

## Mapbender Controllers

A Mapbender installation uses a particular set of controller classes and functions. This chapter will give a short list of these, so you can inspect them more easily.

### Frontend

The frontend is basically the application view of your Mapbender installation. This includes the application list and each application view. It's what your users will interact with. Each application is routed to the ApplicationController class of the CoreBundle:

```console
 /application/{slug} => Mapbender\CoreBundle\Controller\ApplicationController->applicationAction($slug)
```

Elements of an application can provide Ajax endpoints for their client side widgets. These are routed as follows:

```console
 /application/{slug}/element/{id}/{action} => Mapbender\CoreBundle\Controller\ApplicationController->elementAction($slug, $id, $action)
```

> [!NOTE]
> This controller calls the *httpAction* method if the element class and passes the $action parameter and returns the response given by that function. So for the real magic for element Ajax behaviour take a look at the httpAction method of the elements.

### Backend

The backend is where you configure your Mapbender installation. It is handled by the ManagerBundle and allows your admins to manage settings for users, roles, services, applications and elements.

For each section an own controller class exists within this bundle:

* **ApplicationController**: Manage applications
* **GroupController**: Manage user groups
* **LayerController**: Manage layers
* **RepositoryController**: Manage the layer repository
* **SettingsController**: Manage common settings
* **UserController**: Manage users
* **ManagerController**: Provides common functionality for the other controllers

Each of the these controllers (right now work is going on within the ApplicationController) is a good example of what we think of as good kinda RESTful URLs.

[↑ Back to top](#controllers)

[← Back to README](../README.md)
