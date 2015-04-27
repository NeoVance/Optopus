<?php

namespace Optopus;

class Options
{
    /**
     * Toggle Help Display.
     *
     * @param bool
     */
    public $NOHELP = false;

    /**
     * Default options.
     *
     * @param array
     */
    public $options = [

    "--" => [
                'description' => 'End of Options.  If this is used, no options after it will be parsed unless the preceeding option requires an argument'
    ],
    "--help" => [
                'description' => 'This help page.'
    ],
    ];

    /**
     * Contructor
     *
     * Sets script name and given args.
     * @param array $argv Supplied arguments array from command line.
     */
    public function __construct(array $argv)
    {
        $this->script = array_shift($argv);
        $this->given = $argv;
    }

    /**
     * Determines if a token string is a cluster of shortopts.
     *
     * @param string $token Part of the options to check.
     * @return bool
     */
    protected function _isCluster($token)
    {
        if ($token[0] == "-" && $token[1] !== "-" && strlen($token) > 2) {
            return true;
        }
        return false;
    }

    /**
     * Determines if an option is an alias.
     *
     * @param string $alias an Option or alias to check.
     * @return bool
     */
    protected function _isAlias($alias)
    {

        foreach ($this->options as $option) {
            if (array_key_exists('aliases', $option) && in_array($alias, $option['aliases'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determines is an option is actually an option.
     *
     * @param string $option A possible option.
     * @return bool
     */
    protected function _isOption($option)
    {

        if (array_key_exists($option, $this->options) || $this->_isAlias($option)) {
            return true;
        }
        return false;
    }

    /**
     * Gets an option.
     *
     * @param string $option A specific option.
     * @return null|array An option array and it's values.
     */
    protected function _getOption($option)
    {

        if ($this->_isOption($option)) {
            if (array_key_exists($option, $this->options)) {
                return [$option => $this->options[$option]];
            } else {
                $alias = $option;
                foreach ($this->options as $parent => $option) {
                    if (array_key_exists('aliases', $option) && in_array($alias, $option['aliases'])) {
                        return [$parent => $this->options[$parent]];
                    }
                }
            }
        }
        return null;
    }

    /**
     * Determines if an string merely looks like an option.
     *
     * @param string $token A string to be evaluated for optiony-ness.
     * @return bool
     */
    protected function _looksLikeOption($token)
    {

        return $this->_looksLikeLongOpt($token) || $this->_looksLikeShortOpt($token);
        return false;
    }

    /**
     * Determines if a string merely looks like a short option.
     *
     * @param string $token A string to be evaluated for short-optionyness.
     * @return bool
     */
    protected function _looksLikeShortOpt($token)
    {

        if ($token[0] === "-" && strlen($token) == 2 && $token[1] !== "-") {
            return true;
        }
        return false;
    }

    /**
     * Determines if a string merely looks like a long option.
     *
     * @param string $token A string to be evaluated for long-optionyness.
     * @return bool
     */
    protected function _looksLikeLongOpt($token)
    {

     // if token[2] is not set, then this is actually end-of-options "--"
        if ($token[0] === "-" && $token[1] === "-" && isset($token[2])) {
            return true;
        }
        return false;
    }

    /**
     * Determine if an option accepts an argument.
     *
     * @param string $option An option string to check.
     * @return bool
     * @todo Allow an option string, or an option array.
     * @todo Consider making Option a class itself so we
     * can type-hint on Optopus\Option or check instanceof ?
     */
    protected function _acceptsArgument($option)
    {

        if ($Option = $this->_getOption($option)) {
            foreach ($Option as $name => $value) {
                if (isset($this->options[$name]['accepts_argument'])) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Mark an option as selected.
     *
     * @param string $option An option to mark as selected.
     * @return void
     */
    protected function _setSelected($option)
    {

        if ($Option = $this->_getOption($option)) {
            foreach ($Option as $name => $value) {
                $this->options[$name]['selected'] = true;
                if (isset($this->options[$name]['count'])) {
                    $this->options[$name]['count'] += 1;
                } else {
                    $this->options[$name]['count'] = 1;
                }
            }
        }
    }

    /**
     * Unselect an option previously marked as selected.
     *
     * @param string $option An option to unselect.
     * @return void
     */
    protected function _unSetSelected($option)
    {
        if ($Option = $this->_getOption($option)) {
            foreach ($Option as $name => $value) {
             //$this->options[$name]['selected'] = false;
                unset($this->options[$name]['selected']);
                if (isset($this->options[$name]['count'])) {
                    $this->options[$name]['count'] -= 1;
                }
            }
        }
    }

    /**
     * Determine if an option rquires an argument.
     *
     * @param string $option An option to check.
     * @return bool
     */
    protected function _requiresArgument($option)
    {

        if ($Option = $this->_getOption($option)) {
            foreach ($Option as $name => $value) {
                if (isset($this->options[$name]['requires_argument'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Strip leading dashes from an option.
     *
     * @param $option An option sring to remove leading dashes from.
     * @return Modified option if applicable, or original option.
     */
    protected function _strip_leading_dashes($option)
    {
        return preg_replace('/^-+/', '', $option);
    }

    /**
     * Decluster a clustered token / option string.
     *
     * If a token (an option string) is a clustered set of short opts,
     * this method will decluster them.  It takes into account a few
     * things:
     *
     * 1)  Do any of the option in this cluster *require* an argument?
     *   If so, everything after it is considered to be an argument.
     *
     * 2)  Do any of the options *accept* but not require an argument?
     *   If so, everything after is an arg *unless* the next char in the
     *   string is itself a valid option.
     *
     * 3)  Appropriately handle an inline '=' within a cluster.
     *
     * @param string $tokens A string of clutered short opts.
     * @return array An array of declustered short opts in their dashed form.
     */
    protected function _deCluster($tokens)
    {

        $tokens = substr($tokens, 1); // remove leading dash
        $declustered = str_split($tokens);
        foreach ($declustered as $key => $opt) {
         // if it requires arg, then everything after it is arg
            if ($this->_requiresArgument("-".$opt)) {
                $flag = 1;
            }

         // if it only accepts arg, everything after is arg unless next is also an option
            if ($this->_acceptsArgument("-".$opt)) {
                if (isset($declustered[$key + 1]) && !$this->_isOption("-".$declustered[$key + 1]) || isset($flag)) {
                    $arg = implode('', array_slice($declustered, $key + 1));
                    $arg = !empty($arg) ? $arg : null;
                    if ($arg[0] === "=") {
                        $arg = substr($arg, 1);
                    }
                    $result = array_slice($declustered, 0, $key + 1);
                    array_walk($result, function(&$opt) {
                        $opt = "-".$opt;

                    });
                    if (isset($arg)) {
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


    /**
     * Determines if an option string is prohibited.
     *
     * An option may not literally contain '='.
     * An option may not be literally '-'.
     * @todo - Many GNU Core utilities allow '-' to signify STDIN
     *   Should we allow that?
     *
     * @param string $option The option string to check.
     * @return bool
     */
    private function _prohibited($option)
    {
        if (strstr($option, "=") || $option === "-") {
            return true;
        }
        return false;
    }

    /**
     * Guesses an option that may have been typed incorrectly.
     *
     * Utilizes the leveshtein algorithm to make a guess if an
     * option doesn't exist, ala `git`
     *
     * @param string $given A user supplied option string to analyze.
     * @return string The best guess from available options.
     */
    protected function _guessOption($given)
    {

        $options = $this->all();
        foreach ($options as $option) {
            $lev = levenshtein($given, $option);
            if (!isset($floor)) {
                $floor = $lev;
            }
            if ($lev <= $floor) {
                $floor = $lev;
                $best_guess = $option;
            }
        }
        return $best_guess;
    }

    /**
     * Returns a help page or a help page for a specific option.
     *
     * Sets the exit status if supplied.
     *
     * @param string $option Optional option to supply for specific help.
     * @param int $err_code Optional exit status to send.
     */
    protected function _help($option = null, $err_code = 0)
    {

        if ($this->NOHELP) {
            return true;
        }

        $title = isset($this->title) ? $this->title : $this->script;
        echo PHP_EOL.$title.PHP_EOL.PHP_EOL;

        if (isset($option)) {
            $single_option_help = true;
            if (!$options = $this->_getOption($option)) {
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
        if (isset($this->help)) {
            echo $this->help;
        } else {
            foreach ($options as $option => $array) {
                $aliases = '';
                if (array_key_exists('aliases', $array)) {
                    foreach ($array['aliases'] as $alias) {
                        $aliases .= "|".$alias;
                    }
                }

                $description = isset($array['description']) ? $array['description'] : "No description available.";
                echo "  ".$option.$aliases.PHP_EOL;
                echo "     ".$description.PHP_EOL.PHP_EOL;
            }
            echo PHP_EOL;
            if (isset($single_option_help)) {
                echo "More information may be available in the full help page.  Try ".$this->script." --help".PHP_EOL.PHP_EOL;
            }
        }
        if (defined('UNIT_TESTING')) {
            return "Dying with $err_code";
        } else {
            die($err_code);
        }
    }

    /**
     * Sets an Options argument.
     *
     * @param string $option The option to set an argument for.
     * @param string $arg The argument to assign to the option.
     * @return void
     */
    protected function _setOptArg($option, $arg)
    {

        if ($Option = $this->_getOption($option)) {
            foreach ($Option as $name => $value) {
                $this->options[$name]['arg'] = $arg;
            }
        }
    }

    /**
     * Check if an option array has a property/key.
     *
     * @param string $property The property/key to check for.
     * @param string $option The option string in question.
     * @return bool
     */
    protected function _optionHas($property, $option)
    {
        
        $Option = $this->_getOption($option);
        foreach ($Option as $option => $array) {
            if (isset($array[$property]) && $array[$property]) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validates the options supplied against the options parameters.
     *
     * This displays validation error messages to STDOUT and invokes
     * _help() if validation fails.
     *
     * @return void
     */
    protected function _validate()
    {

        foreach ($this->options as $option => $array) {
         // required options must be set
            if ($this->_optionHas('required', $option) && !$this->_optionHas('selected', $option)) {
                $msgs[] = "Option $option is required but not selected.\n";
            }

         // incompatible options must not be selected together
            if ($this->_optionHas('incompatible_with', $option) && $this->_optionHas('selected', $option)) {
                foreach ($this->options[$option]['incompatible_with'] as $inc_option) {
                    if ($this->_optionHas('selected', $inc_option)) {
                        $msgs[] = "Option $inc_option is incompatible with option $option\n";
                    }
                }
            }
            
         // options which require arguments must have an argument
            if ($this->_optionhas('requires_argument', $option) && $this->_optionHas('selected', $option) && !$this->_optionHas('arg', $option)) {
                $msgs[] = "Option $option requires an argument.\n";
            }
        }
        if (isset($msgs)) {
            foreach ($msgs as $msg) {
                echo "[ERROR] ".$msg.PHP_EOL.PHP_EOL;
            }
            $this->_help(null, 1);
        }
    }

    /**
     * Normalizes the input so it can be parsed appropriately.
     *
     * This handles things like option arguments and end-of-options,
     * help invocation, detecting and declustering short options,
     * and handling arguments.
     *
     * @return void
     */
    protected function _normalize()
    {

        $end_of_options = false;
        foreach ($this->given as $key => $token) {
            if ($token === "--help" && !$end_of_options) {
                if (isset($this->given[$key + 1])) {
                    $with = $this->given[$key + 1];
                    $this->_help($with, 0);
                }
                $this->_help(null, 0);
            }

            if ($token === "--") {
                if (!isset($previous) || !$this->_requiresArgument($previous)) {
                    $end_of_options = true;
                }
            }
            
            if (!$end_of_options) {
                if ($this->_isCluster($token)) {
                    foreach ($this->_deCluster($token) as $dtoken) {
                        $this->_normalized[] = $dtoken;
                    }
                } else {
                    if (strstr($token, "=") && $this->_looksLikeOption($token)) {
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

    /**
     * Add dashes to an option string if they don't exist.
     *
     * Checks if it's long or short to add the right
     * amount of dashes.
     *
     * @param sring $option The option to modify.
     * @return string A modified option string if applicable or
     * the original option string.
     */
    protected function _addDashes($option)
    {

        if ($option[0] !== "-") {
            if (strlen($option) > 1) {
                $option = "--".$option;
            } else {
                $option = "-".$option;
            }
        }
        return $option;
    }

    /**
     * Splits a long option that has an argument.
     *
     * @param string $token A string representing a long opt with argument.
     * @return null|array An array containing the option and it's arugmnt if
     * applicable.
     */
    protected function _splitLongOpt($token)
    {

     // break it into array and read by char to allow --corgeCorge if --corge accepts or requires argument
     // @todo - consider throwing an exception / error / hint in the edge-case scenario that --corgeCorge is actually an option !

        $splitToken = str_split($token);
        $arg = '';
        $maybe_option = '';

     // remove "--" to avoid false-positive on "--" end-of-options
        unset($splitToken[0]);
        unset($splitToken[1]);

        foreach ($splitToken as $char) {
            if (isset($opt)) {
                $arg .= $char;
                continue;
            }
            $maybe_option .= $char;
            if ($this->_isOption("--".$maybe_option)) {
                $opt = "--".$maybe_option;
            }
        }
        if (isset($opt)) {
            return ['opt' => $opt, 'arg' => $arg];
        }
        //return false;
        return null;
    }

    /**
     * Add an option to an Options object.
     *
     * @param string $option Long or short option.
     * @return Options Modified Options object.
     */
    public function add($option)
    {

        $option = isset($option) ? $this->_addDashes($option) : null;

        if ($this->_prohibited($option)) {
            throw new Exception("Attmepted to add prohibited option $option");
         // @todo friendlier error handling here
        }

        $this->_current_option = $option;
        $this->options[$option] = [];
        return $this;
    }

    /**
     * Add an alias to an Options object.
     *
     * @param string $alias Long or short option alias.
     * @return Options Modified Options object.
     */
    public function alias($alias)
    {

        $alias = isset($alias) ? $this->_addDashes($alias) : null;

        if ($this->_prohibited($alias)) {
            throw new Exception("Attmepted to add prohibited option $alias");
        }

        $this->options[$this->_current_option]['aliases'][] = $alias;
        return $this;
    }

    /**
     * Set an option as required.
     *
     * @return Options Modified Options object.
     */
    public function required()
    {

        $this->options[$this->_current_option]['required'] = true;
        return $this;
    }

    /**
     * Set an option as accepting of argument.
     *
     * @return Options Modified Options object.
     */
    public function acceptsArgument()
    {

        $this->options[$this->_current_option]['accepts_argument'] = true;
        return $this;
    }

    /**
     * Set an option as requiring an argument.
     *
     * @return Options Modified Options object.
     */
    public function requiresArgument()
    {

        $this->options[$this->_current_option]['accepts_argument'] = true;
        $this->options[$this->_current_option]['requires_argument'] = true;
        return $this;
    }

    /**
     * Set an option's description.
     *
     * Used in help page if help string not overridden.
     *
     * @return Options Modified Options object.
     */
    public function description($desc = null)
    {

        $this->options[$this->_current_option]['description'] = $desc;
        return $this;
    }

    /**
     * Override the help page entirely.
     *
     * You lose the ability to have baked in support for
     * individual options help, ie - script --help --foo
     * to get only "--foo" help info.
     *
     * @param string $help A full help page as a string.
     * @return Options Modified Options object.
     */
    public function overrideHelp($help)
    {

        if (isset($help)) {
            $this->help = $help;
        }
        return $this;
    }

    /**
     * Set script title.
     *
     * Used in help page.
     *
     * Defaults to $argv[0].
     *
     * @param string $title A script title.
     * @return Options Modified Options object.
     */
    public function title($title)
    {

     // defaults to script name in help

        if (isset($title)) {
            $this->title = $title;
        }
        return $this;
    }

    /**
     * Set an option or options as being incompatible with another option.
     *
     * @param string|array $options An option or array of options.
     * @return Options Modified Options object.
     */
    public function incompatibleWith($options)
    {

        if (!is_array($options)) {
            $options = [$options];
        }
        foreach ($options as $option) {
            $Option = $this->_getOption($option);
            foreach ($Option as $option => $array) {
                $inc_options[] = $option;
            }
        }

        $this->options[$this->_current_option]['incompatible_with'] = $inc_options;
        return $this;
    }

    /**
     * Get all available options.
     *
     * @param string $type A type of option.  You can pass:
     * 'alias', or 'option'.  Defaults to all.
     * @return array of options.
     */
    public function all($type = 'both')
    {

        $type = strtolower($type);

        foreach ($this->options as $option => $array) {
            if (!strstr('alias', $type)) {
                $all[] = $option;
            }
            if (!strstr('option', $type)) {
                if (array_key_exists('aliases', $array)) {
                    foreach ($array['aliases'] as $alias) {
                        $all[] = $alias;
                    }
                }
            }
        }
        return $all;
    }

    /**
     * Get a specific option.
     *
     * @param string $option An option to get.
     * @return null|array An options array for the option or null.
     */
    public function get($option = null)
    {

        if (isset($option)) {
            $option = $this->_addDashes($option);
            return $this->_getOption($option);
        }
     //return $this->options;
        return null;
    }
    
    /**
     * Get options that have been selected by the end user.
     *
     * @return array Array of selected options.
     */
    public function getSelected()
    {

        $selected = [];
        foreach ($this->options as $option => $array) {
            if (isset($array['selected']) && $array['selected']) {
                $selected[] = $option;
            }
        }
        return $selected;
    }

    /**
     * Get the argument for a specific option, or all option arguments.
     *
     * @param $option Optional option.
     * @return array Array of option arguments.
     */
    public function getOptArg($option = null)
    {

        $option = isset($option) ? $this->_addDashes($option) : null;

        $optargs = [];
        if (isset($option)) {
            $Option = $this->_getOption($option);
            foreach ($Option as $name => $array) {
                if (isset($array['arg']) && !empty($array['arg'])) {
                    $optargs[$name] = $array['arg'];
                }
            }
        } else {
            foreach ($this->options as $option => $array) {
                if (isset($array['arg']) && !empty($array['arg'])) {
                    $optargs[$option] = $array['arg'];
                }
            }
        }
        return $optargs;
    }
    
    /**
     * Get how many times an option was invoked by the end user.
     *
     * Useful for checking verbosity levels for options like '-v' for instance.
     *
     * @param string $option The option to check count for.
     * @return int How many times the option was invoked.
     */
    public function getCount($option)
    {
    
        if (isset($option)) {
            $option = $this->get($this->_addDashes($option));
            foreach ($this->options as $option => $array) {
                if (isset($array['count'])) {
                    return (int)$array['count'];
                }
            }
        }
        return (int)0;
    }

    /**
     * Gets all script arguments.
     *
     * These are all tokens passed via argv that are not options,
     * and are not captured by an option as option arguments.
     *
     * @return array An array of script arguments.
     */
    public function getArguments()
    {
        $arguments = isset($this->arguments) ? $this->arguments : null;
        return $arguments;
    }

    /**
     * Invoke help page.
     *
     * This is used when you want to manually invoke a help page.
     * The built-in validation handles this in most cases, but you
     * might have a desire to manually invoke the help page at any
     * time.
     *
     * You can optionally pass an exist status as the second argument.
     * The default exit status is 0.
     *
     * @param $option Optional option to supply specific help for.
     * @param int $err_code Optional exit status to return to shell.
     * @return void
     */
    public function help($option = null, $err_code = 0)
    {
        $this->_help($option, $err_code);
    }

    /**
     * Some magic to handle 'fuzzy' calling of methods.
     *
     * @todo Seriously consider removing this.  It seemed
     * cute when I wrote it, but I kind of hate it in that it
     * literally encourages incorrect usage.
     *
     * @param string $name Possible method name.
     * @param @arguments Arguments passed to possible method.
     * @return void
     */
    public function __call($name, $arguments)
    {

        $arguments = empty($arguments) ? null : implode(', ', $arguments);

        if (preg_match('/count/i', $name)) {
            return $this->getCount($arguments);
        }
        if (preg_match('/optargs?/i', $name)) {
            return $this->getOptArg($arguments);
        }
        if (preg_match('/selected/i', $name)) {
            return $this->getSelected();
        }
        if (preg_match('/(getargs|arguments)/i', $name)) {
            return $this->getArguments();
        }
        if (preg_match('/(trigger|call|show|display)_?[hH]elp/i', $name)) {
            return $this->help();
        }
    }

    /**
     * Parse options.
     *
     * This serves to complete the method chaining of your Options object.
     * This must be called last when building an Options object.
     *
     * It handles a lot of the weirdness of determining whether user supplied
     * input jives with the structure of your constructed Options object.
     *
     * It also handles storing tokens as script arguments that are not captured
     * as script options, or option arguments.
     *
     * The last thing it does is invoke the _validate() method to make sure that
     * the user supplied input is appropriate.
     *
     * @return void
     */
    public function parse()
    {

        $end_of_options = false;
        $this->_normalize();
        if (!isset($this->_normalized)) {
            return;
        }
        foreach ($this->_normalized as $key => $token) {
            if ($token === "--") {
                if (!isset($previous) || !$this->_requiresArgument($previous)) {
                    $end_of_options = true;
                    $this->_setSelected($token);
                }
            }
            
            if (!$end_of_options) {
                if ($this->_looksLikeOption($token)) {
                    if (!$this->_isOption($token)) {
                        if (isset($previous) && $this->_acceptsArgument($previous)) {
                            $this->_setOptArg($previous, $token);
                        } else {
                         // this looks like an option but it's not
                         // let's see if it's something like --corgeCorge where --corge is opt and Corge is arg
                            if ($optarg = $this->_splitLongOpt($token)) {
                                $this->_setSelected($optarg['opt']);
                                if (!empty($optarg['arg'])) {
                                    $this->_setOptArg($optarg['opt'], $optarg['arg']);
                                }
                                continue;
                            } else {
                                $this->_help($token, 1);
                            }
                        }
                    } else {
                     // it looks like an option and it is an option
                        $this->_setSelected($token);
                    }
                }
                if (isset($previous) && $this->_requiresArgument($previous)) {
                    $this->_setOptArg($previous, $token);

                 // this is so that if the argument given is an option and was previously called legitimately, we do not unset it
                    if ($this->getCount($token) === 1) {
                        $this->_unSetSelected($token);
                    }

                } elseif (isset($previous) && $this->_acceptsArgument($previous) && !$this->_isOption($token)) {
                    $this->_setOptArg($previous, $token);
                    $this->_unSetSelected($token);
                } else {
                    if (!$this->_isOption($token)) {
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
        if ($end_of_options) {
            foreach ($this->arguments as $key => $argument) {
                if ($argument === "--") {
                    unset($this->arguments[$key]);
                    $this->arguments = array_values($this->arguments);
                    break;
                }
            }
        }
        $this->_validate();
    }
}
