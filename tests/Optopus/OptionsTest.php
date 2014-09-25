<?php

namespace Optopus\Test;

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Optopus\Options;

class OptionsTest extends \PHPUnit_Framework_TestCase {

	public function testAddOption() {

		$argv = [ 'script-name' ];
		$options = new Options($argv);

		$options->add('-x');
		$options->register();
		$this->assertNotFalse($options->get('-x'));
		
	}

	public function testSetTitle() {

		$argv = [ 'script-name' ];
		$options = new Options($argv);

		$options->title('foobar');
		$options->register();
		$this->assertEquals('foobar', $options->getTitle());

	}

	public function testGet() {

		$argv = [ 'script-name' ];
		$options = new Options($argv);
		
		$options->add('--xray')
			->alias('-x');

		// test option	
		$opt = $options->get('--xray');
		$this->assertNotFalse($opt);	
	
		// test alias	
		$opt = $options->get('-x');
		$this->assertNotFalse($opt);	

		// test get all
		$allOptions = $options->get();
		
		$this->assertArrayHasKey('--', $allOptions);	
		$this->assertArrayHasKey('--help', $allOptions);	
		$this->assertArrayHasKey('--xray', $allOptions);	

	}

	public function testAlias() {

		$argv = [ 'script-name' ];
		$options = new Options($argv);
		
		$options->add('--xray')
			->alias('-x')
			->alias('-X');

		$options->register();

		$opt = $options->get('--xray');	
		$this->assertEquals('-x', $opt['aliases'][0]);
		$this->assertEquals('-X', $opt['aliases'][1]);
		
	}	

	public function testRequired() {
		
		$argv = [ 'script-name' ];
		$options = new Options($argv);
		
		$options->add('--xray')
			->required();

		$options->register();

		$opt = $options->get('--xray');
		
		$this->assertArrayHasKey('required', $opt);
	}
	
	public function testAcceptsArgument() {
		
		$argv = [ 'script-name' ];
		$options = new Options($argv);
	
		// optional argument	
		$options->add('--opt-arg')
			->acceptsArgument();

		// required argument
		$options->add('--req-arg')
			->acceptsArgument('required');

		$options->register();
		$optarg = $options->get('--opt-arg');
		$reqarg = $options->get('--req-arg');

		$this->assertArrayHasKey('accepts_argument', $optarg);
		$this->assertArrayNotHasKey('requires_argument', $optarg);

		$this->assertArrayHasKey('accepts_argument', $reqarg);
		$this->assertArrayHasKey('requires_argument', $reqarg);

	}

	private function repeatsHelper($argv) {
		
		$options = new Options($argv);
	
		$options->add('--verbose')
			->alias('-v')
			->repeats();

		$options->register();
		return $options->get('--verbose');

	}
	
