<?php
/**
This File was developed by Stefan Warnat <vtiger@stefanwarnat.de>

It belongs to the Workflow Designer and must not be distributed without complete extension

@version 1.2
@lastupdate 2013-07-09

 * # 1.2
Logical Operators && and ||
 * Performance Improvements
 * # 1.14
- fix one variable as array bug
 * # 1.131
- introduce !==
# 1.13
- introduce ===
- introduce !
# 1.11
 *  - [FIX] Array [] Operator
 * # 1.1
 *  - Introduce Arrays, foreach
 * # 1.03
 *	- fix a problem in "if" condition
 * # 1.02
 *  - add Number handling
 * # 1.01
 *  - fix bug with If construct
 **/
namespace ComplexCondition;

class ExpressionParser {
    private $_expression = false;
    /**
     * @var \Workflow\VTEntity $_context
     */
    private $_context = false;
    private $_tokens = false;

    private $_currentPos =0;
    private $_intend = 0;

    private $_return = null;

    private $_variables = array();
    private $_stop = false;

    private $_debug = false;
    private $_disableFunctionlist = false;
    private $_loopLimit = 150;

    private $_NegotiateCompare = false;

    private static $EvalAllowed = -1;

    public static $INSTANCE = false;

    public static $WhitelistPHPfunctions = array(
        'json_encode',
        'nl2br',
        'date_default_timezone_get',
        'date_default_timezone_set',
        "round",
        "md5",
        "rand",
        "implode",
        "substr",
        "floor",
        "ceil",
        "explode",
        "microtime",
        "date",
        "time",
        "sha1",
        "hash",
        "intval",
        "floatval",
        "ucfirst",
        "number_format",
        'is_numeric',
        'sqrt',
        'pow',
        //'substr_count'
    );

    protected $_nextByReference = false;

    private $StaticReference = array();

    private $_startTime = 0;
    /**
     * @param $expression
     * @param $context
     * @param bool $debug If set, complex debug output will be shown
     */
    public function __construct($expression, &$context, $debug = false, $checkSyntax = true) {
        $this->_expression = str_replace(array("<p>", "</p>", "<div>", "</div>"), "", ($expression));
        $this->_context = $context;
        $this->_debug = $debug;

        $this->_variables["env"] = $context->getEnvironment();

        self::$INSTANCE = $this;

        if($checkSyntax == true) {
            $SyntaxCheck = $this->checkSyntax();

            if($SyntaxCheck !== false) {
                throw new \Exception($expression." - Expression Syntax Error Line ".$SyntaxCheck[1].".\n ".$SyntaxCheck[0], E_NONBREAK_ERROR);
                $this->_expression = "return '';";
            }
        }
    }

    public function getContext() {
        return $this->_context;
    }

