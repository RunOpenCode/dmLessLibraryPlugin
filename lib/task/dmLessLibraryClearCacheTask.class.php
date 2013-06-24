<?php
/**
 * Class dmLessLibraryClearCacheTask
 *
 * @author Nikola Svitlica a.k.a TheCelavi
 */

class dmLessLibraryClearCacheTask extends dmContextTask {

    protected function configure()
    {
        parent::configure();

        $this->addOptions(array(
        ));

        $this->namespace = 'less';
        $this->name = 'clear-cache';
        $this->aliases = array('less:cc');
        $this->briefDescription = 'Clears the compilation cache, if any is used at all.';

        $this->detailedDescription = $this->briefDescription;
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
        $this->get('less_compiler')->clearCache();
    }

}