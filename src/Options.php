<?php

namespace Optopus;

class Options {
	
	protected $_current_option;
	protected $_option_args = [];
	protected $_available = [
		
		// initialize with GNU standard 'end-of-options' option, and GNU standard help option
		"--" => [
			'description' => 'end-of-options.  No options will be parsed after this option.'
		],
		"--help" => [
			'aliases' => [
				'-h',
				'-?'
			],
			'description' => 'Displays this help info',
		],
	];

	public function __construct($tokens) {

		$this->title = $tokens[0];
		array_shift($tokens);
		$this->selected = $tokens;

	}

	// Build Options and Get/Set

	public function title($title) {
		
		// default is script name -- used for help page.  Override that here
		$this->title = $title;
	}

	public function getTitle() {

		// mainly for testsuite but it might be useful outside of that
		return $this->title;
		return false;

	}

	public function add($option) {

		// add available option
		$this->_current_option = $option;
		$this->_available[$option] = [];
		
		return $this;
	}

	public function get($option = null) {
	
		if(!isset($option)) {
			return $this->_available;
		}	

		if($parent_option = $this->getAliasParent($option)) {
			$option = $parent_option;
		}

		if(array_key_exists($option, $this->_available)) {
			return $this->_available[$option];
		}
		return false;
	}

	public function alias($alias) {

		// add available alias for an option
		$this->_available[$this->_current_option]['aliases'][] = $alias;
	
		return $this;

	}

	public function required() {

		$this->_available[$this->_current_option]['required'] = true;

		return $this;
	}

	public function acceptsArgument($required = false) {

		// allow option to accept argument ie:  --foo=bar OR --foo bar
		$this->_available[$this->_current_option]['accepts_argument'] = true;

		if($required) {
			$this->_available[$this->_current_option]['requires_argument'] = true;
		}	

		return $this;

	}

	public function repeats() {

		// for options like --verbose or -v where repeating the option results in amplification like -vvvvvv ... or -v -v -v or --verbose --verbose (less common but should be allowed)

		$this->_available[$this->_current_option]['repeats'] = true;
		$this->_available[$this->_current_option]['repeat_count'] = 0; // just init to 0 since we haven't parsed actual selected opts yet
		
		return $this;
		
	}
	
	public function description($description) {
		
		$this->_available[$this->_current_option]['description'] = $description;

		return $this;
	}

	// General Methods

	private function strip_leading_dashes($opt) {
		return preg_replace('/^-+/', '' ,$opt);
	}

	private function argType($arg) {
	
		if($arg[0] === "-") {
			if($arg[1] === "-") {
				if(!isset($arg[2])) {
					return 'end'; //end-of-options "--"
				}
				return 'long';
			}
			return 'short';
		}
		return 'arg'; // this is not an option, but rather a script argument

	}

	// Supplied Options/Arguments Methods
	
	private function getAliases($option) {

		if(array_key_exists('aliases', $this->_available[$option])) {
			return $this->_available[$option]['aliases'];
		}
		return false;
	}

	public function getAliasParent($alias) {

		foreach($this->_available as $option => $array) {
			if(array_key_exists('aliases', $array)) {
				if(in_array($alias, $array['aliases'])) {
					return $option;	
				}
			}
		}
		return false;

	}

	private function setSelected($option) {

		if(isset($this->_available[$option])) {
			$this->_available[$option]['selected'] = true;
			return true;
		}

		if($parent_option = $this->getAliasParent($option)) {
			$this->setSelected($parent_option);
		}
	
		return false;
	
	}

	private function explodeShortOpts(&$tokens) {

		// this finds clustered shortopts, ie: -asdf and converts them to -a -s -d -f 
		// But it has to also maintain the order they were supplied for consistency, and to allow the final
		// clustered shortopt to acceptsArgument() properly, even though that's a strange end-user usage.

		foreach($tokens as $key => $selected) {

			// Below conditions explained:
			// cond1: argType is short
			// cond2: it is clustered (more than 2 chars including dash), ie: -as, NOT just -a
			// cond3: it is not a shortopt with argument using "=" style syntax, ie: -f=foo
			if($this->argType($selected) === 'short' && strlen($selected) > 2 && $selected[2] !== "=") {

				// if it still has an "=", then it's a 'silly' but acceptable syntax.  Clustered, short, AND "=" style option arg for the last one
				// ie:  -asdf=bar
				//
				if(strstr($selected, "=")) {
					$arr = explode("=", $selected);
					$option_arg = "-".substr($arr[0], -1)."=".$arr[1]; // "-f=bar"
					$selected = $arr[0]; // -asd
				}
				// unset the "-asdf"
				unset($tokens[$key]);

				// repopulate as individual options
				$shortopts = str_split($this->strip_leading_dashes($selected));
				array_walk($shortopts, function(&$shortopt) { $shortopt = "-".$shortopt; });
				array_splice($tokens, $key, 0, $shortopts);

				// we did array_splice, so we have to manually advance the internal array pointer by the amount of shortopts
				// kind of a hack, but it's necessary
				for($i=0; $i<count($shortopts); $i++) { next($tokens); }

				// if a 'silly' style -asdf=bar was inside the cluster we tag it onto the end, and advance internal pointer once again
				if(isset($option_arg)) {
					$tokens[] = $option_arg;
					//next($tokens);
				}
			}
		}

	}