	public function testRepeats() {
	
		// test with no options supplied by user	
		$argv = [ 'script-name' ];
		$opt = $this->repeatsHelper($argv);

		$this->assertArrayHasKey('repeats', $opt);
		$this->assertArrayHasKey('repeat_count', $opt);
		$this->assertEquals(0, $opt['repeat_count']);

		
		// test with single shortopt	
		$argv = [ 'script-name' , '-v'];
		$opt = $this->repeatsHelper($argv);

		$this->assertArrayHasKey('repeats', $opt);
		$this->assertArrayHasKey('repeat_count', $opt);
		$this->assertEquals(1, $opt['repeat_count']);

		// test with multiple shortopt unclustered
		$argv = [ 'script-name' , '-v', '-v', '-v'];
		$opt = $this->repeatsHelper($argv);
	
		$this->assertArrayHasKey('repeats', $opt);
		$this->assertArrayHasKey('repeat_count', $opt);
		$this->assertEquals(3, $opt['repeat_count']);
		
		// test with multiple shortopt unclustered with others in between
		$argv = [ 'script-name', '-v', '-x', '-v', '-x', 'foo', '-v'];
		$opt = $this->repeatsHelper($argv);
	
		$this->assertArrayHasKey('repeats', $opt);
		$this->assertArrayHasKey('repeat_count', $opt);
		$this->assertEquals(3, $opt['repeat_count']);
		
		// test with multiple shortopt clustered
		$argv = [ 'script-name', '-vvv'];
		$opt = $this->repeatsHelper($argv);
	
		$this->assertArrayHasKey('repeats', $opt);
		$this->assertArrayHasKey('repeat_count', $opt);
		$this->assertEquals(3, $opt['repeat_count']);
		
		// test with multiple shortopt clustered with others
		$argv = [ 'script-name', '-x', '-vvxv', 'foo'];
		$opt = $this->repeatsHelper($argv);
	
		$this->assertArrayHasKey('repeats', $opt);
		$this->assertArrayHasKey('repeat_count', $opt);
		$this->assertEquals(3, $opt['repeat_count']);

		// test with multiple longopts
		$argv = [ 'script-name', '--verbose', '--verbose', '--verbose'];
		$opt = $this->repeatsHelper($argv);
	
		$this->assertArrayHasKey('repeats', $opt);
		$this->assertArrayHasKey('repeat_count', $opt);
		$this->assertEquals(3, $opt['repeat_count']);
		
		// test with multiple longopts with others in between
		$argv = [ 'script-name', '--verbose', '-xrq', '--xray', 'foo', '--verbose', '--verbose'];
		$opt = $this->repeatsHelper($argv);
	
		$this->assertArrayHasKey('repeats', $opt);
		$this->assertArrayHasKey('repeat_count', $opt);
		$this->assertEquals(3, $opt['repeat_count']);

		// test combination of shortopts, longopts, clustered, unclustered with others in between
		$argv = [ 'script-name', 'foo', '-xvvx', '-x', '--verbose', '-v', '-r', '--xray', '--verbose', '--foo', '-vvvv', 'bar'];
		$opt = $this->repeatsHelper($argv);
	
		$this->assertArrayHasKey('repeats', $opt);
		$this->assertArrayHasKey('repeat_count', $opt);
		$this->assertEquals(9, $opt['repeat_count']);

	}

	public function testDescription() {

		$argv = [ 'script-name' ];
		$options = new Options($argv);
	
		$options->add('--xray')
			->description('foobar');

		$options->register();

		$opt = $options->get('--xray');
		$this->assertArrayHasKey('description', $opt);
		$this->assertEquals('foobar', $opt['description']);

	}
	
	public function testGetAliasParent() {

		$argv = [ 'script-name' ];
		$options = new Options($argv);

		$options->add('--foo')
			->alias('-f');

		$options->register();
		$parent_option = $options->getAliasParent('-f');

		$this->assertEquals('--foo', $parent_option);
		
	}