    protected function isEvalAllowed() {
        if(self::$EvalAllowed !== -1) {
            return self::$EvalAllowed;
        }

        if(!function_exists("ini_get")) {
            self::$EvalAllowed = false;
            return self::$EvalAllowed;
        }

        if(ini_get("suhosin.executor.disable_eval") == "1") {
            self::$EvalAllowed = false;
            return self::$EvalAllowed;
        }

        $check = ini_get("disable_functions")." ".ini_get("suhosin.executor.func.blacklist");

        if(strpos($check, "eval") !== false) {
            self::$EvalAllowed = false;
            return self::$EvalAllowed;
        }

        return true;
    }
    /**
     * @param $code
     * @return array|bool
     * @author nicolas dot grekas+php at gmail dot com ¶
     */
    public function checkSyntax() {
        $code = trim($this->_expression);
        $code = html_entity_decode($code, ENT_NOQUOTES, "UTF-8");
        $checkCode = $code;
        $braces = 0;
        $inString = 0;

        // First of all, we need to know if braces are correctly balanced.
        // This is not trivial due to variable interpolation which
        // occurs in heredoc, backticked and double quoted strings
        foreach (token_get_all('<?php ' . $code) as $token)
        {
            if (is_array($token))
            {
                switch ($token[0])
                {
                    case T_CURLY_OPEN:
                    case T_DOLLAR_OPEN_CURLY_BRACES:
                    case T_START_HEREDOC: ++$inString; break;
                    case T_END_HEREDOC:   --$inString; break;
                }
            }
            else if ($inString & 1)
            {
                switch ($token)
                {
                    case '`':
                    case '"': --$inString; break;
                }
            }
            else
            {
                switch ($token)
                {
                    case '`':
                    case '"': ++$inString; break;

                    case '{': ++$braces; break;
                    case '(': ++$braces; break;
                    case '}':
                    case ')':
                        if ($inString) --$inString;
                        else
                        {
                            --$braces;
                            if ($braces < 0) break 2;
                        }

                        break;
                }
            }
        }

        // Display parse error messages and use output buffering to catch them
        $inString = @ini_set('log_errors', false);
        $token = @ini_set('display_errors', true);
        ob_start();

        // If $braces is not zero, then we are sure that $code is broken.
        // We run it anyway in order to catch the error message and line number.

        // Else, if $braces are correctly balanced, then we can safely put
        // $code in a dead code sandbox to prevent its execution.
        // Note that without this sandbox, a function or class declaration inside
        // $code could throw a "Cannot redeclare" fatal error.

        #$code = html_entity_decode(htmlspecialchars_decode($code, ENT_NOQUOTES), ENT_NOQUOTES, "UTF-8");

        $code = "if(0){{$code};\n}";

        // If eval not allowed, don't execute this
        if(!$this->isEvalAllowed()) {
            if($braces) {
                return array('syntax error: unexpected $end', 0);
            }
        }

        if (false === eval($code))
        {

            if ($braces) $braces = PHP_INT_MAX;
            else
            {
                // Get the maximum number of lines in $code to fix a border case
                false !== strpos($code, "\r") && $code = strtr(str_replace("\r\n", "\n", $code), "\r", "\n");
                $braces = substr_count($code, "\n");
            }

            $code = ob_get_clean();
            $code = strip_tags($code);

            // Get the error message and line number
            if (preg_match("'syntax error, (.+) in .+ on line (\d+)'s", $code, $code))
            {
                $code[2] = (int) $code[2];
                $code = $code[2] <= $braces
                    ? array($code[1], $code[2])
                    : array('unexpected $end' . substr($code[1], 14), $braces);
            }
            else $code = array('syntax error', 0);

            $oldHandler = set_error_handler('var_dump', 0);
            @$undef_var;
            if(!empty($oldHandler)) {
                set_error_handler($oldHandler);
            }

        }
        else
        {
            ob_end_clean();
            $code = false;
        }

        /*if($code === false) {
            if(strpos($checkCode, 'return') === false) {
                $code = array('No return statement', 0);
            }
        }*/

        @ini_set('display_errors', $token);
        @ini_set('log_errors', $inString);

        return $code;

    }
    public function setEnvironment($env) {
        $this->_variables["env"] = $env;
    }
    public function run() {
        $this->_startTime = time();

        $this->_tokens = $this->_getTokens();

        // Solange abarbeiten, bis Return erreicht oder Ende der Ausgabe
        do {
            $this->_nextCommand();
            $this->checkRuntime();
        } while($this->_currentPos < count($this->_tokens) && $this->_stop == false);

        if(!empty($this->_variables["env"])) {
            $this->_context->loadEnvironment($this->_variables["env"]);
        }
    }

    private function checkRuntime() {
        if(time() - $this->_startTime > 30) {
            throw new ExpressionException('Runtime too high. Please check Exception!');
        }
    }

    /* Internal functions */
    private function _debug($text) {
        if($this->_debug == true) {
            echo "<pre>";
            echo(str_pad($this->_currentPos, 3, ' ', STR_PAD_LEFT).' '.str_repeat("<span style='color:#eee'>|</span>   ", $this->_intend). $text)."\n";
            echo "</pre>";
        }
    }
    private function _getTokens() {
        $functions = token_get_all("<?php ".$this->_expression);

        $tokens = array();
        for($a = 0; $a < count($functions); $a++) {
            // remove Whitespaces
            if($functions[$a][0] == T_WHITESPACE ||$functions[$a][0] == T_OPEN_TAG) continue;
            $tokens[] = $functions[$a];
        }

        return $tokens;
    }

    public function getReturn() {
        if($this->_return === null) {
            return "";
            # removed the error, because there are too much problems
        }

        return $this->_return;
    }
    public function _setReturn($return) {
        $this->_return = $return;
        $this->_stop = true;
    }
    private function _next($increase = true) {
        $this->checkRuntime();

        if($increase == true && !is_string($this->_tokens[$this->_currentPos])) {
            //$this->_debug("[ ] _next(".($increase?"true":"false").") [".$this->_currentPos."]");
            $this->_intend++;

            //$this->_debug("[".($this->_currentPos)."] ".token_name($this->_tokens[$this->_currentPos][0]));

            $this->_intend--;
        }

        if(count($this->_tokens) <= $this->_currentPos) {
            return -1;
        }

        #//$this->_debug("[-] _next");
        if($increase == true) {
            return $this->_tokens[$this->_currentPos++];
        } else {
            return $this->_tokens[$this->_currentPos];
        }
    }

