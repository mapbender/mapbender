# Elements

## Understanding elements

Elements are the building blocks of every application. Each element provides some functionality and can interact with other elements. The map element is probably the element you face most of the time as it provides all map viewing capabilities. 

This documentation page describes element functionality in detail. For a quick start guide to override an existing element, refer to [this guide](./overriding.md)

Each element consists of four parts itself:

* PHP class: Describes the element with its capabilities and also can provide an Ajax callback point, so that the client-side widget can execute database queries display the result.
* JavaScript (JQueryUI) Widget: this is the client side part of an element. It is everything you do and interact with on your screen. Using Ajax, it can call its server-side counterpart to do things like database queries.
* Twig Template (optional): HTML the element is using. In the most basic version, this would just be a DIV, but it can be as complex as is needed. (see [below](#twig-template))
* CSS: Most elements want some style, so they may provide their own.

## PHP class

The PHP class should be declared in your bundle's `Element` namespace and extend `Mapbender\Component\Element\AbstractElementService`. 

The PHP class should be tagged with `mapbender.element`. This will automatically register the element using a Symfony compiler pass. The easiest way is using the attribute AutoconfigureTag:

```php
use Mapbender\Component\Element\AbstractElementService;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('mapbender.element')]
class Legend extends AbstractElementService {}
```

If you don't use Autowiring, instead of adding the AutoconfigureTag attribute, follow [this guide](../architecture/bundles-without-autoconfiguration.md#registering-an-element)


The following methods must be overridden in the PHP class:
- `getClassTitle(): string`: Returns the title of the element that will be shown in the backend's "Add element" dialog. Can be a translation string
- `getClassDescription(): string`: Returns the description of the element that will be shown in the backend's "Add element" dialog. Can be a translation string
- `getRequiredAssets(Element $element): array`: Returns all assets this element uses. Should return an array with the keys `js`, `css` and/or `trans` each containing an array of paths to the resource files
- `getDefaultConfiguration(): array`: Returns the default configuration. Can contain arbitrary keys and values.
- `getType(): string`: Returns the FQCN of the Symfony form Type that is used for configuring the element in the backend (see [below](#php-form-type))
- `getFormType(): string`: Returns the path to the twig template that renderes the admin configuration form (see [below](#twig-admin-template))
- `getWidgetName(): string`: The jQueryUI widget's name (see [below](#javascript-widgets))
- `getView(Element $element): ElementView`: The element's view, either static or a twig template (see [below](#rendering-the-view))

You can also implement the class `Mapbender\CoreBundle\Component\ElementBase\ValidatableConfigurationInterface` and override the `validate` method. Validate the configuration here. The method is called in two cases:
- when saving a form in the administration backend. The `$form` attribute will be non-null. You should create a form error in this case, e.g. `$form->get('configuration')->get('mykey')->addError(new FormError('Something went wrong'));` 
- when accessing an application in the frontend. In this case, the `$form` argument will be null. Throw a `Mapbender\CoreBundle\Component\ElementBase\ValidationFailedException` if a validation error occurs. Caution: This message will be shown to frontend users.


## PHP Form type
A form type is used to configure the element in the backend. By convention, it's placed in your bundle's `Element/Type` namespace and should extend from `Symfony\Component\Form\AbstractType`. 

In `buildForm` construct your form using [Symfony form types](https://symfony.com/doc/current/reference/forms/types.html). A simple example:

```php
<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class RulerAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('type', ChoiceType::class, [
            'required' => true,
            'label' => 'mb.core.ruler.admin.type',
            'choices' => [
                "mb.core.ruler.tag.line" => "line",
                "mb.core.ruler.tag.area" => "area",
                "mb.core.ruler.tag.both" => "both",
            ],
        ]);
    }
}
```

If you want to show an inline help text, you can use the MapbenderTypeTrait. However, you need to inject the translation component for using this feature (will be auto-injected when using Autowiring):

```php
<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Element\Type\MapbenderTypeTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RulerAdminType extends AbstractType
{
    use MapbenderTypeTrait;

    public function __construct(private readonly TranslatorInterface $trans)
    {
    }

    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('help', TextType::class, $this->createInlineHelpText([
            'required' => false,
            'label' => 'mb.core.ruler.admin.help',
            'help' => 'mb.core.ruler.admin.help_help',
        ], $this->trans));
    }
}
```

## Twig Admin Template
The admin template defines how the form configured [above](#php-form-type) should be rendered. It is a twig template and usually saved in `YourBundle/Resources/views/ElementAdmin`. In the easiest form it just renders the form:

```html
form(form)
```

You can also render form_rows manually and add custom content (see [Symfony Docs](https://symfony.com/doc/current/form/form_customization.html#reference-form-twig-functions)).


## JavaScript Widgets

Elements are build using native Javascript classes. This ensures a common pattern for element development and offers

* default options
* constructors and (optional) destructors
* private and public methods

The basic skeleton looks like this:

```javascript
    (function() {

    // This is an example element class. It will create an element class "MbMyClass" as well as an
    // "MbMyClass" object in the "Mapbender" namespace. Be sure
    // to use the "Mb" prefix for your element name to keep naming conventions.
    class MbMyClass extends MapbenderElement {
        // Constructor, gets called on element initialization.
        constructor(configuration, $element) {
            super(configuration, $element);
            
            // This attribute is private for your widget.
            this.var1 = null;
            // Do everything needed for set up here, for example event handling
            this.$element.bind('mbmyclassmagicdone', $.proxy(this._onMagicDone, this));
            this.$element.bind('click', $.proxy(this._clickCallback, this));
        }

        // Public function
        methodA(parameterA, parameterB) {
            this._methodB(parameterA);
        }

        // Private function, only callable from within this widget
        _methodB(parameterA) {
            alert('Called private function!');
        }

        _clickCallback(event) {
            const target = $(event.target);
            const id = target.attr('id');
            // ...
        }
    }
    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbMyClass = MbMyClass;
})();
```

Watch out for JavaScript's default behaviour to modify the `this` context when using events. Use lambdas or the `bind` function.

```javascript
this.$element.click(this._clickCallback.bind(this));
this.$element.click((e) => this._clickCallback(e));
```

### Predefined Methods
The following methods are called automatically by either the jQueryUI or Mapbender framework:

- `_create(): void`: Widget constructor
- `_setup(): void`: Not called automatically, but a mapbender convention for elements that require the map to be loaded before initialisation is the following pattern:

```js
_create: function () {
    Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => this._setup(mbMap));
},
_setup: function() {
    // actual initialisation
}
```
- `reveal(): void`: element in sidepane became visible
- `hide(): void`: element in sidepane became hidden (user switched to another element)
- `activate(?function callback): void`, alias: `open(?function callback): void`: element is triggered via the toolbar or a button. Call the callback function if the user closes the element (is used to update button state)
- `deactivate(): void`, alias: `close(): void`: element is closed via the toolbar or a button 
- `options.autoActivate`, `options.autoStart`, `options.autoOpen`, `options.auto_activate`: If any of these options exist and are set to true, a connected button will be shown as active on application load 

### Element to Element communication

There's an active and a passive way to communicate with another widget. The active way is to call a public method of the other widget. For that, you have to select the widget's HTML element with jQuery and call the method like this:

```javascript
// if you know the id
const otherElement = $('#element-13').mbMyClass('methodA', parameterA, parameterB);
// if you don't know the ids. Can match multiple elements if more than one instances exist!
const otherElements = $('.mb-element-myelement').mbMyClass('methodA', parameterA, parameterB);
```

The IDs are generated on the fly by Mapbender when the application is started. You can pass an element ID in the configuration as the target options for an Element. This will be replaced with the run-time ID of that target Elements' HTML element for you, so that in your widget code you can access the right ID as `this.options.target`.

```javascript
    $('#' + this.options.target).mbMyClass('methodA', parameterA, parameterB);

```

The passive way for communication is to subscribe to events of another target. You also need to know the HTML element,
but you can now listen for the other widget to call your widget. This is done using default jQuery events.


## Rendering the view

In the element's PHP file (specifically the `getView` function) you return the view that is rendered in the frontend. You have two options:

### Static View
For elements that don't need a complex UI, you can return a `Mapbender\Component\Element\StaticView`. Add the HTML content (if there is any) as a constructor argument and add the wrapper div attributes afterwards.

````php
public function getView(Element $element)
{
    $view = new StaticView('');
    $view->attributes['class'] = 'mb-element-legend';
    $view->attributes['data-title'] = $element->getTitle();
    return $view;
}
````

### Twig Template
If your element has a more complex UI, generate a `Mapbender\Component\Element\TemplateView` in the `getView` function. The constructor argument is the path to the twig template file. You can also set the wrapper div attributes, and in addition the variables that will be passed to the twig render function.


```php
public function getView(Element $element)
{
    $view = new TemplateView('@MyBundle/Element/my_element.html.twig');
    $view->attributes['class'] = 'mb-element-myelement';
    $view->attributes['data-title'] = $element->getTitle();

    $view->variables =[
        'some_variable' => ...,
    ];
    return $view;
}
```


[↑ Back to top](#elements)

[← Back to README](../README.md)