	public function testOptionArguments() {

		// test getting all _option_arguments mixed styles and overrides including the silly usage
		$argv = [ 'script-name', '--foo=IGNOREME', '-f=Foo', '-b', 'Bar', '--baz', 'Baz', '--quux=Quux', '-xyzw', 'Waldo', 'asdfasdf', '-xyzc=Corge' ];
		$options = new Options($argv);

		$options->add('--foo')
			->alias('-f')
			->acceptsArgument();
		
		$options->add('--bar')
			->alias('-b')
			->acceptsArgument();
		
		$options->add('--baz')
			->alias('-z')
			->acceptsArgument();

		$options->add('--quux')
			->alias('-q')
			->acceptsArgument();
		
		$options->add('--waldo')
			->alias('-w')
			->acceptsArgument();
		
		$options->add('--corge')
			->alias('-c')
			->acceptsArgument();

		$options->register();
		$optionArguments = $options->getOptionArgs();

		$this->assertEquals('Foo', $optionArguments['--foo']);	
		$this->assertEquals('Bar', $optionArguments['--bar']);	
		$this->assertEquals('Baz', $optionArguments['--baz']);	
		$this->assertEquals('Quux', $optionArguments['--quux']);	
		$this->assertEquals('Waldo', $optionArguments['--waldo']);	
		$this->assertEquals('Corge', $optionArguments['--corge']);	

	
		// single option argument standard syntax
		$argv = [ 'script-name', '--xray', 'foo'];
		$options = new Options($argv);

		$options->add('--xray')
			->acceptsArgument();

		$options->register();
		$option_argument = $options->getOptionArgs('--xray');

		$this->assertEquals('foo', $option_argument);	


		// single option argument standard syntax alias provided instead of option name
		$argv = [ 'script-name', '-x', 'foo'];
		$options = new Options($argv);

		$options->add('--xray')
			->alias('-x')
			->acceptsArgument();

		$options->register();
		$option_argument = $options->getOptionArgs('--xray');

		$this->assertEquals('foo', $option_argument);	


		// multiple options with one option argument standard syntax
		$argv = [ 'script-name', '-r', '--xray', 'foo', 'bar'];
		$options = new Options($argv);

		$options->add('--xray')
			->acceptsArgument();

		$options->register();
		$option_argument = $options->getOptionArgs('--xray');

		$this->assertEquals('foo', $option_argument);	


		// multiple options with one option argument standard syntax, option and alias provided, but alias has the value
		// and option is last (with no value)
		// This should return false because the *final* argument is the one that's accepted, which was not provided by '--xray'
		$argv = [ 'script-name', '-r', '-x', 'foo', '--xray'];
		$options = new Options($argv);

		$options->add('--xray')
			->acceptsArgument();

		$options->register();
		$option_argument = $options->getOptionArgs('--xray');

		$this->assertFalse($option_argument);	


		// single option argument non-standard ('foo=bar') syntax
		$argv = [ 'script-name', '--foo=bar'];
		$options = new Options($argv);

		$options->add('--foo')
			->acceptsArgument();

		$options->register();
		$option_argument = $options->getOptionArgs('--foo');

		$this->assertEquals('bar', $option_argument);	
		

		// single option argument non-standard ('--foo=bar') syntax but testing against alias
		$argv = [ 'script-name', '--foo=bar'];
		$options = new Options($argv);

		$options->add('--foo')
			->alias('-f')
			->acceptsArgument();

		$options->register();
		$option_argument = $options->getOptionArgs('-f');

		$this->assertEquals('bar', $option_argument);	


		// single option argument non-standard ('-f=bar') syntax but alias provided instead of option
		$argv = [ 'script-name', '-f=bar'];
		$options = new Options($argv);

		$options->add('--foo')
			->alias('-f')
			->acceptsArgument();

		$options->register();
		$option_argument = $options->getOptionArgs('--foo');

		$this->assertEquals('bar', $option_argument);	

	}

	public function testSillyUsage() {

		$argv = [ 'script-name', '-asdf=bar'];
                $options = new Options($argv);

                $options->add('-a');
                $options->add('-s');
                $options->add('-d');
                $options->add('-f')
			->acceptsArgument();

                $options->register();

		$optArgs = $options->getOptionArgs();

		$this->assertArrayhasKey('-f', $optArgs);

	}

	public function testArguments() {

		// testing script Arguments here, not Option Arguments.

		// make sure an argument at the beginning, end, and throughout the middle will register, even with option arguments throughout
		$argv = [ 'script-name', 'ARG0', '--foo=IGNOREME', 'ARG1', '-f=Foo', '-b', 'Bar', 'ARG2', '--baz', 'Baz', '--quux=Quux', '-xyzw', 'Waldo', 'ARG3' ];
		$options = new Options($argv);

		$options->add('--foo')
			->alias('-f')
			->acceptsArgument();
		
		$options->add('--bar')
			->alias('-b')
			->acceptsArgument();
		
		$options->add('--baz')
			->alias('-z')
			->acceptsArgument();

		$options->add('--quux')
			->alias('-q')
			->acceptsArgument();
		
		$options->add('--waldo')
			->alias('-w')
			->acceptsArgument();

		$options->register();

		$arguments = $options->getArguments();

		$this->assertContains('ARG0', $arguments);
		$this->assertContains('ARG1', $arguments);
		$this->assertContains('ARG2', $arguments);
		$this->assertContains('ARG3', $arguments);
		$this->assertCount(4, $arguments);
		
	}
	


}

?>
