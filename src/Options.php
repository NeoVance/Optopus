<?php

namespace Optopus;

class Options
{

	public $options = [

		"--" => [
				'description' => 'End of Options.  If this is used, no options after it will be parsed unless the preceeding option requires an argument'
			],
		"--help" => [
				'description' => 'This help page.'
			],
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

	public function description($desc = null) {

		$this->options[$this->_current_option]['description'] = $desc;
		return $this;
	}

	public function help($help) {

		if(isset($help)) {
			$this->help = $help;
		}
		return $this;
	}

	public function title($title) {

		// defaults to script name in help

		if(isset($title)) {
			$this->title = $title;
		}
		return $this;
	}

	public function all($type = 'both') {

		$type = strtolower($type);

		foreach($this->options as $option => $array) {
			if(!strstr('alias', $type)) {
				$all[] = $option;
			}
			if(!strstr('option', $type)) {
				if(array_key_exists('aliases', $array)) {
					foreach($array['aliases'] as $alias) {
						$all[] = $alias;
					}
				} 
			}
		}
		return $all;
	}

	protected function _guessOption($given) {

		$options = $this->all();
		foreach($options as $option) {
			$lev = levenshtein($given, $option);
			if(!isset($floor)) {
				$floor = $lev;
			}
			if($lev <= $floor) {
				$floor = $lev;
				$best_guess = $option;
			}
		}
		return $best_guess;
	}

	protected function _help($option = null) {

		$title = isset($this->title) ? $this->title : $this->script;
		echo PHP_EOL.$title.PHP_EOL.PHP_EOL;

		if(isset($option)) {
			if(!$options = $this->_getOption($option)) {

				// it looked like an option but it isn't one
				$guess = $this->_guessOption($option);
				echo "  Unknown option $option .  Did you mean $guess ? Try --help by itself to see a full help page.\n\n";
				$options = $this->_getOption($guess);
			}
		} else {
			// all
			$options = $this->options;
		}
	
		// user has overridden our baked in help
		if(isset($this->help)) {
			echo $this->help;
		} else {

			foreach($options as $option => $array) {
				$aliases = '';
				if(array_key_exists('aliases', $array)) {
					foreach($array['aliases'] as $alias) {
						$aliases .= "|".$alias;
					}
				}

				$description = isset($array['description']) ? $array['description'] : "No description available.";
				echo "  ".$option.$aliases.PHP_EOL;
				echo "     ".$description.PHP_EOL.PHP_EOL;
			}
			echo PHP_EOL;
		}
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

		$end_of_options = false;
		foreach($this->given as $key => $token) {

			if($token === "--help") {
				if(isset($this->given[$key + 1])) {
					$with = $this->given[$key + 1];
					$this->_help($with);
					die();
				}
				$this->_help();
				die();
			}

			if($token === "--") {
				if(!isset($previous) || !$this->_requiresArgument($previous)) {
					$end_of_options = true;
				}
			}
			
			if(!$end_of_options) {
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
			} else {
				$this->_normalized[] = $token;
			}
			$previous = $token;
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

		$end_of_options = false;
		$this->_normalize();
		foreach($this->_normalized as $key => $token) {

			if($token === "--") {
				if(!isset($previous) || !$this->_requiresArgument($previous)) {
					$end_of_options = true;
					$this->_setSelected($token);
				}
			}
			
			if(!$end_of_options) {
				if($this->_looksLikeOption($token)) {
					if(!$this->_isOption($token)) {
						if(isset($previous) && $this->_acceptsArgument($previous)) {
							$this->_setOptArg($previous, $token);
						} else {
							// this looks like an option but it's not
							$this->_help($token);
							die();
						}
					} else {
						// it looks like an option and it is an option
						$this->_setSelected($token);
						if($this->_repeats($token)) {
							$this->_incrementRepeats($token);
						}
					}
				}
				if(isset($previous) && $this->_requiresArgument($previous)) {
					$this->_setOptArg($previous, $token);	
					$this->_unSetSelected($token);
				} elseif(isset($previous) && $this->_acceptsArgument($previous) && !$this->_isOption($token)) {
					$this->_setOptArg($previous, $token);
					$this->_unSetSelected($token);
				} else {

					if(!$this->_isOption($token)) {
						// then it must be a script argument
						$this->arguments[] = $token;
					}
				}
			} else {
				$this->arguments[] = $token;
			}
			$previous = $token;
		}
		// @todo - changing loop logic will remove need for this
		// it unsets the first occurance of "--" as a script argument since it should not be
		if($end_of_options) {
			foreach($this->arguments as $key => $argument) {
				if($argument === "--") {
					unset($this->arguments[$key]);
					$this->arguments = array_values($this->arguments);
					break;
				}
			}	
		}
	}
}

?>
