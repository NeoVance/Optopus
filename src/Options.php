<?php

namespace Optopus;

class Options {

	public $optArgs = [];

	public $options = [
		
		'--' => [
			'description' => 'End of Options.  Everything after this option will be considered a script argument.',
		],

		'--help' => [
			'description' => 'Generates this help output.',
		],
	];

	public function __construct($argv) {

		$this->script = array_shift($argv);
		$this->given = $argv;
	}

	// define options, aliased options, etc..
	public function add($option) {

		$this->current_option = $option;
		$this->options[$option] = [];
		return $this;
	}

	public function alias($alias) {

		$this->options[$this->current_option]['aliases'][] = $alias;
		return $this;
	}
	
	public function required() {

		$this->options[$this->current_option]['required'] = true;
		return $this;
	}

	public function repeats() {

		$this->options[$this->current_option]['repeats'] = true;
		$this->options[$this->current_option]['repeat_count'] = 0;
		return $this;
	}
	
	public function description($description) {

		$this->options[$this->current_option]['description'] = $description;
		return $this;
	}

	public function title($title) {

		// displayed in _help() page
		$this->title = $title;
		
	}

	public function acceptsArgument($required = null) {

		$this->options[$this->current_option]['accepts_argument'] = true;

		if($required === strtolower('required')) {
			$this->options[$this->current_option]['requires_argument'] = true;
		}
		return $this;
	}
	
	public function get($option = null) {

		if(!isset($option)) {
			return $this->options;
		}

		if($this->_isOption($option)) {
			return $this->_getOption($option);
		}
	}

	public function getAlias($alias) {

		if($this->_isOption($alias)) {
			return $this->_getOption($alias);
		}
	}

	public function helpOverride($help) {

		$this->help_override = $help;
	}

	private function _help() {

		$heading = isset($this->title) ? $this->script." - ".$this->title : $this->script;
		printf("%s\n\n", $heading);

		if(isset($this->help_override)) {
			echo $this->help_override;
		} else {
			foreach($this->options as $name => $Option) {

				$aliases = '';
				if(isset($Option['aliases'])) {
					$aliases = '|';
					foreach($Option['aliases'] as $alias) {
						$aliases .= $alias;
					}
				}

				$desc = isset($Option['description']) ? $Option['description'] : 'No description available.';

				printf("\t%-30s%s\n", $name.$aliases, $desc);
			}
			printf("\n");
		}
	}

	private function _isCluster($token) {

		// for code simplicity, we consider a "cluster" to be like:  -asdf as you would expect, but also a single shortopt qualifies, ie:
		// -a

		if($token[0] === "-" && $token[1] !== "-") {
			return true;	
		}
		return false;
	}

	private function _stripLeadingDashes($token) {

		return preg_replace('/^-+/', '' ,$token);
	}

	private function _getAliasParent($alias) {

		//$alias = $this->_addDashes($alias);

		foreach($this->options as $name => $option) {
			if(array_key_exists('aliases', $option)) {
				if(in_array($alias, $option['aliases'])) {
					return [$name => $option];	
				}
			}
		}
		return false;
	}

	private function _isAlias($option) {
		
		//$option = $this->_addDashes($option);

		return $this->_getAliasParent($option);
	}

	private function _isOption($option) {

		//$option = $this->_addDashes($option);

		if(array_key_exists($option, $this->options) || $this->_isAlias($option)) {
			return true;
		}
		return false;
	}

	private function _repeats($option) {

		//$option = $this->_addDashes($option);
		$Option = $this->_getOption($option);

		if($Option) {
			foreach($Option as $option) {
				if(isset($option['repeats'])) {
					return true;
				}
			}
		}
		return false;
	}

	private function _incrementRepeatsFor($option) {

		//$option = $this->_addDashes($option);
		$Option = $this->_getOption($option);
		reset($Option);

		$this->options[key($Option)]['repeat_count'] +=1;

	}

	private function _getOption($option) {
		
		//$option = $this->_addDashes($option);
		
		if($parentOption = $this->_getAliasParent($option)) {
			return $parentOption;
		}

		if($this->_isOption($option)) {
			$Option = [ $option => $this->options[$option] ];
			return $Option;
		}
		return null;

	}

