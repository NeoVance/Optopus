<?php

namespace Optopus;

class Options
{

	public $options = [

		"--" => [],
		"--help" => [],
	];

	public function __construct($argv) {
		
		$this->script = array_shift($argv);
		$this->given = $argv;
	}

	protected function _isCluster($token) {

		if($token[0] == "-" && $token[1] !== "-" && strlen($token) > 2) {
			return true;
		}
		return false;

	}

	protected function _isAlias($alias) {

		foreach($this->options as $option) {
			if(array_key_exists('aliases', $option) && in_array($alias, $option['aliases'])) {
				return true;
			}
		}
		return false;
	}

	protected function _isOption($option) {

		if(array_key_exists($option, $this->options) || $this->_isAlias($option)) {
			return true;
		}
		return false;
	}

	protected function _getOption($option) {

		if($this->_isOption($option)) {
			if(array_key_exists($option, $this->options)) {
				return [$option => $this->options[$option]];
			} else {
				$alias = $option;
				foreach($this->options as $parent => $option) {
					if(array_key_exists('aliases', $option) && in_array($alias, $option['aliases'])) {
						return [$parent => $this->options[$parent]];
					}
				}
			}
		}
		return null;
	}

	protected function _looksLikeOption($token) {

		return $this->_looksLikeLongOpt($token) || $this->_looksLikeShortOpt($token);
		return false;
	}

	protected function _looksLikeShortOpt($token) {

		if($token[0] === "-" && strlen($token) == 2 && $token[1] !== "-") {
			return true;	
		}
		return false;
	}

	protected function _looksLikeLongOpt($token) {

		// if token[2] is not set, then this is actually end-of-options "--"
		if($token[0] === "-" && $token[1] === "-" && isset($token[2])) {
			return true;
		}
		return false;
	}

	protected function _acceptsArgument($option) {

		if($Option = $this->_getOption($option)) {
			foreach($Option as $name => $value) {
				if(isset($this->options[$name]['accepts_argument'])) {
					return true;
				}
			}
		}
		return false;
	}
	
	protected function _setSelected($option) {

		if($Option = $this->_getOption($option)) {
			foreach($Option as $name => $value) {
				$this->options[$name]['selected'] = true;
			}
		}
	}

	protected function _unSetSelected($option) {

		if($Option = $this->_getOption($option)) {
			foreach($Option as $name => $value) {
				$this->options[$name]['selected'] = false;
			}
		}
	}

	protected function _requiresArgument($option) {

		if($Option = $this->_getOption($option)) {
			foreach($Option as $name => $value) {
				if(isset($this->options[$name]['requires_argument'])) {
					return true;
				}
			}
		}

		return false;
	}

	protected function _strip_leading_dashes($opt) {

		return preg_replace('/^-+/', '' ,$opt);
	}

	protected function _deCluster($tokens) {

		$tokens = substr($tokens, 1); // remove leading dash
		$declustered = str_split($tokens);
		foreach($declustered as $key => $opt) {
			
			// if it requires arg, then everything after it is arg
			if($this->_requiresArgument("-".$opt)) {
				$flag = 1;
			}

			// if it only accepts arg, everything after is arg unless next is also an option
			if($this->_acceptsArgument("-".$opt)) {
				if(isset($declustered[$key + 1]) && !$this->_isOption("-".$declustered[$key + 1]) || isset($flag)) {
					$arg = !empty(implode('', array_slice($declustered, $key + 1))) ? implode('', array_slice($declustered, $key + 1)) : null;
					if($arg[0] === "=") {
						$arg = substr($arg, 1);
					}
					$result = array_slice($declustered, 0, $key + 1);
					array_walk($result, function(&$opt) { $opt = "-".$opt; });
					if(isset($arg)) {
						$result[] = $arg;
					}
					break;
				} else {
					$result[] = "-".$opt;

				}
			} else {
				$result[] = "-".$opt;
			}
		}
		return $result;
	}

	// Option construction methods

	private function _prohibited($option) {

		// we can't allow an option to literally contain "=".  I don't know why someone would do that, but it will break things.
		// we also can't allow literally "-" as an option for similar reasons ("--" is end-of-options already).

		if(strstr($option, "=") || $option === "-") {
			return true;
		}
		return false;
	}

