<?php

class dmLessLibraryDeleteLessTask extends dmContextTask {

    protected $i18n;

    protected function configure()
    {
        parent::configure();

        $this->addOptions(array(
        ));

        $this->namespace = 'less';
        $this->name = 'delete-less';
        $this->briefDescription = 'Delete LESS files from project.';

        $this->detailedDescription = $this->briefDescription;

        $this->addOptions(array(
            new sfCommandOption('plugin', 'p', sfCommandOption::PARAMETER_OPTIONAL, 'Delete LESS files for only targeted plugin/plugins', null),
            new sfCommandOption('enabled-plugins-only', 'ep', sfCommandOption::PARAMETER_NONE, 'Enabled plugins only')
        ));
    }

    protected function execute($arguments = array(), $options = array())
    {
        $this->i18n = $this->get('i18n');
        $enabledOnly =  (isset($options['enabled-plugins-only']) && $options['enabled-plugins-only']) ? true : false;
        $this->deleteLessFiles($options['plugin'], $enabledOnly);
    }

    protected function deleteLessFiles($plugin, $enabledOnly = false)
    {
        $compiler = $this->get('less_compiler');
        $logger = $this->get('console_log');
        $lessFiles = $compiler->getLessFiles($plugin, $enabledOnly);

        if (count($lessFiles)) {

            $logger->logHorizontalRule();
            $logger->logBlock($this->i18n->__('The following %count% LESS files will be deleted:', array('%count%'=>count($lessFiles)), 'dmLessLibraryPlugin'));
            $logger->logHorizontalRule();
            foreach ($lessFiles as $file) {
                $logger->log('- '.$file);
            }
            $logger->logHorizontalRule();

            $logger->logBlock(array(
                '',
                $this->i18n->__('WARNING!!!', array(), 'dmLessLibraryPlugin'),
                $this->i18n->__('This task should be used only on production server.', array(), 'dmLessLibraryPlugin'),
                $this->i18n->__('The task will delete LESS source files from project.', array(), 'dmLessLibraryPlugin'),
                $this->i18n->__('Please create backup of your source files.', array(), 'dmLessLibraryPlugin'),
                $this->i18n->__('This action can not be undone.', array(), 'dmLessLibraryPlugin'),
                ''
            ), 'QUESTION_LARGE');

            $logger->logBreakLine();

            $confirm = $this->askConfirmation($this->i18n->__('Are you shore?', array(), 'dmLessLibraryPlugin'), 'QUESTION', false);

            if ($confirm) {
                $compiler->deleteLessSource($plugin, $enabledOnly);
            } else {
                $logger->logBlock($this->i18n->__('Task is not executed - no LESS file is deleted.', array(), 'dmLessLibraryPlugin'));
            }

        } else {
            $this->logBlock($this->i18n->__('Nothing to delete.', array(), 'dmLessLibraryPlugin'), 'ERROR');
        }
    }
}