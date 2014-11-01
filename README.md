Optopus
=======

PHP library for defining and handling command line options for a PHP command line script.

### Project Goals:
* Handle every type of option syntax intuitively and within reason, based on common GNU/Linux CLI utilities
* Dead Simple to define options and their properties
* Provide baked in help and 'smart help'
* Only does options.  Should not try to add color or other CLI fanciness.
* Allow public methods to override magic / helpers when necessary

### Usage:

```php
$options = new Optopus\Options;

$options->add('--foo')
  ->alias('-f')
  ->description('Foo. Set description here.');
  
$options->add('--bar')
  ->alias('-b')
  ->acceptsArgument()
  ->description('Bar.  Set description here.  This option allows an optional argument.');
  
$options->add('--baz')
  ->alias('-z')
  ->acceptsArgument('required')
  ->description('Baz.  Set description here.  This option requires an additional argument.');

$options->add('--verbose')
  ->alias('-v')
  ->repeats()
  ->description('Verbosity.  You can amplify by adding again ie: -vvv or -v -v or --verbose --verbose');
  
$options->parse();

print_r($options);
```

### Option Arguments:

Option Arguments come in two flavors:
* Optional Option Arguments
* Required Option Arguments

Optional Option arguments are rare, but they are used in many CLI utilities.  This means that an option `accceptsArgument()` but does not `requiresArgument()`.  So if `--foo` only `acceptsArgument()` then it could be used like:

`./script --foo`

`./script --foo Bar`

`./script --foo=Bar`

`./script --fooBar`

### Clutered Short Options:

It will also allow 'clustered' short options ie: `-asdf` is equal to `-a -s -d -f`.  In addition, option arguments can be provided with 'clustered' short options, for example, if `-f` `acceptsArgument()` :

`./script -asdf=bar`

`./script -asdf bar`

`./script -asdfBar`

Note in the latter case, if `-B` was a valid option, then it would be parsed as such.

Also note that if any option in a cluster `requiresArgument()` anything after it will be treated as it's option argument, so for example if -r `requiresArgument()` :

`./script -abcrdefFoo`

Will produce an arg of `defFoo` for `-r` (regardless of whether anything after is a valid option)

`./script -rabc`

Will produce an arg of `abc` for `-r`

`./script -abcr=Foo`

Will produce an arg of `Foo` for `-r`

### Repeating Options
Useful mostly for debug / verbosity options, for example:
```php
$options->add('--verbose')
  ->alias('-v')
  ->repeats()
  ->description('Verbosity.  You can amplify by adding again ie: -vvv or -v -v or --verbose --verbose');
```

The `repeat_count` will be set for the option in the returned options Object.


### End of Options and Help Page
Supports `--` for end-of-options GNU pseudo-standard

`--help` will default to a help page generated from the public `description()` method.  As of now this can be overriden by calling public `help()` method, ie - `$options->help($string)` where `$string` is a full help page string.  This is only useful if you want to add a lot of custom help information for various options.


Demo script:

If you'd like to give it a test, do the following:

`git clone git@github.com:kevinquinnyo/Optopus.git`

`cd Optopus/src`

`composer install`  (for test suite inclusion and to set PSR-4 namespace)

`chmod u+x script`

It handles arguments in a way that you would expect.  For instance, try the following 'silly' usage:

`./script ARG0 -v -vv --foo=bar ARG1 -f=Foo -b Bar ARG2 --baz Baz --quux=Quux -xyzw Waldo ARG3 -xyzc=Corge -v --verbose -vv --verbose`

You can also try:

`./script --help`

or:

`./script --help [option]`

It also has 'smart help' like `git`.  If you misspell an option it will suggest the closest match.

### Script Arguments
Script arguments are anything that passed through the option filters.  They are kept track of in the `arguments` property and are indexed in the order in which they were received.

To see all usage, the tests are helpful too. @todo tests not passing yet since refactor