    private function _nextCommand() {
        $return = "";
        //$this->_debug("[+] _nextCommand");
        $this->_intend++;
        $nextToken = $this->_next();

        if(is_string($nextToken)) {
            //$this->_debug("[+] _runString \" ".$nextToken." \"");
        } else {
            //$this->_debug("[+] _runCommand (".token_name($nextToken[0]).") [".htmlentities($nextToken[1])."]");
        }
        $this->_intend++;

        $this->checkRuntime();

        switch($nextToken[0]) {
            case ";":
#				echo "Found ;";

                #return $return;
                break;
            case T_IF:
                $return = $this->_run_if();
                break;
            case T_FOR:
                $this->_run_for();
                break;
            case T_FOREACH:
                $this->_run_foreach();
                break;
            case T_ARRAY:
                $return = $this->_getArrayValues();
                break;
            case T_VARIABLE:
                if($this->_nextByReference == true) {
                    $byRef = true;
                    $this->_nextByReference = false;
                } else {
                    $byRef = false;
                }
                $varName = substr($nextToken[1], 1);
                $tmpToken = $this->_next(false);

                $pointerSet = false;
                $varPointer = false;
                $addItem = false;

                if($tmpToken == "[") {
                    $keys = array();
                    //$this->_debug('Array found');

                    while($this->_next(false) == "[") {
                        $this->_currentPos++;
                        if($this->_next(false) == "]") {
                            $this->_currentPos++;
                            $addItem = true;
                            break;
                        }
                        $keys[] = $this->_getArrayKey();
                    }

                    $pointerSet = true;

                    unset($varPointer);

                    if(is_array($this->_variables[$varName])) {
                        $varPointer = &$this->_variables[$varName];
                    } else {
                        $varPointer = $this->_variables[$varName];
                    }

                    # Array Struktur herabgehen
                    foreach($keys as $key) {
                        if(!is_array($varPointer) && !is_string($varPointer)) {
                            $varPointer = array();
                        }
                        $pointerSet = true;

                        if(!is_string($varPointer)) {
                            $varPointer = &$varPointer[$key];
                        } else {
                            if(is_numeric($key)) {
                                $varPointer = $varPointer[$key];
                            } else {
                                unset($varPointer);
                                $varPointer = '';
                            }
                        }
                    }

                    // Handle [] suffix on array
                    if($addItem == true) {
                        $TMPpointer = &$varPointer;
                        if(!is_array($TMPpointer)) {
                            $varPointer = array();
                        }
                        array_push($TMPpointer, null);
                        $pointerSet = true;
                        $varPointer = &$TMPpointer[count($varPointer) - 1];
                    }
                }

                $tmpToken = $this->_next(false);

                if((!is_array($tmpToken) && $tmpToken == "=") ||  (is_array($tmpToken) && in_array($tmpToken[0], array(T_PLUS_EQUAL, T_MINUS_EQUAL, T_INC, T_DEC, T_CONCAT_EQUAL, T_MUL_EQUAL)))) {
                    // Wird eine Zuweisung
                    //$this->_debug(" #  prepare Assignment [".$varName."]");
                    $this->_currentPos++;

                    if(!is_array($tmpToken) || !in_array($tmpToken[0], array(T_PLUS_EQUAL, T_MINUS_EQUAL, T_MUL_EQUAL))) {
                        $valueOfVariable = $this->_nextCommand();
                    }

                    if(is_array($tmpToken) && in_array($tmpToken[0], array(T_PLUS_EQUAL, T_MINUS_EQUAL, T_MUL_EQUAL, T_INC, T_DEC, T_CONCAT_EQUAL))) {

                        if(!$pointerSet) {
                            if(isset($this->_variables[$varName])) {
                                $startvalue = $this->_variables[$varName];
                                //$this->_debug(" #  _getVariable [".$varName."] = ".$startvalue);
                            }
                        } else {
                            $startvalue = $varPointer;
                            //$this->_debug(" #  _getArrayVariable [".$varName."][".implode("][", $keys)."] = ".$startvalue);
                        }

                    } else {
                        $startvalue = 0;
                    }

                    if(is_array($tmpToken) && $tmpToken[0] == T_MINUS_EQUAL) {
                        $valueOfVariable = $startvalue - $this->_nextCommand();
                    } elseif(is_array($tmpToken) && $tmpToken[0] == T_PLUS_EQUAL) {
                        $valueOfVariable = $startvalue + $this->_nextCommand();
                    } elseif(is_array($tmpToken) && $tmpToken[0] == T_MUL_EQUAL) {
                        $valueOfVariable = $startvalue * $this->_nextCommand();
                    } elseif(is_array($tmpToken) && $tmpToken[0] == T_INC) {
                        $valueOfVariable = $startvalue + 1;
                    } elseif(is_array($tmpToken) && $tmpToken[0] == T_DEC) {
                        $valueOfVariable = $startvalue - 1;
                    } elseif(is_array($tmpToken) && $tmpToken[0] == T_CONCAT_EQUAL) {
                        $valueOfVariable = $startvalue . $valueOfVariable;
                    }

                    if($varName == "disableFunctionlist" && $valueOfVariable == "1") {
                        $this->_disableFunctionlist = true;
                    }
                    if($varName == "loopLimit") {
                        $this->_loopLimit = intval($valueOfVariable);
                    }

                    if(!$pointerSet) {
                        //$this->_debug(" #  _setVariable [".$varName."] = ".serialize($valueOfVariable));
                        $this->_variables[$varName] = $valueOfVariable;
                    } else {
                        //$this->_debug(" #  _setArrayVariable [".$varName."][".implode("][", $keys)."] = ".serialize($valueOfVariable));
                        $varPointer = $valueOfVariable;
                    }

                } elseif(isset($this->_variables[$varName]) || $pointerSet) {
                    if(!$pointerSet) {

                        if($byRef == true) {
                            $value = &$this->_variables[$varName];
                        } else {
                            $value = $this->_variables[$varName];
                        }

                        //$this->_debug(" #  _getVariable ".($byRef == true?"&":"")."[".$varName."] = ".json_encode($value));
                    } else {
                        if($byRef == true) {
                            $value = &$varPointer;
                        } else {
                            $value = $varPointer;
                        }
                        //$this->_debug(" #  _getArrayVariable ".($byRef == true?"&":"")."[".$varName."][".implode("][", $keys)."] = ".$value);
                    }

                    $return = $value;
                } elseif(is_array($tmpToken) && $tmpToken[0] == T_OBJECT_OPERATOR) {
                    //$this->_debug(" #  _getValue from reference");

                    $this->_currentPos++;
                    $moduleToken = $this->_next(false);

                    if($varName != "current_user") {
                        /**
                         * @var $reference VTEntity
                         */
                        $reference = $this->_context->getReference($moduleToken[1], $varName);
                    } else {
                        global $current_user;
                        /**
                         * @var $reference VTEntity
                         */
                        $reference = VTEntity::getForId($current_user->id, $moduleToken[1]);
                    }

                    $this->_currentPos++;
                    $tmpToken = $this->_next(false);
                    if(is_array($tmpToken) && $tmpToken[0] == T_OBJECT_OPERATOR) {
                        $this->_currentPos++;
                        $tmpToken = $this->_next(false);

                        $this->_currentPos++;
                        if($reference instanceof VTEntity) {
                            $return = $reference->get($tmpToken[1]);
                        } else {
                            #error_log("No Reference defined for $".$varName."->".$moduleToken[1]);
                            throw new ExpressionException("No Reference defined for $".$varName."->".$moduleToken[1]);
                        }
                    } else {
                        throw new ExpressionException("Error in ExpressionParser near $".$varName."->".$moduleToken[1]);
                    }

                } else{
                    $return = $this->_context->get($varName);
                    //$this->_debug(" #  _getValue $varName = ('".$return."')");
                }

                $tmpToken = $this->_next(false);

                if((is_array($tmpToken) && in_array($tmpToken[0], array(T_IS_SMALLER_OR_EQUAL, T_IS_EQUAL, T_IS_IDENTICAL, T_IS_NOT_IDENTICAL, T_IS_NOT_EQUAL, T_IS_GREATER_OR_EQUAL))) || $tmpToken == '<' || $tmpToken == '>') {
                    $startvalue = $return;

                    $next = $this->_next();

                    $value = $this->_nextCommand();

                    $return = $this->_compareOperator($startvalue, $value, $tmpToken);

                    //$this->_debug(" #  _ShortCompare ".$startvalue . " -- " . $value . ' '.serialize($return));

                    if($this->_NegotiateCompare == true) { $return = !$return; $this->_NegotiateCompare = false; }

                }
                break;
            case T_DNUMBER:
                $return = $nextToken[1];
                break;
            case T_STRING:
                if(defined($nextToken[1])) {
                    //$this->_debug(" #  Constant Found");
                    $return = constant($nextToken[1]);
                } elseif(function_exists("VT_".$nextToken[1])) {
                    //$this->_debug(" #  Custom function");
                    // Methodennamen werden umgeschrieben um nur bestimmte Funktionen zuzulassen
                    $methodName = "VT_".$nextToken[1];

                    $parameter = $this->_getFunctionParameters();
                    $return = call_user_func_array($methodName, $parameter);
                } elseif($this->_disableFunctionlist || in_array($nextToken[1], self::$WhitelistPHPfunctions) || substr($nextToken[1], 0, 3) == "str" || substr($nextToken[1], 0, 5) == "array" || substr($nextToken[1], 0, 3) == "wf_" || substr($nextToken[1], 0, 5) == "array") {
                    //$this->_debug(" #  Whitelisted PHP Function");
                    $parameter = $this->_getFunctionParameters();

                    $return = call_user_func_array($nextToken[1], $parameter);
                }

                //$this->_debug("    return Value '".substr(serialize($return), 0, 250)."'");
                break;
            case "-":
                #$this->_currentPos++;
                $nextValue = $this->_next();
                $return = -1 * $nextValue[1];
                break;
            case "&":
                $this->_nextByReference = true;
                break;
            case "(":
                $return = $this->_nextCommand();
                $this->_currentPos++;
                break;
            case ")":
                //$this->_debug("    RETURN Brackets ['".$return."']");
                $this->_intend--;
                $this->_intend--;
                return $return;
                break;
            case T_LNUMBER:
                $return = floatval($nextToken[1]);
                break;
            case T_CONSTANT_ENCAPSED_STRING:

                if((substr($nextToken[1], 0, 1) == "'" || substr($nextToken[1], 0, 1) == '"') && substr($nextToken[1], -1, 1) == substr($nextToken[1], 0, 1)) {
                    $nextToken[1] = trim($nextToken[1], "'".'"');
                }
                $return = $nextToken[1];
                break;
            case T_EMPTY:
                $parameter = $this->_getFunctionParameters();

                if(empty($parameter[0])) return true;

                break;
            case T_RETURN:
                $return = $this->_nextCommand();
                $this->_setReturn($return);
                break;
            case T_COMMENT;
                $return = $this->_nextCommand();
                break;
            case T_IS_NOT_IDENTICAL:
            case T_IS_IDENTICAL:
            case T_IS_EQUAL:
            case T_IS_GREATER_OR_EQUAL:
            case T_IS_IDENTICAL:
            case T_IS_NOT_EQUAL:
            case T_IS_NOT_IDENTICAL:
            case T_IS_SMALLER_OR_EQUAL:
                $this->_currentPos--;
                $return = false;
                break;
            default;
                break;
        }

        //$this->_debug("potential next: ".json_encode($this->_next(false)));

        if($this->_next(false) == ".") {
            $this->_currentPos++;
            //$this->_debug("[ ] _foundCombination");
            $return .= $this->_nextCommand();
        }

        $tmpToken = $this->_next(false);

        if(in_array($tmpToken, array("+", "-", "*", "/", "^"))) {
            //$this->_debug("    found Operation");

            $this->_currentPos++;
            $valueOfVariable = $this->_nextCommand();
            if(empty($return)) {
                $return = 0;
            }
            if(empty($valueOfVariable)) {
                $valueOfVariable = 0;
            }
            //$this->_debug("    run Operation ('return ".$return." ".$tmpToken." ".$valueOfVariable.";')");

            $return = eval('return '.$return.' '.$tmpToken.' '.$valueOfVariable.';');
        }

        if((is_array($tmpToken) && in_array($tmpToken[0], array(T_IS_SMALLER_OR_EQUAL, T_IS_EQUAL, T_IS_IDENTICAL, T_IS_NOT_IDENTICAL, T_IS_NOT_EQUAL, T_IS_GREATER_OR_EQUAL))) || $tmpToken == '<' || $tmpToken == '>') {
            $startvalue = $return;

            $next = $this->_next();

            $value = $this->_nextCommand();

            $return = $this->_compareOperator($startvalue, $value, $tmpToken);

            //$this->_debug(" #  _ShortCompare ".$startvalue . " -- " . $value . ' '.serialize($return));

            if($this->_NegotiateCompare == true) { $return = !$return; $this->_NegotiateCompare = false; }

        }
        $this->_intend--;

        $this->_intend--;
        //$this->_debug("[-] _nextCommand [".htmlentities(json_encode($return)."")."]");

        return $return;
    }

