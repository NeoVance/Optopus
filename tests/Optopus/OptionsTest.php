<?php

namespace Optopus\Test;

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Optopus\Options;

class OptionsTest extends \PHPUnit_Framework_TestCase {

	public function testGet() {

		$argv = [ 'script-name' ];
		$Options = new Options($argv);

		$Options->add('--foo');
		$Options->parse();
		$option = $Options->get('--foo');
		$this->assertArrayHasKey('--foo', $option);
	}

	public function testGetSelected() {

		$argv = [ 'script-name', '--foo', '--bar' ];
		$Options = new Options($argv);

		$Options->add('--foo');
		$Options->add('--bar');
		$Options->parse();

		$selected = $Options->getSelected();
		$this->assertContains('--foo', $selected);
		$this->assertContains('--bar', $selected);
		$this->assertCount(2, $selected);
		
	}
	
	public function testGetOptArg() {
		
		$argv = [ 'script-name', '--foo', 'FOO', '--bar', '--bar', '--baz', '--foo' ];
		$Options = new Options($argv);

		// testing that --foo accepts an argument of FOO
		$Options->add('--foo')
			->acceptsArgument();

		// testing that --bar accepts but does not require an argument, so --bar is simply called twice
		$Options->add('--bar')
			->acceptsArgument();

		// testing that --baz requires arg and will force the next token to be it's argument even if the token itself is a valid option
		$Options->add('--baz')
			->requiresArgument();
		$Options->parse();

		$optargs = $Options->getOptArg();
		$this->assertEquals([ '--foo' => 'FOO', '--baz' => '--foo' ], $optargs);

		// test with specific option provided
		$foo = $Options->getOptArg('--foo');

		$this->assertEquals(['--foo' => 'FOO'], $foo);
		
	}
	
	public function testAdd() {

		$argv = [ 'script-name' ];
		$Options = new Options($argv);

		$Options->add('--foo');
		$Options->parse();
		$option = $Options->get('--foo');
		$this->assertArrayHasKey('--foo', $option);
	}

	public function testAlias() {

		$argv = [ 'script-name' ];
		$Options = new Options($argv);

		$Options->add('--foo')
			->alias('-f');
		$Options->parse();
		$option = $Options->get('-f');
		$this->assertArrayHasKey('--foo', $option);
		$this->assertEquals('-f', $option['--foo']['aliases'][0]);
	}

	public function testAcceptsArgument() {

		$argv = [ 'script-name' ];
		$Options = new Options($argv);

		$Options->add('--foo')
			->acceptsArgument();
		$Options->parse();
		$option = $Options->get('--foo');
		$this->assertTrue($option['--foo']['accepts_argument']);
	}

	public function testRequiresArgument() {

		$argv = [ 'script-name' ];
		$Options = new Options($argv);

		$Options->add('--foo')
			->requiresArgument();
		$Options->parse();
		$option = $Options->get('--foo');
		$this->assertTrue($option['--foo']['accepts_argument']);
		$this->assertTrue($option['--foo']['requires_argument']);
	}

	public function testDescription() {

		$argv = [ 'script-name' ];
		$Options = new Options($argv);

		$Options->add('--foo')
			->description('my description');
		$Options->parse();
		$option = $Options->get('--foo');
		$this->assertEquals('my description', $option['--foo']['description']);
		
	}

	public function testTitle() {

		$argv = [ 'script-name' ];
		$Options = new Options($argv);

		$Options->add('--foo');
		$Options->title('my title');
		$Options->parse();
		$this->assertEquals('my title', $Options->title);
	}
	
	public function testEndOfOptions() {

		// "--foo" should be treated as an argument, NOT parsed as an option
		$argv = [ 'script-name', '--', '--foo'];
		$Options = new Options($argv);

		$Options->add('--foo');
		$Options->parse();
		$this->assertEquals('--foo', $Options->arguments[0]);

		// end-of-options should NOT be honored here because --foo requiresArgument
		// it should be captured as the argument to option --foo instead
		$argv = [ 'script-name', '--foo', '--'];
		$Options = new Options($argv);

		$Options->add('--foo')
			->requiresArgument();
		$Options->parse();
		
		$eooOption = $Options->get('--');	
		$this->assertArrayNotHasKey('selected', $eooOption['--']);
		
		$fooOption = $Options->get('--foo');
		$this->assertEquals('--', $fooOption['--foo']['arg']);

		// end-of-options should be honored if the option merely acceptsArgument

		$argv = [ 'script-name', '--foo', '--' ];
		$Options = new Options($argv);

		$Options->add('--foo')
			->acceptsArgument();
		$Options->parse();
		
		$eooOption = $Options->get('--');	
		$this->assertArrayHasKey('selected', $eooOption['--']);
		
		$fooOption = $Options->get('--foo');
		$this->assertArrayNotHasKey('arg', $fooOption['--foo']);

		// end-of-options should be honored even if --help is given

		$argv = [ 'script-name', '--', '--help'];
		$Options = new Options($argv);

		$Options->parse();
		
		$eooOption = $Options->get('--');	
		$this->assertArrayHasKey('selected', $eooOption['--']);
		
		$helpOption = $Options->get('--help');
		$this->assertArrayNotHasKey('selected', $helpOption['--help']);
		
	}

	public function testGetArguments() {

		// script arguments: They are not options and also not options' arguments
		// They are simply tokens useable by the script itself

		// basic test
		$argv = [ 'script-name', 'ARG'];
		$Options = new Options($argv);

		$Options->add('--foo');
		$Options->parse();

		$script_args = $Options->getArguments();

		$this->assertEquals('ARG', $script_args[0]);

		// test with end-of-options
		$argv = [ 'script-name', '--', 'ARG'];
		$Options = new Options($argv);

		$Options->add('--foo');
		$Options->parse();

		$script_args = $Options->getArguments();

		$this->assertEquals('ARG', $script_args[0]);
	
		// test with end-of-options with some tokens that would otherwise be valid options, even end-of-options '--' itself
		$argv = [ 'script-name', 'ARG0', '--', '--foo', '--', 'ARG3' ];
		$Options = new Options($argv);

		$Options->add('--foo');
		$Options->parse();

		$script_args = $Options->getArguments();

		// arg0
		$this->assertEquals('ARG0', $script_args[0]);
		// arg1
		$this->assertEquals('--foo', $script_args[1]);
		// arg2
		$this->assertEquals('--', $script_args[2]);
		// arg3
		$this->assertEquals('ARG3', $script_args[3]);
		
	}

	public function testUglyOptArgSyntax() {

		// Let's test that 'FOO' is accepted as the optional argument for --foo
		// This is an ugly but acceptable way to pass an option argument to '--foo'
		// "--option=value"  or "--foo Foo" is preferred by most people, but we want to
		// support this style because it's common in some GNU utils, and why not?

		$argv = [ 'script-name', '--fooFOO' ];
		$Options = new Options($argv);

		$Options->add('--foo')
			->acceptsArgument();
		$Options->parse();

		$opt_args = $Options->getOptArgs();

		$this->assertEquals(['--foo' => 'FOO'], $opt_args);

		// Let's try the same thing, but what if '--fooFOO' is actually valid option?
		$argv = [ 'script-name', '--fooFOO' ];
		$Options = new Options($argv);

		$Options->add('--fooFOO');
		$Options->parse();

		$opt_args = $Options->getOptArgs();
		$this->assertEmpty($opt_args);

	}
}

?>
