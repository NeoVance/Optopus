Optopus
=======

Yet another PHP CLI options parser.  Strives to be simple, robust, user-friendly, and adhere to GNU standards

Usage:

```
$options = new Optopus\Options;

$options->add('--foo')
  ->alias('-f)
  ->description('Foo. Set description here.');
  
$options->add(--bar)
  ->alias(-b)
  ->acceptsArgument('required')
  ->description('Bar.  Set description here.  This option requires an additional argument.');
  
$options->add('--verbose')
  ->alias('-v')
  ->repeats()
  ->description('Verbosity.  You can amplify by adding again ie: -vvv or -v -v or --verbose --verbose');
  
$options->register();

print_r($options);
```

Option Arguments can be called with the following syntaxes:

`./script --bar foo`

`./script -b foo`

`./script --bar=foo`

`./script -b=foo`

It will also allow 'clustered' short options ie: `-asdf` is equal to `-a -s -d -f`


Currently not accepted:

`./script -xyzb=foo` assuming -b acceptsArgument()  This will not work as expected

`./script --barFOO` no spaces. assuming --bar acceptsArgument()  This will not work as expected


I'm not sure if those bizarre end-user cases *should* be supported, but they aren't as of now.

Supports "--" for end-of-options GNU pseudo-standard

`--help`, `-h`, `-?` will default to a help page generated from the `description()` method of options.


