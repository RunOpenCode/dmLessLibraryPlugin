<?php

/**
 * Class dmLessLibraryTask
 *
 * @author Nikola Svitlica a.k.a TheCelavi
 */
class dmLessLibraryCompileTask extends dmContextTask {


    protected function configure()
    {
        parent::configure();

        $this->addOptions(array(
        ));

        $this->namespace = 'less';
        $this->name = 'compile';
        $this->aliases = array('lessc');
        $this->briefDescription = 'Compiles all or some of the LESS files in project.';

        $this->detailedDescription = $this->briefDescription;

        $this->addOptions(array(
            new sfCommandOption('plugin', 'p', sfCommandOption::PARAMETER_OPTIONAL, 'Compile for only targeted plugin/plugins', null),
            new sfCommandOption('force', 'f', sfCommandOption::PARAMETER_NONE, 'Force compile'),
            new sfCommandOption('write-empty', 'we', sfCommandOption::PARAMETER_NONE, 'Write empty files'),
            new sfCommandOption('preserve-comments', 'pc', sfCommandOption::PARAMETER_NONE, 'Preserve comments'),
            new sfCommandOption('enabled-plugins-only', 'ep', sfCommandOption::PARAMETER_NONE, 'Enabled plugins only')
        ));


    }

    /**
     * Executes the current task.
     *
     * @param array $arguments  An array of arguments
     * @param array $options    An array of options
     *
     * @return integer 0 if everything went fine, or an error code
     */
    protected function execute($arguments = array(), $options = array())
    {
        $force =  (isset($options['force']) && $options['force']) ? true : false;
        $writeEmpty = (isset($options['write-empty']) && $options['write-empty']) ? true : false;
        $enabledOnly =  (isset($options['enabled-plugins-only']) && $options['enabled-plugins-only']) ? true : false;
        $preserveComments =  (isset($options['preserve-comments']) && $options['preserve-comments']) ? true : false;

        $this->get('less_compiler')->compileProject($options['plugin'], $enabledOnly, null, null, $force, $writeEmpty, $preserveComments);
    }

}