    public function setReference($variable, VTEntity $context) {
        $this->StaticReference[$variable] = $context;
    }
    public function setVariable($variable, $value) {
        $this->_variables[$variable] = $value;
    }

    private function _getArrayKey() {
        if($this->_next(false) == "[") {
            $this->_currentPos++;
        }
        $next = $this->_nextCommand();

        if($this->_next(false) == "]") {
            $this->_currentPos++;
        }

        return $next;
    }
    private function _getArrayValues() {
        //$this->_debug("[+] _getArrayValues");
        $this->_intend++;

        $parameter = array();
        do {
            $this->checkRuntime();
            $nextToken = $this->_next(false);

            if($nextToken == ";") {
                throw new ExpressionException("Error in ExpressionParser near. An ')' is missing!");
            }

            if($nextToken == "(" || $nextToken == "," || $nextToken == ")") {
                $this->_currentPos++;

                if($nextToken == "(" && $this->_next(false) == ")") {
                    $this->_intend--;
                    //$this->_debug("[-] _getArrayValues (No Args)");

                    $this->_currentPos++;
                    return array();
                }
            }

            if($nextToken == ")") {
                break;
            }

            $value = $this->_nextCommand();

            $nextToken = $this->_next(false);
            if(is_array($nextToken) && $nextToken[0] == T_DOUBLE_ARROW) {
                $setValue = $this->_next();
                $setValue = $this->_nextCommand();
                $parameter[$value] = $setValue;
            } else {
                $parameter[] = $value;
            }


            if($this->_currentPos > count($this->_tokens)) {
                break;
            }
        } while(1 == 1);

        $this->_intend--;
        //$this->_debug("[-] _getArrayValues (".json_encode($parameter).")");

        return $parameter;
    }
    private function _getFunctionParameters() {
        //$this->_debug("[+] __getFunctionParameters");
        $this->_intend++;

        $parameter = array();
        do {
            $nextToken = $this->_next(false);

            if($nextToken == ";") {
                throw new ExpressionException("Error in ExpressionParser near. An ')' is missing!");
            }

            if($nextToken == "(" || $nextToken == "," || $nextToken == ")") {
                $this->_currentPos++;

                if($nextToken == "(" && $this->_next(false) == ")") {
                    $this->_intend--;
                    //$this->_debug("[-] __getFunctionParameters (No Args)");

                    $this->_currentPos++;
                    return array();
                }
            }

            if($nextToken == ")") {
                break;
            }

            $parameter[] = $this->_nextCommand();

            if($this->_currentPos > count($this->_tokens)) {
                break;
            }
        } while(1 == 1);

        $this->_intend--;
        //$this->_debug("[-] __getFunctionParameters ('".implode("','", $parameter)."')");

        return $parameter;
    }

