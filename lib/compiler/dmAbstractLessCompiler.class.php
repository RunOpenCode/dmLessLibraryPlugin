<?php

define('DM_LESS_COMPILER_RESULT_COMPILED', 1);
define('DM_LESS_COMPILER_RESULT_SKIPPED_CACHE', 2);
define('DM_LESS_COMPILER_RESULT_SKIPPED_EMPTY_SOURCE', 3);
define('DM_LESS_COMPILER_RESULT_SKIPPED_EMPTY_OUTPUT', 4);

abstract class dmAbstractLessCompiler {

    protected $serviceContainer;

    public function __construct($serviceContainer)
    {
        $this->serviceContainer = $serviceContainer;
    }

    /**
     * Compiles LESS file to CSS file
     *
     * @param string $source Path to source file
     * @param string $target Path to output file
     * @param bool $force Force compile, do not check cache, default false
     * @param bool $writeEmpty Should empty source or empty compiled file be written, default false
     * @param bool $preserveComments Should comments be preserved, default true
     * @return int flag of compilation result
     * @throws dmLessCompilerException
     */
    public abstract function compile($source, $target, $force = false, $writeEmpty = false, $preserveComments = true);

    /**
     * Clears compilation cache (if any is used)
     *
     * @return array Associative array of 'errors' - error files/cache, 'success' - success files/cache
     */
    public abstract function clearCache();

    /**
     * Strips comments from the source code
     * NOTE: This function removes comments with hash tag, which is not good for LESS and CSS
     *
     * @param $source Source code
     * @return string Code without comments
     */
    protected function stripComments($source)
    {
        if (!defined('T_ML_COMMENT')) {
            define('T_ML_COMMENT', T_COMMENT);
        }
        if (!defined('T_DOC_COMMENT')) {
            define('T_DOC_COMMENT', T_ML_COMMENT);
        }

        $tokens = token_get_all($source);
        $ret = '';
        foreach ($tokens as $token) {
            if (is_string($token)) {
                $ret.= $token;
            } else {
                list($id, $text) = $token;

                switch ($id) {
                    case T_COMMENT:
                    case T_ML_COMMENT:
                    case T_DOC_COMMENT:
                        break;

                    default:
                        $ret.= $text;
                        break;
                }
            }
        }

        return trim($ret);
    }
}