	private function setOptionArgs($option, $arg) {

		// _option_args are like script paramaters/arguments except for an option itself, ie: --foo=bar or --foo bar or -f=bar or -f bar
		// @todo - the one thing we don't currently allow is -asdfbar or -asdf=bar ...Should we?  It's kind of a bizarre user usage...
		// we are assuming in he above that only -f/--foo allows an option argument
		if($parent_option = $this->getAliasParent($option)) {
			$option = $parent_option;
		}
		
		$this->_option_args[$option] = $arg;
		return true;

	}

	public function getOptionArgs($option = null) {

		if(!isset($option)) {
			return $this->_option_args;
		}
		
		if($parent_option = $this->getAliasParent($option)) {
			$option = $parent_option;
		}
	
		if(isset($this->_option_args[$option])) {
			return $this->_option_args[$option];
		}
		return false;

	}

	private function setArgument($arg) {

		// main script arguments (not options or option args) ie:  ./script --foo bar baz
		// assuming foo accepts arguments, then baz is a script argument

		$this->arguments[] = $arg;	
	}

	public function getArguments() {
		
		if(isset($this->arguments)) {
			return $this->arguments;
		}
		return false;
	}

	private function allowsArgument($option) {
		
		// just determines if option or alias accepts argument
		//if(!empty($this->_available[$option]['accepts_argument'])) {
		if(isset($this->_available[$option]['accepts_argument']))
			if($this->_available[$option]['accepts_argument']) {
				return true;
		}

		// maybe it's an alias?
		$alias = $option;
		$parent_option = $this->getAliasParent($alias);

		if(isset($this->_available[$parent_option]['accepts_argument'])) {
                        if($this->_available[$parent_option]['accepts_argument']) {
                                return true;
                        }
                }

		return false;

	}

	private function allowsRepeats($option) {

		if(isset($this->_available[$option]['repeats']) && $this->_available[$option]['repeats']) {
			return true;
		}

		// maybe it's an alias?
		$alias = $option;
		$parent_option = $this->getAliasParent($alias);
	
		// @todo - condense this -- also see all allows* methods	
		if(isset($this->_available[$parent_option]['repeats'])) {
			if($this->_available[$parent_option]['repeats']) {
				return true;
			}
		}

		return false;
	}

	private function incrementRepeats($option) {

		if(!isset($this->_available[$option])) {
			$option = $this->getAliasParent($option);
		}
		if(!isset($this->_available[$option]['repeat_count'])) {
			$this->_available[$option]['repeat_count'] = 1;
		} else {
			$this->_available[$option]['repeat_count'] += 1;
		}
	}

	private function help() {
		
		if(isset($this->title)) {
			printf("\n%s\n\n", $this->title);
		}
		foreach($this->_available as $option => $array) {
			if(!isset($array['description'])) {
				$array['description'] = "No description available.";
			}
			printf("%-20s%s\n", $option, $array['description']);
		}
		printf("\n");
	}	

	public function register() {

		// this method is called after all options have been set
	
		$this->explodeShortOpts($this->selected);	
		foreach($this->selected as $selected) {
			if($selected === "--help" || $selected === "-h" || $selected === "-?") {
				$this->help();
				die();
			}
			if($this->allowsRepeats($selected)) {
				$this->incrementRepeats($selected);
			}

			if(strstr($selected, "=")) {
				$option_arg = explode("=", $selected);
				if($this->allowsArgument($option_arg[0]) && $option_arg[1][0] !== '-') {
					$this->setOptionArgs($option_arg[0], $option_arg[1]);
					$this->setSelected($option_arg[0]);
					// @todo - error handling here?  they've tried to set an option argument to an option that doesn't accept arguments outside this if block
				}
			} elseif(isset($previous) && $this->allowsArgument($previous) && $selected[0] !== "-") {
				
					// @todo - error handling here?  if the option takes  a 'required' argument, we should error out here 
					$this->setOptionArgs($previous, $selected);
			} else {

				// otherwise just parse the rest by type
				switch($this->argType($selected)) {
					case 'end':
						$this->endOfOptions($key);
						continue 2;
					case 'short':
					case 'long':
						$this->setSelected($selected);
						break;
					case 'arg':
						$this->setArgument($selected);
						break;
				}
			}
			$previous = $selected;
		}
		
	}

}