    private function _getValueString() {
        //$this->_debug("[+] _getValueString");
        $nextToken = $this->_next();

        switch($nextToken[0]) {

            #    case
        }
        //$this->_debug("[-] _getValueString");
    }

    private function _executeBlock() {
        $shouldClose = 1;
        do {
            $next = $this->_next(false);

            if($next == "{") {
                $shouldClose++;
            }
            if($next == "}") {
                $this->_currentPos++;
                $shouldClose--;

                if($shouldClose <= 0) {
                    break;
                }
                continue;
            }

            $this->_nextCommand();

            if($next === -1) {
                throw new \Exception("ERROR IN EXPRESSION ".$this->_expression, E_NONBREAK_ERROR);
            }
        } while(1==1);
    }

    private function _run_for() {
        //$this->_debug("[+] _run_for");
        $this->_intend++;

        $nextValue = $this->_next(false);

        if($nextValue != "(") {
            throw new ExpressionException("Error in ExpressionParser near for");
        }
        $this->_next();

        # Setter Init
        $nextValue1 = $this->_nextCommand();

        $this->_skip_useless();

        $startPos = $this->_currentPos;
        $counter = 0;
        $first = true;

        do {
            //$this->_debug("[ ] start new Loop");

            if($first === false) {
                //$this->_debug("    do Modification Part");
                $this->_currentPos = $startModifyPos;
                $this->_nextCommand();
                $this->_currentPos = $startCondPos;

            } else {

                $startCondPos = $this->_currentPos;

            }

            $finishCheck = $this->_nextCommand();
            /*
            $operator = $this->_next();
            $nextValue2 = $this->_nextCommand();
            */

            //$finishCheck = $this->_compareOperator($nextValue1, $nextValue2, $operator);

            if($finishCheck == false || $counter >= $this->_loopLimit) {
                break;
            }
            $counter++;

            if($first == false) {
                $this->_currentPos = $startExecPos;
            }

            $this->_skip_useless();

            if($first === true) {
                $startModifyPos = $this->_currentPos;

                //$this->_debug("skip operation for first run");

                do {
                    $this->_next();
                } while($this->_next(false) != "{");

                //$this->_debug("skip operation for first run END");
                $this->_next();
                $first = false;

                $startExecPos = $this->_currentPos;
                $first = false;
            }

            $this->_executeBlock();

        } while(true);


        $this->_intend--;
        //$this->_debug("[-] _run_for");
    }