	private function _handleClusteredOptArgs($token) {

		$opts = '';
		$arg = '';
		$token = $this->_stripLeadingDashes($token);
		$tokens = str_split($token);
		foreach($tokens as $key => $opt) {
			// only set option args if it allows them, AND the NEXT token is NOT an option itself
			// or if it REQUIRES an argument then set everything after it
			if($this->_requiresArgument("-".$opt) || ($this->_acceptsArgument("-".$opt) && isset($tokens[$key + 1]) && !$this->_isOption("-".$tokens[$key + 1]))) {
				$opts = substr($token, 0, $key + 1);
				$arg = substr($token, $key + 1);
				if($arg[0] === "=") {
					$arg = substr($arg, 1);
				}
				break;
			}
			else {
				$opts .= $opt;
			}
		}
		$optargs['opts'] = $opts;
		$optargs['arg'] = $arg;
		return $optargs;
		
	}

	private function _setOptionSelected($option) {

		$Option = $this->_getOption($option);
		$this->options[key($Option)]['selected'] = true;
		
	}

	private function _setOptArg($opt, $arg) {

		$Option = $this->_getOption($opt);
		$this->optArgs[key($Option)] = $arg;
		$this->options[key($Option)]['argument'] = $arg;
	}

	private function _setArgument($argument) {

		$this->arguments[] = $argument;
	}

	private function _unSetArgument($argument) {

		$key = array_search($argument, $this->arguments);
		unset($this->arguments[$key]);
	}
	
	private function _deCluster($token) {

		$optargs = $this->_handleClusteredOptArgs($token);
		$opts = !empty($optargs['opts']) ? $optargs['opts'] : null;
		$arg = !empty($optargs['arg']) ? $optargs['arg'] : null;

		foreach(str_split($opts) as $opt) {
			$dtokens[] = "-".$opt;	
		}

		if(isset($arg)) {
			$dtokens[] = $arg;
		}
		return $dtokens;
	}

	private function _normalize() {

		$parsedTokens = [];
		$given = $this->given;
		foreach($given as $token) {

			if($end || $token === "--") {
				$end = true;
				$parsedTokens[] = $token;
				continue;
			}

			if($this->_isCluster($token)) {
				foreach($this->_deCluster($token) as $dtoken) {
					$parsedTokens[] = $dtoken;
				}
			} else {
				// longopt
				$option = '';
				$token = str_replace('=', '', $token);
				foreach(str_split($token) as $key => $char) {
					$option .= $char;
					if($this->_isOption($option)) {
						// everything after is optArg
						if($this->_acceptsArgument($option)) {
							$arg = substr($token, $key + 1);
							$parsedTokens[] = $option;
							$parsedTokens[] = $arg;
							continue 2;
						}
					}
				}
				$parsedTokens[] = $token;
			}
		}
		$this->normalized = $parsedTokens;
		return $this->normalized;
	}

	private function _addDashes($option) {
	
		// convenience method
		if($option[0] !== "-") {
			if(strlen($option) > 1) {
				return "--".$option;
			}
			else return "-".$option;
		}
		return $option;
	}

	private function _acceptsArgument($option) {
	
	
		if($this->_isOption($option)) {
			$Option = $this->_getOption($option);
			foreach($Option as $option) {
				if(isset($option['accepts_argument'])) {
					return true;
				}
			}
		}
		return false;
	}

	private function _requiresArgument($option) {

		if($this->_isOption($option)) {
			$Option = $this->_getOption($option);
			foreach($Option as $option) {
				if(isset($option['requires_argument'])) {
					return true;
				}
			}
		}
		return false;
		
	}

	public function parse() {

		$end = false;

		foreach($this->_normalize() as $token) {

			if($token === '--help') {
				$this->_help();
				die();
			}

			if($this->_isOption($token)) {
				$this->_setOptionSelected($token);
			} else {
				$this->_setArgument($token);
			}
			if(isset($previous) && $this->_acceptsArgument($previous)) {
	
				// if it accepts AND REQUIRES argument, set whatever token is after previous as it's optArg, regardless
				// and unset it as a SCRIPT argument
				if($this->_requiresArgument($previous)) {
					$this->_setOptArg($previous, $token);
					$this->_unSetArgument($token);
				}

				// if it merely ACCEPTS OPTIONAL argument, set next token as the optArg only if it itself, is NOT an option
				// and unset it as a SCRIPT argument
				if(!$this->_isOption($token)) {
					$this->_setOptArg($previous, $token);
					$this->_unSetArgument($token);
				}
			}
			if($this->_repeats($token)) {
				$option = $token;
				$this->_incrementRepeatsFor($option);
			}
			$previous = $token;
		}

	}
}

?>
