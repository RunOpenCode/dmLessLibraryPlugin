<?php

class dmLesscLessCompiler extends dmAbstractLessCompiler {

    /**
     * Compiles LESS file to CSS file using http://leafo.net/lessphp
     *
     * @param string $source Path to source file
     * @param string $target Path to output file
     * @param bool $force Force compile, do not check cache, default false
     * @param bool $writeEmpty Should empty source or empty compiled file be written, default false
     * @param bool $preserveComments Should comments be preserved, default true
     * @return int flag of compilation result
     * @throws dmLessCompilerException
     */
    public function compile($source, $target, $force = false, $writeEmpty = false, $preserveComments = true)
    {
        if (!$writeEmpty) {
            $src = file_get_contents($source);
            if (trim($src) == '') {
                return DM_LESS_COMPILER_RESULT_SKIPPED_EMPTY_SOURCE;
            }
        }

        $compiler = new lessc();
        $compiler->setPreserveComments($preserveComments);
        $cacheFile = dmOs::join(sfConfig::get('sf_cache_dir'), 'dmLesscLessCompiler', md5($source) . '.less.cache');
        try {
            if (file_exists($cacheFile) && !$force) {
                $cache = unserialize(file_get_contents($cacheFile));
            } else {
                $cache = $source;
            }

            $newCache = $compiler->cachedCompile($cache, $force);

            if (!is_array($cache) || $newCache['updated'] > $cache['updated']) {
                if (!file_exists(dmOs::join(sfConfig::get('sf_cache_dir'), 'dmLesscLessCompiler'))) {
                    $this->serviceContainer->getService('filesystem')->mkdir(dmOs::join(sfConfig::get('sf_cache_dir'), 'dmLesscLessCompiler'));
                }
                file_put_contents($cacheFile, serialize($newCache));
                if ($this->stripComments($newCache['compiled']) != '') {
                    file_put_contents($target, $newCache['compiled']);
                    return DM_LESS_COMPILER_RESULT_COMPILED;
                } else {
                    return DM_LESS_COMPILER_RESULT_SKIPPED_EMPTY_OUTPUT;
                }
            } else {
                return DM_LESS_COMPILER_RESULT_SKIPPED_CACHE;
            }
        } catch (Exception $e) {
            throw new dmLessCompilerException(sprintf('Less compile exception in file: "%s", inner message: "%s"', $source, $e->getMessage()));
        }
    }

    /**
     * Clears compilation cache (if any is used)
     *
     * @return mixed
     *  * If cache is used: array Associative array of 'errors' - error files/cache, 'success' - success files/cache, 'type' - type of cache used
     *  * If no cache is used, null
     */
    public function clearCache()
    {
        $files = sfFinder::type('file')->name('*.less.cache')->in(dmOs::join(sfConfig::get('sf_cache_dir'), 'dmLesscLessCompiler'));
        $errors = array();
        $success = array();
        foreach ($files as $file) {
            if (!@unlink($file)) {
                $errors[] = $file;
            } else {
                $success[] = $file;
            }
        }
        return array(
            'success' => $success,
            'errors' => $errors,
            'type' => 'file'
        );
    }
}