    private function _run_foreach() {
        //$this->_debug("[+] _run_foreach");
        $this->_intend++;

        $nextValue = $this->_next(false);
        if($nextValue == "(") {
            $keyVar = false;
            $valueVar = false;

            $this->_currentPos++;
            $arrayVar = $this->_nextCommand();

            $as = $this->_next();
            $firstVar = $this->_next();

            if($firstVar != "{") {
                if(!is_array($firstVar) || $firstVar[0] != T_VARIABLE) {
                    if(is_array($firstVar)) {
                        throw new ExpressionException("Error in ExpressionParser near  as ".$firstVar[1].")");
                    } else {
                        throw new ExpressionException("Error in ExpressionParser near  as ".$firstVar.")");
                    }
                }
                $firstVar = $firstVar[1];

                $nextValue = $this->_next();
                if($nextValue == ")") {
                    $keyVar = false;
                    $valueVar = $firstVar;
                } elseif($nextValue[0] == T_DOUBLE_ARROW) {
                    $secondVar = $this->_next();
                    if(!is_array($secondVar) || $secondVar[0] != T_VARIABLE) {
                        if(is_array($secondVar)) {
                            throw new ExpressionException("Error in ExpressionParser near ".$firstVar." => ".$secondVar[1].")");
                        } else {
                            throw new ExpressionException("Error in ExpressionParser near ".$firstVar." => ".$secondVar.")");
                        }
                    }
                    $secondVar = $secondVar[1];

                    $keyVar = $firstVar;
                    $valueVar = $secondVar;

                    ## Skip )
                    $nextValue = $this->_next();
                }

                $next = $this->_next(false);
            } else {
                $next = "{";
                $this->_currentPos--;
            }

            if($next == "{") {
                $this->_currentPos++;

                //$this->_debug("[+] start ".count($arrayVar)." iteration [key=".$keyVar." value=".$valueVar."]");
                $startPos = $this->_currentPos;

                foreach($arrayVar as $runIndex => $runValue) {
                    $this->_currentPos = $startPos;
                    //$this->_debug("[+] run foreach Block [".$runIndex."=>".$runValue."]");
                    $this->_intend++;

                    if($keyVar !== false) {
                        $this->_variables[substr($keyVar, 1)] = $runIndex;
                    }
                    if($valueVar !== false) {
                        $this->_variables[substr($valueVar, 1)] = $runValue;
                    }
                    $this->_executeBlock();

                    $this->_intend--;
                    //$this->_debug("[-] run foreach Block");
                }
                //$this->_debug("[-] stop iteration");

                //$this->_debug("[-] run foreach Block");
            }

        }

        $this->_intend--;
        //$this->_debug("[-] _run_foreach");
    }

