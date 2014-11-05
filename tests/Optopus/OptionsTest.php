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

	public function testAdd() {

		// same as above but it doesn't matter
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

		$argv = [ 'script-name', '--foo', '--'];
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
}

?>
