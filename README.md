Optopus
=======

Yet another PHP CLI options parser.  Strives to be simple, robust, user-friendly, and adhere to GNU standards

Usage:

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

Option Arguments can be called with the following syntaxes:

`./script --bar foo`

`./script -b foo`

`./script --bar=foo`

`./script -b=foo`

It will also allow 'clustered' short options ie: `-asdf` is equal to `-a -s -d -f`.  In addition, option arguments can be provided with 'clustered' short options, ie:

`./script -asdf=bar`

`./script -asdf bar`


This will also work:

`./script --barFOO` no spaces.


Supports "--" for end-of-options GNU pseudo-standard

`--help` will default to a help page generated from the `description()` method.  As of now this can be overriden by calling public method `helpOverride`, ie - `$options->helpOverride($string)` where `$string` is a full help page string.  This is only useful if you want to add a lot of custom help information for various options.


Demo script:

If you'd like to give it a test, do the following:

`git clone git@github.com:kevinquinnyo/Optopus.git`

`cd Optopus/src`

`chmod u+x script`

It handles arguments in a way that you would expect.  For instance, try the following silly usage:

`./script ARG0 -v -vv --foo=bar ARG1 -f=Foo -b Bar ARG2 --baz Baz --quux=Quux -xyzw Waldo ARG3 -xyzc=Corge -v --verbose -vv --verbose`

You can also try:

`./script --help`

To see all usage, the tests are helpful too. @todo tests not passing yet since refactor