    private function _skip_useless() {
        $useless = array(" ", ";");
        //$this->_debug("[+] _skip_useless");
        $this->_intend++;

        while(in_array($this->_next(false), $useless)) {
            //$this->_debug("skip '".$this->_next(false)."'");
            $this->_next();
        }
        $this->_intend--;
    }
    private function _run_if() {
        //$this->_debug("[+] _run_IF");

        $nextValue = $this->_next(false);
        if($nextValue == "(") {
            $this->_currentPos++;
            $compareResult = $this->_compare();

            //$this->_debug("[ ] _run_IF Result: ".intval($compareResult));
            if($compareResult == true) {
                $next = $this->_next(false);

                if($next == "{") {
                    $this->_currentPos++;
                    $shouldClose = 1;

                    //$this->_debug("[+] run first Block");
                    $this->_intend++;
                    do {
                        $next = $this->_next(false);


                        if(empty($next)) {
                            throw new \Exception("ERROR IN EXPRESSION ".$this->_expression, E_EXPRESSION_ERROR);
                        }
                        if($next == "{") {
                            $shouldClose++;
                        }
                        if($next == "}") {
                            $this->_currentPos++;
                            $shouldClose--;
                            if($shouldClose <= 0) {
                                break;
                            }
                            continue;
                        }

                        $this->_nextCommand();

                        if($next === -1) {
                            throw new \Exception("ERROR IN EXPRESSION ".$this->_expression, E_EXPRESSION_ERROR);
                        }
                    } while(1==1);

                    $this->_intend--;
                    //$this->_debug("[-] run first Block");
                }

                // $this->_currentPos++;
                //               $this->_currentPos++;
                $nextToken = $this->_next(false);

                if($nextToken[0] == T_ELSE) {
                    $shouldClose = 0;
                    //$this->_debug("[ ] skip second Block");
                    $this->_intend++;
                    do {
                        $next = $this->_next();

                        if($next == "{") {
                            $shouldClose++;
                        }
                        if($next == "}") {
                            $shouldClose--;
                            if($shouldClose <= 0) {
                                break;
                            }
                        }
                        if($next === -1) {
                            throw new \Exception("ERROR IN EXPRESSION ".$this->_expression, E_EXPRESSION_ERROR);
                        }
                    } while(1==1);
                    $this->_intend--;
                }
            } else {
                $shouldClose = 0;
                //$this->_debug("[ ] skip first Block");
                $this->_intend++;
                do {
                    $next = $this->_next();


                    if($next == "{") {
                        $shouldClose++;
                    }
                    if($next == "}") {
                        $shouldClose--;
                        if($shouldClose <= 0) {
                            break;
                        }
                    }
                    if($next === -1) {
                        throw new \Exception("ERROR IN EXPRESSION ".$this->_expression, E_EXPRESSION_ERROR);
                    }
                } while(1==1);
                $this->_intend--;

                $nextToken = $this->_next(false);

                if($nextToken[0] == T_ELSE) {
                    $this->_currentPos++;
                    $nextToken = $this->_next(false);
                    if($nextToken == "{") {
                        $this->_currentPos++;
                    }

                    //$this->_debug("[+] run second Block");
                    $shouldClose = 1;
                    $this->_intend++;
                    do {
                        $next = $this->_next(false);

                        if($next === -1) {
                            throw new \Exception("ERROR IN EXPRESSION ".$this->_expression, E_EXPRESSION_ERROR);
                        }

                        if($next == "{") {
                            $shouldClose++;
                        }
                        if($next == "}") {
                            $this->_currentPos++;
                            $shouldClose--;
                            if($shouldClose <= 0) {
                                break;
                            }
                            continue;
                        }

                        $this->_nextCommand();

                    } while(1==1);

                    $this->_intend--;
                    //$this->_debug("[-] run second Block");
                }



            }
        }
        //$this->_debug("[-] _run_IF");
    }

