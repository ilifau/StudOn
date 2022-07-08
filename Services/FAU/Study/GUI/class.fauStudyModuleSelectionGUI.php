<?php

use FAU\Study\Data\CourseOfStudy;
use FAU\Study\Data\Module;
use FAU\Study\Data\ModuleCos;

class fauStudyModuleSelectionGUI extends ilFormPropertyGUI
{
    /** @var CourseOfStudy[] */
    protected $cos = [];

    /** @var Module  */
    protected $modules = [];

    /** @var ModuleCos[]  */
    protected $modules_cos = [];


    protected ?int $cos_id;
    protected ?int $module_id;


    /**
     * Constructor
     * @param string $a_title   Title
     * @param string $a_postvar Post Variable
     */
    public function __construct($a_title = "", $a_postvar = "")
    {
        parent::__construct($a_title, $a_postvar);
        $this->setType("cos_module");
    }

    /**
     * @param CourseOfStudy[] $cos
     */
    public function setCoursesOfStudy(array $cos)
    {
        $this->cos = $cos;
    }

    /**
     * @param Module[] $modules
     */
    public function setModules(array $modules)
    {
        $this->modules = $modules;
    }

    /**
     * @param Module[] $modules
     */
    public function setModulesCos(array $modules_cos)
    {
        $this->modules_cos = $modules_cos;
    }


    /**
     * Insert property html
     *
     */
    public function render()
    {
        $tpl = $this->plugin->getTemplate("tpl.daytime.html", true, true);

        $tpl->setVariable("TXT_DELIM", $this->plugin->txt('time_delim'));
        $tpl->setVariable("TXT_SUFFIX", $this->plugin->txt('time_suffix'));

        $val =  ['  ' => '--'];
        for ($i = 0; $i <= 23; $i++) {
            $val[sprintf("%02d", $i)] = sprintf("%02d", $i);
        }
        $tpl->setVariable(
            "SELECT_HOURS",
            ilUtil::formSelect(
                $this->hours,
                $this->getPostVar() . "[hh]",
                $val,
                false,
                true,
                0,
                '',
                '',
                $this->getDisabled()
            )
        );

        $val =  ['  ' => '--'];
        for ($i = 0; $i <= 59; $i = $i + 5) {
            $val[sprintf("%02d", $i)] = sprintf("%02d", $i);
        }
        $tpl->setVariable(
            "SELECT_MINUTES",
            ilUtil::formSelect(
                $this->minutes,
                $this->getPostVar() . "[mm]",
                $val,
                false,
                true,
                0,
                '',
                '',
                $this->getDisabled()
            )
        );

        return $tpl->get();
    }

    /**
     * Check input, strip slashes etc. set alert, if input is not ok.
     *
     * @return	boolean		Input ok, true/false
     */
    public function checkInput()
    {
        $_POST[$this->getPostVar()]["cos_id"] = ilUtil::stripSlashes($_POST[$this->getPostVar()]["cos_id"]);
        $_POST[$this->getPostVar()]["module_id"] = ilUtil::stripSlashes($_POST[$this->getPostVar()]["module_id"]);


        return true;
    }

    /**
     * Get the value as array ['cos_id' => ?int, 'module_id' => ?int]
     * @return array
     */
    public function getValue()
    {
        return [
            'cos_id' => $this->cos_id,
            'module_id' => $this->module_id,
        ];
    }

    /**
     * Set the value from an array ['cos_id' => ?int, 'module_id' => ?int]
     * @var array $a_value
     */
    public function setValue($a_value)
    {
        $this->cos_id = (empty($a_value['cos_id'] ?? null) ? null : (int) $a_value['cos_id']);
        $this->module_id = (empty($a_value['module_id'] ?? null) ? null : (int) $a_value['cos_id']);
    }

    /**
     * Set value from part of a posted array
     *
     * @param	array	$a_values	value array
     */
    public function setValueByArray($a_values)
    {
        $this->setValue($a_values[$this->getPostVar()]);
    }
}