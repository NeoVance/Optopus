<?php

namespace Optopus\Test;

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Optopus\Options;

class OptionsTest extends \PHPUnit_Framework_TestCase {

	public function testAddOption() {

		$argv = [ 'script-name' ];
		$options = new Options($argv);

		$options->add('-x');
		$options->parse();
		$this->assertNotFalse($options->get('-x'));
	}
	
	public function testAddAlias() {

		$argv = [ 'script-name' ];
		$options = new Options($argv);

		$options->add('-x')
			->alias('-X');
		$options->parse();
		$this->assertNotFalse($options->getAlias('-X'));
	}
	
	public function testRequired() {

		$argv = [ 'script-name' ];
		$options = new Options($argv);

		$options->add('-x')
			->required();
		$options->parse();
		$option = $options->get('-x');
		$this->assertNotFalse($option['-x']['required']);
	}

	public function testAcceptsArgument() {
		
		$argv = [ 'script-name' ];
		$options = new Options($argv);

		$options->add('-x')
			->acceptsArgument();
		$options->parse();
		$option = $options->get('-x');
		$this->assertNotFalse($option['-x']['accepts_argument']);

	}
	
	public function testAcceptsRequiresArgument() {
		
		$argv = [ 'script-name' ];
		$options = new Options($argv);

		$options->add('-x')
			->acceptsArgument('required');
		$options->parse();
		$option = $options->get('-x');
		$this->assertNotFalse($option['-x']['accepts_argument']);
		$this->assertNotFalse($option['-x']['requires_argument']);

	}

	public function testRepeats() {

		// called once
		$argv = [ 'script-name', '-v' ];
		$options = new Options($argv);

		$options->add('--verbose')
			->alias('-v')
			->repeats();
		$options->parse();
		$option = $options->get('--verbose');
		$this->assertNotFalse($option['--verbose']['repeats']);
		$this->assertEquals(1, $option['--verbose']['repeat_count']);

		// called twice
		$argv = [ 'script-name', '-vv' ];
		$options = new Options($argv);

		$options->add('--verbose')
			->alias('-v')
			->repeats();
		$options->parse();
		$option = $options->get('-v');
		$this->assertNotFalse($option['--verbose']['repeats']);
		$this->assertEquals(2, $option['--verbose']['repeat_count']);
		
		// called with long and short
		$argv = [ 'script-name', '-v', '--verbose' ];
		$options = new Options($argv);

		$options->add('--verbose')
			->alias('-v')
			->repeats();
		$options->parse();
		$option = $options->get('-v');
		$this->assertNotFalse($option['--verbose']['repeats']);
		$this->assertEquals(2, $option['--verbose']['repeat_count']);

		// called with long and short and other options and script args mixed in
		$argv = [ 'script-name', '-a', 'ARG0', '-v', '-s', '--verbose', 'ARG1', '-d', 'ARG2' ];
		$options = new Options($argv);

		$options->add('--verbose')
			->alias('-v')
			->repeats();
		$options->parse();
		$option = $options->get('-v');
		$this->assertNotFalse($option['--verbose']['repeats']);
		$this->assertEquals(2, $option['--verbose']['repeat_count']);
	}

	public function testCluster() {
	
		// simple cluster	
		$argv = [ 'script-name', '-asdf' ];
		$options = new Options($argv);

		$options->add('-a');
		$options->add('-s');
		$options->add('-f');
		$options->add('-d');
		$options->parse();

		$myopts = $options->get();

		$given = [ '-a', '-s', '-d', '-f' ];
		foreach($given as $opt) {
			$this->assertArrayHasKey($opt, $myopts);
		}

		// test f=Foo; f accepts argument
		$argv = [ 'script-name', '-asdf=Foo' ];
		$options = new Options($argv);

		$options->add('-a');
		$options->add('-s');
		$options->add('-f')
			->acceptsArgument();
		$options->add('-d');
		$options->parse();

		$myopts = $options->get();

		// make sure all arguments are present
		$given = [ '-a', '-s', '-d', '-f' ];
		foreach($given as $opt) {
			$this->assertArrayHasKey($opt, $myopts);
		}

		$this->assertEquals($myopts['-f']['argument'], 'Foo');
		
		// test fFoo; f accepts argument
		$argv = [ 'script-name', '-asdfFoo' ];
		$options = new Options($argv);

		$options->add('-a');
		$options->add('-s');
		$options->add('-f')
			->acceptsArgument();
		$options->add('-d');
		$options->parse();

		$myopts = $options->get();

		// make sure all arguments are present
		$given = [ '-a', '-s', '-d', '-f' ];
		foreach($given as $opt) {
			$this->assertArrayHasKey($opt, $myopts);
		}

		$this->assertEquals($myopts['-f']['argument'], 'Foo');
		
		// test -s accepts argument, should take everything after -s, which is "Soodf"
		$argv = [ 'script-name', '-asSoodf' ];
		$options = new Options($argv);

		$options->add('-a');
		$options->add('-s')
			->acceptsArgument();
		$options->add('-f');
		$options->add('-d');
		$options->parse();

		$myopts = $options->get();

		$this->assertEquals($myopts['-s']['argument'], 'Soodf');

		// test -s accepts argument with s= syntax, should take everything after -s, which is "Soodf"
		$argv = [ 'script-name', '-as=Soodf' ];
		$options = new Options($argv);

		$options->add('-a');
		$options->add('-s')
			->acceptsArgument();
		$options->add('-f');
		$options->add('-d');
		$options->parse();

		$myopts = $options->get();

		// make sure all arguments are present
		$given = [ '-a', '-s', '-d', '-f' ];
		foreach($given as $opt) {
			$this->assertArrayHasKey($opt, $myopts);
		}

		$this->assertEquals($myopts['-s']['argument'], 'Soodf');

	}

}

?>