    private function _compare() {
        //$this->_debug("[+] _compare");$this->_intend++;
        $compareResult = true;
        $negotiate = false;

        $nextValue = $this->_next(false);

        if($nextValue == "!") {
            while($nextValue == "!") {
                $this->_currentPos++;
                $negotiate = !$negotiate;
                $nextValue = $this->_next(false);
            }
        }

        if($nextValue == "(") {
            //$this->_debug("[ ] Found SubCompare");
            $this->_currentPos++;
            $compareResult = $this->_compare();
        } else {
            $this->_NegotiateCompare = $negotiate;
            $compareResult = $this->_nextCommand();
            if($negotiate === true && $this->_NegotiateCompare === false) {
                $negotiate = false;
            }
        }

        do {
            $operator = $this->_next(true);

            if($operator == ")") {
                break;
            }
            if($operator === -1 || $operator == "{") {
                throw new \Exception("ERROR IN EXPRESSION _compare", E_EXPRESSION_ERROR);
            }

            if(is_array($operator) && in_array($operator[0], array(T_BOOLEAN_AND, T_BOOLEAN_OR, T_LOGICAL_AND, T_LOGICAL_OR))) {
                //$this->_currentPos++;
                //$this->_debug("[ ] _compare Before ".$operator[1]." Logical Operator: ".serialize($compareResult));
                $skip = false;
                if(($operator[0] == T_BOOLEAN_AND || $operator[0] == T_LOGICAL_AND) && $compareResult == false && $negotiate == false) {
                    $skip = true;
                }
                if(($operator[0] == T_BOOLEAN_OR || $operator[0] == T_LOGICAL_OR) && $compareResult == true && $negotiate == false) {
                    $skip = true;
                }

                if($skip === true) {
                    $nextValue = $operator;
                    if($nextValue != ")") {
                        //var_dump($this->_currentPos, $nextValue);echo '<br/>';
                        $counter = 0;
                        $shouldClose = 1;
                        while($shouldClose != 0) {
                            $nextValue = $this->_next(false);
                            $this->_currentPos++;
                            if($nextValue == '(') {
                                $shouldClose++;
                            }
                            if($nextValue == ')') {
                                $shouldClose--;
                            }
                        }
                    }

                    //$this->_debug("[-] _compare Skip Next check because OR Operator Result: ".serialize($compareResult));$this->_intend--;
                    return $compareResult;
                }

                $nextPart = $this->_compare();
                //var_dump($operator);
                if($operator[0] == T_BOOLEAN_AND || $operator[0] == T_LOGICAL_AND) {
                    $compareResult = $compareResult && $nextPart;
                } elseif($operator[0] == T_BOOLEAN_OR || $operator[0] == T_LOGICAL_OR) {
                    $compareResult = $compareResult || $nextPart;
                }

                //$this->_debug("[-] _compare Result1: ".serialize($compareResult));$this->_intend--;
                return $compareResult;
            }


            $nextValue = $this->_next(false);
            if($nextValue == "(") {
                $this->_currentPos++;
                $checkValue = $this->_compare();
            } else {
                $checkValue = $this->_nextCommand();
            }

            $compareResult = $this->_compareOperator($compareResult, $checkValue, $operator);

            if($negotiate == true) {
                //$this->_debug("[ ] Negate Result");
                $compareResult = !$compareResult;
                $negotiate = false;
            }

            $operator = $this->_next(false);

            $firstValue = $checkValue;
        } while(1 == 1);

        if($negotiate == true) { $compareResult = !$compareResult; $negotiate = false; }

        /*
                do {
                    $operator = $this->_next(true);
                    if($operator == ")") {
                        break;
                    }
                    if($operator === -1 || $operator == "{") {
                        throw new \Exception("ERROR IN EXPRESSION _compare", E_EXPRESSION_ERROR);
                    }

                    $nextValue = $this->_next(false);
                    if($nextValue == "(") {
                        $this->_currentPos++;
                        $checkValue = $this->_compare();
                    } else {
                        $checkValue = $this->_nextCommand();
                    }

                    $compareResult = $compareResult && $this->_compareOperator($firstValue, $checkValue, $operator);


                    $firstValue = $checkValue;
                } while(1 == 1);
        */
        //$this->_debug("[-] _compare Result2: ".serialize($compareResult));$this->_intend--;
        return $compareResult;
    }

    private function _compareOperator($value1, $value2, $operatorToken) {
        if(is_string($operatorToken)) {
            $compareOperator = $operatorToken;
        } else {
            $compareOperator = $operatorToken[0];
        }
        //$this->_debug('[ ] Compare Operators '.$value1.' '.$value2.' '.json_encode($operatorToken));
        switch($compareOperator) {
            case "<":
                return $value1 < $value2;
                break;
            case ">":
                return $value1 > $value2;
                break;
            case T_IS_NOT_IDENTICAL:
                return $value1 !== $value2;
                break;
            case T_IS_IDENTICAL:
                return $value1 === $value2;
                break;
            case T_IS_EQUAL:
                return $value1 == $value2;
                break;
            case T_IS_GREATER_OR_EQUAL:
                return $value1 >= $value2;
                break;
            case T_IS_IDENTICAL:
                return $value1 === $value2;
                break;

            case T_IS_NOT_EQUAL:
                return $value1 != $value2;
                break;

            case T_IS_NOT_IDENTICAL:
                return $value1 !== $value2;
                break;

            case T_IS_SMALLER_OR_EQUAL:
                return $value1 <= $value2;
                break;
        }
    }

}

class ExpressionException extends \Exception
{

}

$alle = glob(dirname(__FILE__).'/../../extends/functions/*.inc.php');
foreach($alle as $datei) { include_once(realpath($datei)); }


?>