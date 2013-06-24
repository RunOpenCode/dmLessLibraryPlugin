<?php

class dmLessLibraryDeleteCompiledTask extends dmContextTask {

    protected function configure()
    {
        parent::configure();

        $this->addOptions(array(
        ));

        $this->namespace = 'less';
        $this->name = 'delete-css';
        $this->aliases = array('less:delcss');
        $this->briefDescription = 'Delete CSS files created from compiled LESS files.';

        $this->detailedDescription = $this->briefDescription;

        $this->addOptions(array(
            new sfCommandOption('plugin', 'p', sfCommandOption::PARAMETER_OPTIONAL, 'Delete CSS files for only targeted plugin/plugins', null),
            new sfCommandOption('enabled-plugins-only', 'ep', sfCommandOption::PARAMETER_NONE, 'Enabled plugins only')
        ));
    }

    protected function execute($arguments = array(), $options = array())
    {
        $enabledOnly =  (isset($options['enabled-plugins-only']) && $options['enabled-plugins-only']) ? true : false;
        $this->get('less_compiler')->deleteCompiledCSS($options['plugin'], $enabledOnly);
    }

}