	public function add($option) {

		if($this->_prohibited($option)) {
			throw new Exception("Attmepted to add prohibited option $option");
			// @todo friendlier error handling here
		}

		$this->_current_option = $option;
		$this->options[$option] = [];
		return $this;
	}

	public function alias($alias) {

		if($this->_prohibited($alias)) {
			throw new Exception("Attmepted to add prohibited option $alias");
		}

		$this->options[$this->_current_option]['aliases'][] = $alias;
		return $this;
	}

	public function required() {

		$this->options[$this->_current_option]['required'] = true;
		return $this;
	}

	public function acceptsArgument() {

		$this->options[$this->_current_option]['accepts_argument'] = true;
		return $this;
	}

	public function requiresArgument() {

		$this->options[$this->_current_option]['accepts_argument'] = true;
		$this->options[$this->_current_option]['requires_argument'] = true;
		return $this;
	}

	public function repeats() {

		$this->options[$this->_current_option]['repeats'] = true;
		$this->options[$this->_current_option]['repeat_count'] = 0;
		return $this;
	}

	protected function _setOptArg($option, $arg) {

		if($Option = $this->_getOption($option)) {
			foreach($Option as $name => $value) {
				$this->options[$name]['arg'] = $arg;
			}
		}
	}


	protected function _incrementRepeats($option) {

		$Option = $this->_getOption($option);
		foreach($Option as $name => $value) {
			$this->options[$name]['repeat_count'] += 1;
		}
	}

	protected function _repeats($option) {

		if($Option = $this->_getOption($option)) {
			foreach($Option as $name => $value) {
				if(isset($this->options[$name]['repeats'])) {
					return true;
				}
			}
		}
		return false;
	}

	protected function _normalize() {

		foreach($this->given as $key => $token) {
			if($this->_isCluster($token)) {
				foreach($this->_deCluster($token) as $dtoken) {
					$this->_normalized[] = $dtoken;	
				}
			} else {
				if(strstr($token, "=") && $this->_looksLikeOption($token)) {
					list($opt, $arg) = explode("=", $token);
					$this->_normalized[] = $opt;
					$this->_normalized[] = $arg;
				} else {
					$this->_normalized[] = $token;
				}
			}
		}
	}

	protected function _addDashes($option) {

		if($option[0] !== "-") {
			if(strlen($option) > 1) {
				$option = "--".$option;
			} else {
				$option = "-".$option;
			}
		}
		return $option;
	}

	public function parse() {

		$this->_normalize();
		foreach($this->_normalized as $key => $token) {

			// the only time end-of-options can not be honored is if previous option requires an argument
			// in which case "--" will be it's argument

			if(isset($end_of_options)) {
				if(isset($previous) && $this->_requiresArgument($previous)) {
					$this->_setOptArg($previous, $token);	
					$this->_unSetSelected("--");
				}
				$this->arguments[] = $token;
				continue;
			}

			if($token === "--") {
				$end_of_options = true;
				$this->_setSelected($token);
			}

			if($this->_looksLikeOption($token)) {
				if(!$this->_isOption($token)) {
					if(isset($previous) && $this->_acceptsArgument($previous)) {
						$this->_setOptArg($previous, $token);
					} else {
						// this looks like an option but it's not
						echo "Error: $token is not an option\n";
						continue;
						// @todo - help page here
					}
				} else {
					// it looks like an option and it is an option
					$this->_setSelected($token);
					if($this->_repeats($token)) {
						$this->_incrementRepeats($token);
					}
				}
			} elseif(isset($previous) && $this->_requiresArgument($previous)) {
				$this->_setOptArg($previous, $token);	
				$this->_unSetSelected($token);
			} elseif(isset($previous) && $this->_acceptsArgument($previous) && !$this->_isOption($token)) {
				$this->_setOptArg($previous, $token);
				$this->_unSetSelected($token);
			} else {

				// then it must be a script argument
				$this->arguments[] = $token;
			}

			$previous = $token;
		}
	}
}

?>
