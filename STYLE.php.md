# PHP code style

This style guide is an opinionated remix of [Zend Framework 3 coding standards](https://github.com/zendframework/zendframework/wiki/Coding-Standards)
and [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md).

## Goals

Code should be produced from the onset to be readable, discoverable, maintainable, reusable and extensible.
On top of that, the rule set should achieve minimal diffs for any change or extension to the code.
This helps us reviewing change sets and avoiding expensive merge conflicts.

## Scope
Obviously, when contributing _to_ an external framework directly, completely different
rules may apply. Mere usage and working on top of a framework however should not lead
to adoption of that framework's conventions as they come into conflict with our
own.

This guide is relevant to all PHP code produced and contributed to our public github
group and internal Mapbender-related developments. This guide is not relevant for code to be merged directly
into a third-party codebase where different rules govern.

This document is _not_ a call to action to reformat all existing code.

## Conventions 

In accordance to [RFC 2119](http://www.ietf.org/rfc/rfc2119.txt): 

* **MUST** and **MUST NOT** indicate non-optional requirements 
* **SHOULD** and **SHOULD NOT** indicate recommendations for which exceptions may exist 
* **MAY** indicates truly optional requirements 

# Code formatting
Binary operators and ternary sub-operators are separated from operands by one space on both sides with only two exceptions: the class member access operators `->` and `::`
* assignment is a binary operator, too
* Unlike some other standards, the `.` string concatenation operator gets spaces, too

Unary operators are not separated from their operand
* reference taking `&`, and by extension array append `$someArray[]`, are unary operators, too

Comma and semicolon either end the line or are followed by a single space in all circumstances.

Place no padding space inside round or square brackets on either side, in any construct.

Function and method incovations do not place a space before or after the argument list brackets.

## Control flow
All control flow bodies **MUST** be blocks demarcated by curly braces, even / in particular if they only contain a single statement.

The opening curly brace goes on the same line as the control flow operator, preceded by one space.

The closing curly brace of a block starts a new line.

Control flow continuation keywords are placed on the same line as the closing curly brace of the preceding block.
These are:
* `else`, `elseif` (preferred), `else if` (discouraged)
* `while` in a do-while loop (i.e. after loop body)
* `catch`, `finally`

In a `switch`/`case`, you **MUST** include a `default:`, and every `case` must either `break` _or_ include
an explicit <code>//&nbsp;fall&nbsp;through</code> (preferred) or
<code>//&nbsp;no break</code> ([PSR-2 suggestion](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md#52-switch-case))
comment to indicate that the omission of the `break` was fully intentional.

## Strings
Constant string literals **SHOULD** use single quotes but you also **SHOULD** use double quotes if it reduces
escaping (i.e. whenever you need single quotes inside the string).

Inline expansions in double-quoted strings **MUST** use the curly-braces form `{$variable}`. Rationale: "naked" form only works when followed with a space.

You **SHOULD** prefer inline expansions of variables over concatenation for readability. [Concatenation is harder to read and leads to
mistakes](https://github.com/mapbender/mapbender/pull/767/commits/c8ec5cf86e519c30deaa79aff1ced1867a6d6795#diff-4ae206501a11cf3c043da972fe92afb6L334).

```php
$descriptor = "mess";
# bad
echo "Your code is a $descriptor despite all those fine rules!";
# error (see?)
echo "Your code is $descriptory!";
# workaround (acceptable)
echo 'Your code is ' . $descriptor . 'y but I made it work!';
# good
echo "Your code is still {$descriptor}y, let's improve it together!";
```

Long concatenated string literals spanning multiple lines **MUST** put the concatenation operator(s) before the continuing string, **MUST** place the final semicolon on a new line, and **SHOULD** horizontally align the concatenation operator the assignment operator.

A leading space following the concatenation is always preferred over a trailing space before concatenation.

Rationale: these rules minify the diffs for adding / removing lines. Leading spaces allow you to comment (or remove entirely) a phrase without redistributing the space to the previous line.
The dangling semicolon allows you to remove the last line without redistributing the semicolon.

## Arrays
Unlike flow keywords, there is no space between `array` and the opening round bracket. Think of `array`
as in invoking a factory method.

Multi-line arrays **MUST** have a "dangling" comma after the last element. [This is perfectly legal in PHP](https://stackoverflow.com/a/2829598/9377827) and avoids reformatting of adjacent lines when adding / removing. For the same reason, the closing round bracket goes on a new line.
```php
$shoppingList = array(
    'oranges',
    'cheese',
    'walnuts',
);
```

Non-empty array definitions **SHOULD** be multi-line, even for a single element. Rationale: no more reformatting needed if it's growing later.

Nested arrays are simply indented one more level, and otherwise retain all formatting rules.
```php
$gridState = array(
    array(
        'x',
        null,
        'o',
    ),
    array(
        'o',
        'x',
        'o',
    ),
    array(
        null,
        null,
        'x',    # dangling comma after last element (2nd level)
    ),          # dangling comma after last element (top level)
);
```

## The picture that is worth more than a thousand words

```php
# array initialization
$buckets = array(
    # prefer single quote for constant string
    'even' => array(),
    'uneven' => array(),    # dangling comma after last element
);

# control flow spacing, operator spacing, semicolon spacing
for ($i = 0; $i < $max; ++$i) {
    $square = $i * $i;
    # control flow must start a block even for single statement
    if ($i >= 2 && isPrime($square)) {
        // single quote inside string => prefer double quotes over escaping
        throw new LogicException("I can't believe my luck");
    }
    # ternary operator formatting: same as binary
    $bucketKey = ($i & 1) ? 'uneven' : 'even';
    # square brackets do not incur spaces, only binary+ operators do
    $buckets[$bucketKey][] = $i;
}

# general string + multiline string formatting
# * single quote if possible
# * inline variable expansion preferred over concatenation
# * space preferred on next line after concatenation (+ demonstration why)
# * lone semicolon after multiline string
$longStory = 'Hello I would like to inform you that I have just inspected all integers'
           . " below {$max}, and I have found that none of these numbers has a square"
           . ' that is prime.'
//           . ' Is anyone surprised?'  # disabled for now, CR complained it was too snotty
;

if ($max <= 1) {
    # double quotes used to avoid escaping
    $message = "I didn't actually do anything.";
} else {
    $message = $longStory;
}

# method / function invocation format, member access operators
$voyager = $this->getNearestHangar()->retrieveCraft('voyager', 'here', 'now');
$this->engravingService->engraveText($voyager, $message, EngravingService::DEPTH_SEVERE);

# try / catch formatting, rethrow same exception
try {
    $voyager->launch();
} catch (LaunchException $e) {
    $manufacturer = $voyager->getMetaData()->getManufacturer();
    $manufacturer->notifyProductDefect($voyager, $e);
    throw $e;
}

# do-while formatting
do {
    sleep(1);
    $missionState = $voyager->getState();
} while ($missionState && $missionState === Mission::STATE_IN_PROGRESS);

$displayState = Mission::stateCodeToDisplayable($missionState);
echo "The mission ended with status {$displayState}\n";
```

## Lambdas
Lambda definitions are not function invocations. Format the head of a lambda definition like you would
for control flow, with spaces before and after the keywords, and the opening curly brace on the same line: 
```php
$isStrictMode = true;
$squareRooter = function ($x) use ($isStrictMode) {
    if ($x < 0) {
        if ($isStrictMode) {
            throw new GlobalMassPanicException("You can't do that!");
        } else {
            return null;
        }
    } else {
        return sqrt($x);
    }
};
```

## Formatting of embedded multiline constructs
Is hilariously complex to define, and thus the embedding is simply forbidden.

Lambda definitions **MUST NOT** be inlined into a function argument list, array initalization, or any expression
except direct variable assignment. Store your defined lambda in a variable, then work with that variable.

Likewise, non-empty array definitions **MUST NOT** be inlined into any construct except
* direct assignment to a variable
* another array definition (i.e. you're defining a sub-level of a nested array)
* function calls when followed by another argument
  * you **SHOULD NOT** inline the array, even as the last argument, if the function you call would accept optional arguments
    after your array

```php
# bad
$fidget->spin(array(
    'around',
    'and around',
    'and around again'), Fidget::SPEED_VERY_HIGH);

# good
$fidgetInstructions = array(
    'around',
    'and around',
    'and around again',
);
$fidget->spin($fidgetInstructions, Fidget::SPEED_VERY_HIGH);
```

An invocation where the last (or only) argument is an inlined array definition is acceptable, and formatted like this:
```php
# acceptable
$yodaPhraseParts = array_reverse(array(
    'I do not',
    'particularly',
    'like this',
));
# acceptable
$keyLists = array_map('array_keys', array(
    'locals' => $locals,
    'globals' => $globals,
    'universals' => $universals,
));
```

## Advanced weirdness

You **MUST NOT** let pass-by-reference create a local variable for you. You **MUST** introduce every variable you reference explicitly.

```php
// bad
function worksMagicallyEvenInEmptyLocalScope() {
    // $matches undefined but magically created because it's taken by reference
    preg_match('#cat#', 'baguette', $matches);
    return $matches;    // it may actually exist now, seriously
}

// yes
$matches = array();
preg_match('#hat#', 'chat', $matches);
```

You **MUST NOT** rely on [PHP's assumptions about you wanting to promote invalid syntax to a string](https://stackoverflow.com/questions/2941169/what-does-the-php-error-message-notice-use-of-undefined-constant-mean).
Work with notices **on**, always.

You **SHOULD NOT** ever use [list](http://php.net/manual/en/function.list.php). The _WARNING_ content in
the official documentation outgrowing the actual description of how it works should suffice to scare you off.

## Neatness

You **SHOULD NOT** horizontally align values for a map-style array initialization, and you **SHOULD NOT**
align the `=` in a block of variable assignments either.

Consider:
```php
$colorMap = array(
            # so neat, everything starts here
    'red'   => '#ff0000',
    'green' => '#00ff00',
    'blue'  => '#0000ff',
);
```
One commit later:
```php
$colorMap = array(
            # so neat, everything starts here
    'red'   => '#ff0000', 
    'green' => '#00ff00',
    'blue'  => '#0000ff',
                # oops!
    'turquoise' => '#00ccff',
             # oops!
    'salmon' => '#cc9999',
);
```

Applying any such "group indentation" is highly discouraged. Introducing it to existing
code [creates noise in commits](https://github.com/mapbender/mapbender/commit/21dc369b7257144a57535de1135d23ec49638e5f#diff-0d202367ea7022053c0c70b1145e7c2eL45)
which obfuscate the actual changes.
And then it's going to get broken again anyway. A single longer variable name or array key is all it takes.

Code is not static. Code changes over time. Your neatly made alignment will not survive changes.

### Call chaining
Applying "this chaining" is similarly discouraged. It creates the same commit artifacts as the lack of dangling comma on arrays, in that [one extra line will have to change](https://github.com/mapbender/mapbender/commit/6ee09db1fb3f130e4e5a9fa6791e6b452fa57c60#diff-f8e36759387779e5e86f87b143ed97e9L355).
It will also falsely motivate you to [hoist complex initializations far away from their usage](https://github.com/mapbender/mapbender/blob/6ee09db1fb3f130e4e5a9fa6791e6b452fa57c60/src/Mapbender/WmsBundle/Component/WmsInstanceEntityHandler.php#L324).

You cannot this-chain without holding a reference to the object in the first place. And that reference will
still be good afterwards, you can keep using it. The calls will even be perfectly aligned visually.
```php
# how is this any worse ...
$book->setTitle('A tale of pointers and chases');
$book->setPriceNet(19.99);

# ... than this?
$book->setTitle('A tale of pointers and chases')
    ->setPriceNet(19.99);

# ... or even this?
$book
    ->setTitle('A tale of pointers and chases')
    ->setPriceNet(19.99)
;
```

`$this` is generally the least useful value you can return from a method call. A method with no return
value can later be extended to provide some information. A method returning `$this` on the other hand is
forever bound to give the caller the one piece of information it already was guaranteed to have.

You **SHOULD NOT** introduce a constant `return $this` into methods unless required by framework
integration (e.g. setters on entity classes managed by Doctrine ORM).

We maintain that call chaining only ever adds value when [it _doesn't_ invariantly return the same object](https://github.com/mapbender/mapbender/blob/6ee09db1fb3f130e4e5a9fa6791e6b452fa57c60/src/Mapbender/CoreBundle/DependencyInjection/Configuration.php#L23).

### Shorthand array syntax
[At the end of an eight-year war of attrition where it has been argued how much a PHP hashmap could benefit from
resembling non-hashmap types in other languages, a vote was passed and all sanity was lost, forever](https://wiki.php.net/rfc/shortsyntaxforarrays).

Apparently it makes [PHP's list](http://php.net/manual/en/function.list.php) even better. You **SHOULD NOT** use `list`,
ever, by the way. 

We do not encourage this, at all. Five characters saved. What is there even left to say.

# PHP file formatting and naming 

Any and all files containing PHP code
* **MUST** use 4-space indentation. Tab characters **MUST NOT** be present anywhere in the file. 
* **MUST** use Unix line endings (LF only; CR is not acceptable, neither alone nor in combination with LF).
* **MUST** use the long-form `<?php` opening tag. No short form tags, including the short-form echo tag (`<?=`), are acceptable.
* **MUST** be stored in UTF-8 encoding without BOM.
* **MUST NOT** end with a closing `?>` tag, ignoring whitespace. Closing tags are completely optional to the php interpreter and are little more than a source of hard-to-debug "Headers already sent" errors.

The line length **SHOULD** be at most 80 characters and **MUST** be at most 120 characters.

## Pure PHP files (not templates) 

These are the majority of files you will ever deal with. They start in PHP mode and never drop out of PHP mode.
These
* **MUST** use the `.php` extension.
* **MUST** use a purely alphanumeric name before the extension. Whitespace, underscores and additional dots in the filename are prohibited. Numerics are acceptable but discouraged.
* **MUST NOT** include any whitespace before the opening `<?php` tag.
* **SHOULD** end with a linefeed.

## Mixed output / php files (templates)

You may encounter systems that use PHP directly as a templating format, which results in a mix of direct-to-output HTML markup, or other content, with PHP logic within the same file.

Such templates
* **MAY** be named arbitrarily, as suggested by the conventions of the templating engine.
* **MAY** omit the trailing linefeed character, unless they end with PHP code.
* **MAY** exceed line length limits.
* **MAY** use otherwise inacceptable control flow forms (`endif` et al)

# Classes and namespaces 

We use [PSR-4 autoloading](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md).

Namespace components, classes, interfaces and traits **MUST** be named in StudlyCaps and consist of only alphanumeric characters.
* the file name must be exactly the name of the defined class (or interface / trait) plus extension `.php`
* abbreviations **SHOULD** be treated the same as words (i.e. we prefer "PdfWriter" over "PDFWriter")
* names **MUST NOT** contain underscores. We do not treat underscores specially, but some legacy autoloaders and analysis tools may still interpret them as namespace separators under now outdated conventions.
* every component of the namespace **MUST** correspond directly to an equally named (=same case, too) directory.
  * when authoring a composer package you **MAY** omit leading parts of the namespace from the directory structure
    but if you do so, you also **MUST** [declare your namespace anchor directory](https://github.com/mapbender/mapbender-digitizer/blob/release/1.2/composer.json#L25).
    Do not rely on the embedding outer project to "fix" your namespace base directory for you.

A PHP file defining a class **MUST NOT** cause side effects.
* no PHP statements outside the class definition
* no function and / or variable definitions outside the class definition

A PHP file **MUST NOT** define more than one class, trait or interface.

## Class definitions
```php
<?php
namespace BreakfastBundle\Component;

use FoodBundle\Component\Food;
use FoodBundle\Component\GlutenAwareInterface;
use HouseholdBundle\Component\Tools\Cutlery;
use HouseholdBundle\Component\Tools\Fallback\Hands;
use HouseholdBundle\Component\Tools\CutleryLocatorInterface;
use HouseholdBundle\Component\Tools\SpoonableInterface;
use HouseholdBundle\Component\Tools\Spoon;
use StatesOfMatterBundle\Component\PourableInterface;
use StatesOfMatterBundle\Component\ShakableInterface;
use StatesOfMatterBundle\Component\ShakableTrait;

/**
 * Food specialization for cereals.
 */
abstract class Cereal extends Food implements GlutenAwareInterface, PourableInterface, ShakableInterface
{
    use ShakableTrait;

    /** @var CerealNutritionInformation */
    protected $nutritionInformation;
    
    /** @var float in [0;1] */
    protected $mothDensity;
    
    public function __construct()
    {
        $this->mothDensity = $this->calculateMothDensity();
    }
    
    /**
     * @return Cutlery|Hands
     */ 
    public function suggestSuitableTool()
    {
        /** @var CutleryLocatorInterface $cutleryLocator */
        $cutleryLocator = $this->container->get('app.cutlery.locator.service');
        return $cutleryLocator->findNearestSpoon();
    }

    /**
     * @return float in [0;1] as a proportion of mass
     */
    public function getGlutenDensity()
    {
        $meta = $this->getMetaData();
        return $meta->getGlutenDensity();
    }
    
    /**
     * Cereals are generally stable when stored dry and dark and sealed. But sometimes bad things happen,
     * and a good stir can upset the moths, motivating them to settle somewhere else.
     * This is a self stir, not to be confused with Bowl::stir()!
     */
    public function stir()
    {
        foreach (range(10) as $i) {
            $this->shakeUp();
            $this->shakeDown();
        }
        $this->mothDensity = $this->calculateMothDensity();
    }
    
    /**
     * @param BowlInterface $bowl which will receive
     * @param float|null $volume in cubic meters; null (default) to autodetect available space 
     */
    public function pour(BowlInterface $targetBowl, $volume = null)
    {
        if (is_null($volume)) {
            $volume = $targetBowl->getFreeVolume();
        }
        $targetBowl->receive($this, $volume);
    }
    
    /**
     * Should calculate the current ratio of moths to cereal, by volume, in [0;1]. 0 for no moths.
     * @return float
     */
    abstract protected function calculateMothDensity();
}

// file ends with no closing tag
```

Noteworthy here:
* you **MUST** leave an empty line after the namespace declaration
* you **MUST** leave an empty line after the (block of) `use` statement(s) (if any)
* opening and closing curly braces for classes and methods **MUST** be alone on a separate line
* `extends` first, `implements` second
  * all `extends` and `implements` **MUST** be completely on the same line as the `class` keyword itself, even there's (like here) a whole bunch of them
* `use` for any trait(s), if present at all, **MUST** be the first section inside the class definition, followed by a blank line
* method and property visibility **MUST** be declared
* for both properties and methods `abstract` and `final` go first, visibility next, `static` last (then the `function` or variable)
* Property, parameter and return types are annotated
  * prose trailing the `@return` annotation is legal and your IDE will evaluate and display it
  * we do **not** annotate `@return void`
* Text documentation for methods is happily omitted, unless their function is particularly obscure
* the file just ends; there is no closing tag

## Use statement aliases

If a class name does not clash with another within the same file scope you **MUST NOT** use an alias.
Even with a clash, you **SHOULD** prefer a qualified name over a `use` alias. Remember that use statements are entirely optional both to PHP itself and to a modern IDE's completion / introspection.

Class `use` aliases **SHOULD** be built by concatenating trailing namespace elements until the conflict is resolved.
E.g.
```php
// alias name = class name . final namespace component
use Mapbender\CoreBundle\Entity\Element as ElementEntity;
use Mapbender\CoreBundle\Component\Element as ElementComponent;
```

## Abstract classes

Abstract classes follow the same conventions as regular classes, with no further considerations for placement or naming.

Specifically, abstract classes **SHOULD NOT** bear "Abstract" in the name (as suffix or prefix) or in their namespace. Rationale: they may not remain abstract, and then the naming will cause confusion, which will motivate a commit renaming the class and moving the entire file, which will then cause merge conflicts. Avoid this from the start.

## Naming of methods, properties and variables 

Methods, properties and variables **MUST** be named in camelCase and be purely alphanumeric. Underscores are prohibited.
Numbers are permitted but discouraged.

Some versions of php may ignore the case of properties and classes. This behaviour is not to be relied upon. Symbol references **MUST** use the exact same case as the declaration.

All properties **MUST** be declared. Automatically created properties are not acceptable.
* By extension, any use of stdClass is discouraged and will only be tolerated in legacy code.

## Interfaces and traits

Authoring of interfaces should be avoided whenever an abstract base class would be at all possible.
Rationale: implementing class declarations will have to change whenever you decide to switch
your interface to an abstract class ("implements" becomes "extends"). If you author
your interface as an abstract class from the start, no such change will be necessary and the scope
of your change will be lower.

Extracting (a subset of) abstract methods from an abstract class into an interface later is a much
less invasive process than the opposite motion. Your old abstract class can stay in place as is. You
can declare it to implement your new interface with no further change, even without removing its
own, compatible abstract method declarations. None of the inheriting classes need to be touched.

Interfaces are generally required for skirting around language limitations such as
* [abstract static declaration not allowed in PHP 5](https://stackoverflow.com/a/6386309/9377827)
* [the officially sanctioned multiple-inheritance hack using interface + trait](https://wiki.php.net/rfc/traits-with-interfaces)

Interface names **MUST** end with "Interface".

Traits are only acceptable if they fulfill an interface. A trait **MUST NOT** be used for any other purpose. There is at most a 1:1 correspondance between trait and interface, where the trait remains optional.

Since the only acceptable use of traits is fulfilling interfaces, we arrive at the following rules:
* the trait **MUST** be in the same path and namespace as the corresponding interface
* the trait **MUST** be named equally, only swapping the "Interface" suffix for a "Trait" suffix
* the trait **MUST** implement **ALL** of its corresponding interface's methods. This means:
  * If you have multiple interfaces to fulfill, you **MUST** either use multiple traits or declare a trivial merged interface.
  * A trait that expects the class it gets embedded in to fulfill some of the interface methods, is forbidden.
* a trait **MAY** introduce additional properties (because an interface cannot)
* a trait **SHOULD NOT** introduce methods beyond the corresponding interface definition

It is, of course, acceptable to define a class, abstract or otherwise, which `implements` one or more interfaces without
a corresponding trait, _in_ _addition_ _to_ trait-saturated interfaces.  

# Docblocks and annotations
Type annotations on properties and local variables **SHOULD** use the single line form for brevity,
unless annotations drive logic (e.g. classes used as Doctrine ORM entities).
```php
/** @var Element $newInstance */
$newInstance = new $className();
```

Method and class docblocks on the other hand **MUST** use the multi-line form if present at all.

A doc block **SHOULD NOT** repeat the name of its associated property or method. A repetition adds nothing of value for neither
the human reader nor the IDE or other automated tools. We prefer no documentation over zero-value documentation.

```php
# zero-value doc block
/**
 * Description of class ObscureItemWrapper
 */
class ObscureItemWrapper
{
    # zero-value doc block
    /**
     * Obscure thing
     */
    protected $obscureThing;

    # zero-value doc block
    /**
     * Perform obscure operation
     */
    public function performObscureOperation()
    {
        $this->obscureThing->operate();
    }
}
```

It is entirely acceptable to document only the parameter and return types of methods and omit the text altogether.
Types documentation is by far more important than the text, especially if your methods are already named clearly.

The need for long prose on the method usually means it has some counter-intutive behavior, which should be avoided
by properly segmenting concerns in newly added code. Weird quirks for backwards compatibility should always be
documented, to inform the reader of the code why the behavior is in place.

Entirely empty docblocks are prohibited. You **SHOULD** document your code, but if you have nothing to say, omit
the container for that nothing. Rationale: diffs that add lines generally integrate better than diffs that
change existing lines.

# Reformatting existing code

... should not be taken lightly.

We are aware that much of our existing code is presently in violation of these guidelines.
We value a smooth reviewing-and-merging process more than global adherence. This means we may willingly leave
portions of the code in a non-conformant state until we determine a good opportunity has come to change it.
This will generally happen in the form of a "style overhaul" commit or branch that expressly
makes no change to function, but only to format and documentation.

When working on a functional change, any line that is getting modified anyway **SHOULD** be brought
in line with the styling conventions. Restyling entire files, or portions of files outside of the necessary
functional change on the other hand is generally frowned upon. Bundling of gratuitous style changes with
desirable functional changes makes the changeset difficult to review and increases the likelihood
of merge conflicts.

Styling changes should be separate from functional commits and the commit message should
be prefixed with `[NFC]` (="no functional change"). 

Doc block amendments, likewise, should go into commits separate from the functional change, and also
be prefixed with `[NFC]`.

We make no demands on the ordering of NFC and functional commits within a branch. You definitely can
make multiple separate `[NFC]` commits and intersperse them freely with your logic commits.

# Your IDE and you

Never let your IDE automatically reformat a file that already exists in the repository. Definitely do not
turn on "reformat on save" or similar options.

Turn off "trim trailing whitespace on save". The maximum acceptable setting is "only edited lines". Systems
using only .editorconfig generally cannot model this behavior, requiring you to turn off auto-trimming entirely.

Do not battle with the repository over a trailing linefeed at the end of a file. Ending with a linefeed is a
**SHOULD**-level rule. Your IDE has an "ensure line feed at file end on Save" option which you **MAY** disable
when in doubt.

# Deviations from PSR-2 / Zend
Zend 3 in particular goes to great lengths in definining how to inline arrays and lambda definitions into
method and function calls. We absolutely do not. Lambda definitions are never allowed to be inlined into
invocations (or trigraphs), and arrays are only allowed in the trivial case where nothing follows them.
Local variable assignments are cheap and easy to read. They prevent all this complexity.

Zend 3 specifies that in a lambda definition the `function` keyword is followed directly by the
parameter list with no space, while `use` is spaced on both sides. This is inconsistent. We require the same
spacing around both.

Both define how to break class `extends` and `implements` sections over mutliple lines. We do not. We
require all on the same line as the class. **NOTE**: this may bring you into conflict with line length
limits. In those cases, we may require you to break up your class hierarchy along the way so that all
declarations fit. This will generally be a good thing.

