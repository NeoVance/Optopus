Optopus
=======

PHP library for defining and handling command line options for a PHP command line script.

### Project Goals:
* Handle every type of option syntax intuitively and within reason, based on common GNU/Linux CLI utilities
* Dead Simple to define options and their properties
* Provide baked in help and 'smart help'
* Only does options.  Should not try to add color or other CLI fanciness.
* Allow public methods to override magic / helpers when necessary

### Why another PHP CLI Options Parser?
While ConsoleKit and others are awesome, they don't seem to address only options parsing, and handle every type of option common to CLI scripts, like keeping track of option count for verbosity levels, and allowing optional option arguments.

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
  ->requiresArgument()
  ->description('Baz.  Set description here.  This option requires an additional argument.');

$options->add('--verbose')
  ->alias('-v')
  ->description('Verbosity.  You can amplify by adding again ie: -vvv or -v -v or --verbose --verbose');
  
$options->parse();

print_r($options);
```

`parse()` must be called *after* all option creation.

### Public Option Building Methods

This library allows for a lot of flexibility in end-user options usage.  The one thing it does not allow is an option to not have a preceeding "-".  Those are considered script arguments or option arguments (if an option that allows arguments preceeds it).  This is to maintain uniformity, based on (most) common GNU/Linux CLI utilities.

Long options have two dashes and short options have one.  See the clustering section for details on short option clustering (which is of course allowed).

As seen in the example above, there exists method chaining for creating new options.  They are:
* `add('option')`  - option can be shortopt or longopt, and specifying the dashes here is optional.

  For example:
```

  add('f')
  
  add(-f')
  
  add('--foo')
  
  add('foo')

```

* `alias('alias')`  - another name for the option.  You can make the option shortopt, and the alias longopt, vice-versa, or whatever you want.  It's invocation will be equivalent to calling the parent option.

* `acceptsArgument()` - This is a rare.  You probably want `requiresArgument()`.  `acceptsArgument()` is for cases where you want the option to *optionally* accept an argument.  For an example, see GNU's `cp` option `--reflink`.  It accepts an argument, but if not given, acts as a 'flag'.  This library handles this properly if you need this type of option for your script.

* `requiresArgument()` - The option requires an argument.  It can be given in various common syntaxes.  See below 'Option Arguments' section for examples.

* `description($desc_string)` - Set the option description.  This is used in the baked in help.  This should be a brief description.  See public method `help()` also if you wish to override this and make your own help page.

* `help($help_string)` - Override the baked in help and set the output string for the help page if options are given incorrectly, or otherwise fail the criteria you've set.

* `title($title_string)` - Override the title.  This is only used in the first line of the help page, and defaults to the actual invoked script name ( `$argv[0]` ).

* `incompatibleWith($options)` - $options can be a single option or an array of options.  If an more than one option is given bu end user that are incompatible with eachother, a message will be generated indicating they are incompatible, along with the standard help page.

### Public 'Getters'

These methods are available for you to see what was selected, how many times it was selected, what arguments were provided, what option arguments were provided, etc.  They are what you will use in your script to determine what to do, based on the user-supplied options.

**NOTE:** Some magic is involved here via PHP's magic method `__call()`.  This is only so that you don't have to remember the exact method names to retreive information about the options and what was selected.  For example, `$options->selected()`, `$options->getSelected()`, `$options->sElecTED()` all call `$options->getSelected()`.  See the `__call()` function for more information on what's allowed.

* `get($option = null)` - returns an array of options selected or an array of just the option given.

* `all()` - Not sure how useful externally, but it produces an array of all available options and aliases.

* `getSelected()` - Returns an array of all selected options.

* `getCount($option)` - returns an integer of how many times an option has been selected.

* `getOptArg($option = null)` - returns an array of option arguments indexed by the option name

### Option Arguments:

Option Arguments come in two flavors:
* Optional Option Arguments
* Required Option Arguments

Optional Option arguments are rare, but they are used in some CLI utilities.  This means that an option `accceptsArgument()` but does not `requiresArgument()`.  So if `--foo` only `acceptsArgument()` then it could be used like:

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
  ->description('Verbosity.  You can amplify by adding again ie: -vvv or -v -v or --verbose --verbose');
```

The `count` will be incremented for any option selected in the returned options Object.


### End of Options and Help Page
Supports `--` for end-of-options GNU pseudo-standard

`--help` will default to a help page generated from the public `description()` method.  As of now this can be overriden by calling public `help()` method, ie - `$options->help($string)` where `$string` is a full help page string.  This is only useful if you want to add a lot of custom help information for various options.


### Try it:

If you'd like to give it a test run, here's how.  It comes with a script that you can play around with.  Do the following:

`git clone git@github.com:kevinquinnyo/Optopus.git`

`cd Optopus/`

`composer install`  (for test suite inclusion and to set PSR-4 namespace)

`cd src`

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


