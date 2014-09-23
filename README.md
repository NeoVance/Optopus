Optopus
=======

Yet another PHP CLI options parser.  Strives to be simple, robust, user-friendly, and adhere to GNU standards

Usage:

```
$options = new Optopus\Options;

$options->add('--foo')
  ->alias('-f);
  
$options->add(--bar)
  ->alias(-b)
  ->acceptsArgument('required');
  
$options->register();

print_r($options);

