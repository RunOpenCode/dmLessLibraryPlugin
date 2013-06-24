<?php
/**
 * User: Nikola Svitlica, a.k.a TheCelavi
 * Date: 22.6.13.
 * Time: 17.16
 */


class dmLessCompilerService extends dmConfigurable {

    protected
        $serviceContainer,
        $eventLog,
        $consoleLog,
        $finder,
        $compiler,
        $i18n;

    public function __construct($serviceContainer, $eventLog, $consoleLog, $options)
    {
        $this->serviceContainer = $serviceContainer;
        $this->eventLog = $eventLog;
        $this->consoleLog = $consoleLog;
        $this->i18n = $serviceContainer->getService('i18n');
        $this->initialize($options);
    }

    protected function initialize($options)
    {
        if ($options && is_array($options)) $this->configure($options);

        $finderClass = $this->getOption('less_finder_class', 'dmLessFileFinder');
        $compilerClass = $this->getOption('compiler_class', 'dmLesscLessCompiler');

        $this->finder = new $finderClass();
        $this->compiler = new $compilerClass($this->serviceContainer);
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
     * @see dmAbstractLessCompiler::compile
     */
    public function compile($source, $target, $force = false, $writeEmpty = false, $preserveComments = true)
    {
        return $this->compiler->compile($source, $target, $force, $writeEmpty, $preserveComments);
    }

    /**
     * Searches for LESS files in project or plugin
     *
     * @param string $pluginName Plugin in which to search for LESS files
     * Can be any plugin in plugins dir, or some aliases can be used as well:
     *      - web: searches in web/theme and web/themeAdmin dir
     *      - diem: searches in diem-extended/dmAdminPlugin/web, diem-extended/dmCorePlugin/web, diem-extended/dmFrontPlugin/web
     *      - admin: diem-extended/dmAdminPlugin/web
     *      - core: diem-extended/dmCorePlugin/web
     *      - front: diem-extended/dmFrontPlugin/web
     *      - plugins: project/plugins/*
     * @param bool $enabledOnly Search for only enabled plugins, default false
     * @param mixed $innerDirs List of inner dirs of web dir in which to search, beside configured. Can be array or string separated with comma.
     * @param int $maxDepth Max depth of recursive search. If no value is provided, the value from config will be used
     * @return array List of LESS files
     * @see dmLessFileFinder::findLessFiles
     */
    public function getLessFiles($pluginName = null, $enabledOnly = false, $innerDirs = null, $maxDepth = null)
    {
        return $this->finder->findLessFiles($pluginName, $enabledOnly, $innerDirs, $maxDepth);
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
        $this->consoleLog->logSection('less:cache', $this->i18n->__('Attempting to clear LESS compilation cache...', array(), 'dmLessLibraryPlugin'));
        $status = $this->compiler->clearCache();
        if ($status == null) {
            $this->consoleLog->logSection('less:cache', $this->i18n->__('Nothing to clear, compilation cache is not used.', array(), 'dmLessLibraryPlugin'));
        } else {
            foreach ($status['success'] as $success) {
                $this->consoleLog->logSection('less:cache', $this->i18n->__('Removed: %cache%', array('%cache%' => $success), 'dmLessLibraryPlugin'));
            }
            foreach ($status['errors'] as $error) {
                $this->consoleLog->logSection('less:cache', $this->i18n->__('Not removed: %cache%', array('%cache%' => $error), 'dmLessLibraryPlugin'), null, 'ERROR');
            }
            if (count($status['errors'])) {
                $message = $this->i18n->__('LESS compilation cache partially cleared, %count% left.', array('%count%' => count($status['errors'])), 'dmLessLibraryPlugin');
                $this->consoleLog->logSection('less:cache', $message, null, 'ERROR');
                $this->eventLog->log(array(
                    'server'  => $_SERVER,
                    'action'  => 'error',
                    'type'    => 'exception',
                    'subject' => $message
                ));
            } else {
                $message = $this->i18n->__('LESS compilation cache cleared.', array(), 'dmLessLibraryPlugin');
                $this->consoleLog->logSection('less:cache', $message);
                $this->eventLog->log(array(
                    'server'  => $_SERVER,
                    'action'  => 'clear',
                    'type'    => 'cache',
                    'subject' => $message
                ));
            }
        }

        return $status;
    }

    /**
     * Deletes compiled CSS files from LESS
     *
     * @param string $pluginName Plugin in which to search for CSS files
     * Can be any plugin in plugins dir, or some aliases can be used as well:
     *      - web: searches in web/theme and web/themeAdmin dir
     *      - diem: searches in diem-extended/dmAdminPlugin/web, diem-extended/dmCorePlugin/web, diem-extended/dmFrontPlugin/web
     *      - admin: diem-extended/dmAdminPlugin/web
     *      - core: diem-extended/dmCorePlugin/web
     *      - front: diem-extended/dmFrontPlugin/web
     *      - plugins: project/plugins/*
     * @param bool $enabledOnly Search for only enabled plugins, default false
     * @param mixed $innerDirs List of inner dirs of web dir in which to search, beside configured. Can be array or string separated with comma.
     * @param int $maxDepth Max depth of recursive search. If no value is provided, the value from config will be used
     *
     * @return array Associative array of 'errors' - error files, 'success' - success files, 'skipped' - skipped files
     *
     * @see dmLessFileFinder::findLessFiles
     */
    public function deleteCompiledCSS($pluginName = null, $enabledOnly = false, $innerDirs = null, $maxDepth = null)
    {
        $start = microtime(true);

        $success = array();
        $errors = array();
        $skipped = array();

        $lessFiles = $this->finder->findLessFiles($pluginName, $enabledOnly, $innerDirs, $maxDepth);
        if (count($lessFiles) == 0) {
            $this->consoleLog->logBlock($this->i18n->__('Nothing to delete.', array(), 'dmLessLibraryPlugin'), 'ERROR');
            return array(
                'success' => $success,
                'errors' => $errors,
                'skipped' => $skipped
            );
        } else {
            $this->consoleLog->logSettings(
                $this->i18n->__('Deleting compiled CSS files for project with settings:', array(), 'dmLessLibraryPlugin'),
                array(
                    $this->i18n->__('Plugins', array(), 'dmLessLibraryPlugin') => ((is_null($pluginName)) ? $this->i18n->__('ALL', array(), 'dmLessLibraryPlugin') : $pluginName),
                    $this->i18n->__('Enabled plugins only', array(), 'dmLessLibraryPlugin') => ($enabledOnly) ? $this->i18n->__('YES', array(), 'dmLessLibraryPlugin') : $this->i18n->__('NO', array(), 'dmLessLibraryPlugin'),
                    $this->i18n->__('Inner directories', array(), 'dmLessLibraryPlugin') => implode(',', $this->finder->getInnerDirs($innerDirs)),
                    $this->i18n->__('Max depth', array(), 'dmLessLibraryPlugin') => ($maxDepth) ? $maxDepth : sfConfig::get('dm_dmLessLibraryPlugin_search_max_depth')
                )
            );

            $this->consoleLog->logSection('less:delete-css', $this->i18n->__('Attempting to delete %count% CSS files...', array('%count%' => count($lessFiles)), 'dmLessLibraryPlugin'));
            $this->consoleLog->logHorizontalRule();

            foreach ($lessFiles as $file) {
                $cssFile = dmOs::join(dirname($file), pathinfo($file, PATHINFO_FILENAME) . '.css');
                if (file_exists($cssFile)) {
                    if (@unlink($cssFile)) {
                        $this->consoleLog->logSection($this->i18n->__('Deleted:', array(), 'dmLessLibraryPlugin'), $cssFile);
                        $success[] = $cssFile;
                    } else {
                        $this->consoleLog->logBlock($this->i18n->__('CSS file %file% could not be deleted', array('%file%' => $cssFile), 'dmLessLibraryPlugin'), 'ERROR');
                        $errors[] = $cssFile;
                    }
                } else {
                    $this->consoleLog->logSection($this->i18n->__('Skipped (not exist):', array(), 'dmLessLibraryPlugin'), $cssFile);
                    $skipped[] = $cssFile;
                }
            }

            $this->consoleLog->logStatus(
                $this->i18n->__('Status:', array(), 'dmLessLibraryPlugin'),
                array(
                    $this->i18n->__('Deleted files', array(), 'dmLessLibraryPlugin') => count($success),
                    $this->i18n->__('Skipped files', array(), 'dmLessLibraryPlugin') => count($skipped),
                    $this->i18n->__('Not deleted', array(), 'dmLessLibraryPlugin') => array(
                        'message' => count($errors),
                        'style' => (count($errors)) ? 'ERROR' : 'INFO'
                    ),
                ),
                round(microtime(true) - $start, 2)
            );

            $this->eventLog->log(array(
                'server'  => $_SERVER,
                'action'  => (count($errors)) ? 'exception' : 'info',
                'type'    => $this->i18n->__('Deleted CSS', array(), 'dmLessLibraryPlugin'),
                'subject' =>  $this->i18n->__('Compiled CSS files are deleted, %errors% errors', array('%errors%' => count($errors)), 'dmLessLibraryPlugin')
            ));

            return array(
                'success' => $success,
                'errors' => $errors,
                'skipped' => $skipped
            );
        }
    }

    /**
     * Compiles LESS files in project
     *
     * @param string $pluginName Plugin in which to search for LESS files
     * Can be any plugin in plugins dir, or some aliases can be used as well:
     *      - web: searches in web/theme and web/themeAdmin dir
     *      - diem: searches in diem-extended/dmAdminPlugin/web, diem-extended/dmCorePlugin/web, diem-extended/dmFrontPlugin/web
     *      - admin: diem-extended/dmAdminPlugin/web
     *      - core: diem-extended/dmCorePlugin/web
     *      - front: diem-extended/dmFrontPlugin/web
     *      - plugins: project/plugins/*
     * @param bool $enabledOnly Search for only enabled plugins, default false
     * @param mixed $innerDirs List of inner dirs of web dir in which to search, beside configured. Can be array or string separated with comma.
     * @param int $maxDepth Max depth of recursive search. If no value is provided, the value from config will be used

     * @see dmLessFileFinder::findLessFiles
     *
     * @param bool $force Force compile, do not check cache, default false
     * @param bool $writeEmpty Should empty source or empty compiled file be written, default false
     * @param bool $preserveComments Should comments be preserved, default true
     *
     * @see dmAbstractLessCompiler::compile
     *
     * @return array Associative array of 'errors' - error files, 'success' - success files, 'skipped' - skipped files
     *
     */
    public function compileProject($pluginName = null, $enabledOnly = false, $innerDirs = null, $maxDepth = null, $force = false, $writeEmpty = false, $preserveComments = true)
    {
        $start = microtime(true);

        $success = array();
        $errors = array();
        $skipped = array();

        $lessFiles = $this->finder->findLessFiles($pluginName, $enabledOnly, $innerDirs, $maxDepth);

        if (count($lessFiles) == 0) {
            $this->consoleLog->logBlock($this->i18n->__('Nothing to compile.', array(), 'dmLessLibraryPlugin'), 'ERROR');
            return array(
                'success' => $success,
                'errors' => $errors,
                'skipped' => $skipped
            );
        } else {
            $this->consoleLog->logSettings(
                $this->i18n->__('Compiling LESS files for project with settings:', array(), 'dmLessLibraryPlugin'),
                array(
                    $this->i18n->__('Plugins', array(), 'dmLessLibraryPlugin') => ((is_null($pluginName)) ? $this->i18n->__('ALL', array(), 'dmLessLibraryPlugin') : $pluginName),
                    $this->i18n->__('Enabled plugins only', array(), 'dmLessLibraryPlugin') => ($enabledOnly) ? $this->i18n->__('YES', array(), 'dmLessLibraryPlugin') : $this->i18n->__('NO', array(), 'dmLessLibraryPlugin'),
                    $this->i18n->__('Inner directories', array(), 'dmLessLibraryPlugin') => implode(',', $this->finder->getInnerDirs($innerDirs)),
                    $this->i18n->__('Max depth', array(), 'dmLessLibraryPlugin') => ($maxDepth) ? $maxDepth : sfConfig::get('dm_dmLessLibraryPlugin_search_max_depth'),
                    $this->i18n->__('Force compile', array(), 'dmLessLibraryPlugin') => ($force) ? $this->i18n->__('YES', array(), 'dmLessLibraryPlugin') : $this->i18n->__('NO', array(), 'dmLessLibraryPlugin'),
                    $this->i18n->__('Write empty files', array(), 'dmLessLibraryPlugin') => ($writeEmpty) ? $this->i18n->__('YES', array(), 'dmLessLibraryPlugin') : $this->i18n->__('NO', array(), 'dmLessLibraryPlugin'),
                    $this->i18n->__('Preserve comments', array(), 'dmLessLibraryPlugin') => ($preserveComments) ? $this->i18n->__('YES', array(), 'dmLessLibraryPlugin') : $this->i18n->__('NO', array(), 'dmLessLibraryPlugin'),
                )
            );

            $this->consoleLog->logSection('less:compile', $this->i18n->__('Attempting to compile %count% LESS files...', array('%count%' => count($lessFiles)), 'dmLessLibraryPlugin'));
            $this->consoleLog->logHorizontalRule();

            foreach ($lessFiles as $file) {
                try {
                    $target =  dmOs::join(dirname($file) , pathinfo($file, PATHINFO_FILENAME) . '.css');
                    $this->consoleLog->logBlock($this->i18n->__('Compiling: %file%', array('%file%' => $file), 'dmLessLibraryPlugin'), 'COMMENT');
                    $this->consoleLog->logBlock($this->i18n->__('Into:      %file%', array('%file%' => $target), 'dmLessLibraryPlugin'), 'COMMENT');
                    $flag = $this->compile(
                        $file,
                        $target,
                        $force,
                        $writeEmpty,
                        $preserveComments
                    );
                    switch ($flag) {
                        case DM_LESS_COMPILER_RESULT_SKIPPED_EMPTY_OUTPUT:
                            $this->consoleLog->logSection('less:compiler', $this->i18n->__('Skipped - empty output.', array(), 'dmLessLibraryPlugin'));
                            $skipped[] = $file;
                            break;
                        case DM_LESS_COMPILER_RESULT_SKIPPED_EMPTY_SOURCE:
                            $this->consoleLog->logSection('less:compiler', $this->i18n->__('Skipped - empty source.', array(), 'dmLessLibraryPlugin'));
                            $skipped[] = $file;
                            break;
                        case DM_LESS_COMPILER_RESULT_SKIPPED_CACHE:
                            $this->consoleLog->logSection('less:compiler', $this->i18n->__('Skipped - already compiled.', array(), 'dmLessLibraryPlugin'));
                            $skipped[] = $file;
                            break;
                        default:
                            $this->consoleLog->logSection('less:compiler', $this->i18n->__('Compiled.', array(), 'dmLessLibraryPlugin'));
                            $success[] = $file;
                            break;
                    }

                } catch (dmLessCompilerException $e) {
                    $this->consoleLog->logSection('less:compiler', $this->i18n->__('COMPILER ERROR: %message%', array('%message%' => $e->getMessage()), 'dmLessLibraryPlugin'), null, 'ERROR');
                    $errors[] = $file;
                } catch (Exception $e) {
                    $this->consoleLog->logSection('less:compiler', $this->i18n->__('UNEXPECTED ERROR: %message%', array('%message%' => $e->getMessage()), 'dmLessLibraryPlugin'), null, 'ERROR');
                    $errors[] = $file;
                }
            }

            $this->consoleLog->logStatus(
                $this->i18n->__('Status:', array(), 'dmLessLibraryPlugin'),
                array(
                    $this->i18n->__('Compiled files', array(), 'dmLessLibraryPlugin') => count($success),
                    $this->i18n->__('Skipped files', array(), 'dmLessLibraryPlugin') => count($skipped),
                    $this->i18n->__('Not compiled', array(), 'dmLessLibraryPlugin') => array(
                        'message' => count($errors),
                        'style' => (count($errors)) ? 'ERROR' : 'INFO'
                    ),
                ),
                round(microtime(true) - $start, 2)
            );

            $this->eventLog->log(array(
                'server'  => $_SERVER,
                'action'  => (count($errors)) ? 'exception' : 'info',
                'type'    => $this->i18n->__('Compile LESS', array(), 'dmLessLibraryPlugin'),
                'subject' =>  $this->i18n->__('LESS files compiled, %errors% errors', array('%errors%' => count($errors)), 'dmLessLibraryPlugin')
            ));

            return array(
                'success' => $success,
                'errors' => $errors,
                'skipped' => $skipped
            );
        }
    }


    /**
     * Deletes LESS files from project
     *
     * @param string $pluginName Plugin in which to search for LESS files
     * Can be any plugin in plugins dir, or some aliases can be used as well:
     *      - web: searches in web/theme and web/themeAdmin dir
     *      - diem: searches in diem-extended/dmAdminPlugin/web, diem-extended/dmCorePlugin/web, diem-extended/dmFrontPlugin/web
     *      - admin: diem-extended/dmAdminPlugin/web
     *      - core: diem-extended/dmCorePlugin/web
     *      - front: diem-extended/dmFrontPlugin/web
     *      - plugins: project/plugins/*
     * @param bool $enabledOnly Search for only enabled plugins, default false
     * @param mixed $innerDirs List of inner dirs of web dir in which to search, beside configured. Can be array or string separated with comma.
     * @param int $maxDepth Max depth of recursive search. If no value is provided, the value from config will be used
     *
     * @return array Associative array of 'errors' - error files, 'success' - success files, 'skipped' - skipped files
     *
     * @see dmLessFileFinder::findLessFiles
     */
    public function deleteLessSource($pluginName = null, $enabledOnly = false, $innerDirs = null, $maxDepth = null)
    {
        $start = microtime(true);

        $success = array();
        $errors = array();
        $skipped = array();

        $lessFiles = $this->finder->findLessFiles($pluginName, $enabledOnly, $innerDirs, $maxDepth);
        if (count($lessFiles) == 0) {
            $this->consoleLog->logBlock($this->i18n->__('Nothing to delete.', array(), 'dmLessLibraryPlugin'), 'ERROR');
            return array(
                'success' => $success,
                'errors' => $errors,
                'skipped' => $skipped
            );
        } else {
            $this->consoleLog->logSettings(
                $this->i18n->__('Deleting source LESS files for project with settings:', array(), 'dmLessLibraryPlugin'),
                array(
                    $this->i18n->__('Plugins', array(), 'dmLessLibraryPlugin') => ((is_null($pluginName)) ? $this->i18n->__('ALL', array(), 'dmLessLibraryPlugin') : $pluginName),
                    $this->i18n->__('Enabled plugins only', array(), 'dmLessLibraryPlugin') => ($enabledOnly) ? $this->i18n->__('YES', array(), 'dmLessLibraryPlugin') : $this->i18n->__('NO', array(), 'dmLessLibraryPlugin'),
                    $this->i18n->__('Inner directories', array(), 'dmLessLibraryPlugin') => implode(',', $this->finder->getInnerDirs($innerDirs)),
                    $this->i18n->__('Max depth', array(), 'dmLessLibraryPlugin') => ($maxDepth) ? $maxDepth : sfConfig::get('dm_dmLessLibraryPlugin_search_max_depth')
                )
            );

            $this->consoleLog->logSection('less:delete-less', $this->i18n->__('Attempting to delete %count% LESS files...', array('%count%' => count($lessFiles)), 'dmLessLibraryPlugin'));
            $this->consoleLog->logHorizontalRule();

            foreach ($lessFiles as $file) {
                if (file_exists($file)) {
                    if (true /*@unlink($cssFile)*/) {
                        $this->consoleLog->logSection($this->i18n->__('Deleted:', array(), 'dmLessLibraryPlugin'), $file);
                        $success[] = $file;
                    } else {
                        $this->consoleLog->logBlock($this->i18n->__('LESS file %file% could not be deleted', array('%file%' => $file), 'dmLessLibraryPlugin'), 'ERROR');
                        $errors[] = $file;
                    }
                } else {
                    $this->consoleLog->logSection($this->i18n->__('Skipped (not exist):', array(), 'dmLessLibraryPlugin'), $file);
                    $skipped[] = $file;
                }
            }

            $this->consoleLog->logStatus(
                $this->i18n->__('Status:', array(), 'dmLessLibraryPlugin'),
                array(
                    $this->i18n->__('Deleted files', array(), 'dmLessLibraryPlugin') => count($success),
                    $this->i18n->__('Skipped files', array(), 'dmLessLibraryPlugin') => count($skipped),
                    $this->i18n->__('Not deleted', array(), 'dmLessLibraryPlugin') => array(
                        'message' => count($errors),
                        'style' => (count($errors)) ? 'ERROR' : 'INFO'
                    ),
                ),
                round(microtime(true) - $start, 2)
            );

            $this->eventLog->log(array(
                'server'  => $_SERVER,
                'action'  => (count($errors)) ? 'exception' : 'info',
                'type'    => $this->i18n->__('Deleted LESS', array(), 'dmLessLibraryPlugin'),
                'subject' =>  $this->i18n->__('LESS files are deleted, %errors% errors', array('%errors%' => count($errors)), 'dmLessLibraryPlugin')
            ));

            return array(
                'success' => $success,
                'errors' => $errors,
                'skipped' => $skipped
            );
        }
    }
}