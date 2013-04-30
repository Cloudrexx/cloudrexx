<?php

namespace Cx\Core_Modules\Workbench\Model\Entity;

abstract class UserInterface {
    private $commands = array();
    private $workbench = null;
    
    public function __construct() {
        \Env::get('ClassLoader')->loadFile(ASCMS_CORE_PATH.'/Typing/Model/Entity/AutoBoxedObject.class.php');
        \Env::get('ClassLoader')->loadFile(ASCMS_CORE_PATH.'/Typing/Model/Entity/Primitives.class.php');
        $this->commands = array(
            'create' => new CreateCommand($this), // create new component
            'delete' => new DeleteCommand($this), // delete a component
            'activate' => new ActivateCommand($this), // activate a component
            //'deactivate' => new DeactivateCommand($this), // deactivate a component
            //'export' => new ExportCommand($this), // export contrexx files without workbench
            //'remove' => new RemoveCommand($this), // remove workbench from installation
            //'update' => new UpdateCommand($this), // port a component to this version of contrexx
            //'convert' => new ConvertCommand($this), // convert component types (core to core_module, etc.)
            //'publish' => new PublishCommand($this), // publish component to contrexx app repo (after successful unit testing)
            //'test' => new TestCommand($this), // run UnitTests
            //'db' => new DbCommmand($this), // wrapper for doctrine commandline tools
        );
    }
    
    public function commandExists($commandName) {
        return isset($this->commands[$commandName]);
    }

    /**
     *
     * @param type $commandName
     * @return Command 
     */
    public function getCommand($commandName) {
        if (!$this->commandExists($commandName)) {
            return null;
        }
        return $this->commands[$commandName];
    }
    
    public function getCommands() {
        return $this->commands;
    }
    
    public function getConfigVar($name) {
        if (!$this->workbench) {
            $this->workbench = new \Cx\Core_Modules\Workbench\Controller\Workbench();
        }
        return $this->workbench->getConfigEntry($name);
    }
    
    public function setConfigVar($name, $value) {
        if (!$this->workbench) {
            $this->workbench = new \Cx\Core_Modules\Workbench\Controller\Workbench();
        }
        return $this->workbench->setConfigEntry($name, $value);
    }
    
    /**
     * @return \Cx\Core\Db\Db
     */
    public abstract function getDb();
    
    public abstract function input($description, $defaultValue = '');
    
    public abstract function yesNo($question);
    
    public abstract function show($message);
}
