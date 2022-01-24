<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Table/classes/class.ilTable2GUI.php");

/**
 * Benchmark table
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 *
 * @ingroup ModulesSystemFolder
 */
class ilBenchmarkTableGUI extends ilTable2GUI
{
    /**
     * @var ilAccessHandler
     */
    protected $access;


    /**
     * Constructor
     */
    public function __construct($a_parent_obj, $a_parent_cmd, $a_records, $a_mode = "chronological")
    {
        global $DIC;

        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->access = $DIC->access();
        $ilCtrl = $DIC->ctrl();
        $lng = $DIC->language();
        $ilAccess = $DIC->access();
        $lng = $DIC->language();

        parent::__construct($a_parent_obj, $a_parent_cmd);
        $this->setLimit(9999);
        $this->mode = $a_mode;

        // fau: extendBenchmark - get total time
        global $ilBench;
        $this->total = $ilBench->getDbBenchTotalTime();
        // fau.

        // fau: extendBenchmark - add columns for time share and toggle, headers for grouping by SQL
        $lng->loadLanguageModule('trac');

        switch ($this->mode) {
            case "slowest_first":
                $this->setData(ilUtil::sortArray($a_records, "time", "desc", true));
                $this->setTitle($lng->txt("adm_db_bench_slowest_first"));
                $this->addColumn($this->lng->txt("adm_time"));
                $this->addColumn($this->lng->txt("trac_percentage"));
                $this->addColumn(' ');
                $this->addColumn($this->lng->txt("adm_sql"));
                break;

            case "sorted_by_sql":
                $this->setData(ilUtil::sortArray($a_records, "sql", "asc"));
                $this->setTitle($lng->txt("adm_db_bench_sorted_by_sql"));
                $this->addColumn($this->lng->txt("adm_time"));
                $this->addColumn($this->lng->txt("trac_percentage"));
                $this->addColumn(' ');
                $this->addColumn($this->lng->txt("adm_sql"));
                break;

            case "grouped_by_sql":
                $this->setData($this->groupDataBySQL($a_records));
                $this->setTitle($lng->txt("adm_db_bench_grouped_by_sql"));
                $this->addColumn($this->lng->txt("adm_time"));
                $this->addColumn($this->lng->txt("trac_percentage"));
                $this->addColumn($this->lng->txt("adm_nr_statements"));
                $this->addColumn(' ');
                $this->addColumn($this->lng->txt("adm_sql"));
                break;

            case "by_first_table":
                $this->setData($this->getDataByFirstTable($a_records));
                $this->setTitle($lng->txt("adm_db_bench_by_first_table"));
                $this->addColumn($this->lng->txt("adm_time"));
                $this->addColumn($this->lng->txt("trac_percentage"));
                $this->addColumn($this->lng->txt("adm_nr_statements"));
                $this->addColumn($this->lng->txt("adm_table"));
                break;

            default:
                $this->setData($a_records);
                $this->setTitle($lng->txt("adm_db_bench_chronological"));
                $this->addColumn($this->lng->txt("adm_time"));
                $this->addColumn($this->lng->txt("trac_percentage"));
                $this->addColumn(' ');
                $this->addColumn($this->lng->txt("adm_sql"));
                break;
        }
        // fau.

        $this->setEnableHeader(true);
        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
        $this->setRowTemplate("tpl.db_bench.html", "Modules/SystemFolder");
        $this->disable("footer");
        $this->setEnableTitle(true);

        //		$this->addMultiCommand("", $lng->txt(""));
        //		$this->addCommandButton("", $lng->txt(""));

        // fau: extendBenchmark - include JQUery for toggling the
        include_once("./Services/jQuery/classes/class.iljQueryUtil.php");
        ilJQueryUtil::initjQuery();
        // fau.
    }

    /**
     * Get first occurence of string
     *
     * @param
     * @return
     */
    public function getFirst($a_str, $a_needles)
    {
        $pos = 0;
        foreach ($a_needles as $needle) {
            $pos2 = strpos($a_str, $needle);

            if ($pos2 > 0 && ($pos2 < $pos || $pos == 0)) {
                $pos = $pos2;
            }
        }

        return $pos;
    }

    /**
     * Extract first table from sql
     *
     * @param
     * @return
     */
    public function extractFirstTableFromSQL($a_sql)
    {
        $pos1 = $this->getFirst(strtolower($a_sql), array("from ", "from\n", "from\t", "from\r"));

        $table = "";
        if ($pos1 > 0) {
            $tablef = substr(strtolower($a_sql), $pos1 + 5);
            $pos2 = $this->getFirst($tablef, array(" ", "\n", "\t", "\r"));
            if ($pos2 > 0) {
                $table = substr($tablef, 0, $pos2);
            } else {
                $table = $tablef;
            }
        }
        if (trim($table) != "") {
            return $table;
        }

        return "";
    }


    /**
     * Get data by first table
     *
     * @param
     * @return
     */
    public function getDataByFirstTable($a_records)
    {
        $data = array();
        foreach ($a_records as $r) {
            $table = $this->extractFirstTableFromSQL($r["sql"]);
            $data[$table]["table"] = $table;
            $data[$table]["cnt"]++;
            $data[$table]["time"] += $r["time"];
        }
        if (count($data) > 0) {
            $data = ilUtil::sortArray($data, "time", "desc", true);
        }

        return $data;
    }

    // fau: extendBenchmark - new function to group data by SQL
    public function groupDataBySQL($a_records)
    {
        $data = array();
        foreach ($a_records as $r) {
            $hash = md5($r["sql"]);
            $data[$hash]["sql"] = $r["sql"];
            $data[$hash]["cnt"]++;
            $data[$hash]["time"] += $r["time"];

            $bthash = md5($r["backtrace"]);
            $data[$hash]["backtrace"][$bthash]["trace"] = $r["backtrace"];
            $data[$hash]["backtrace"][$bthash]["count"]++;
            $data[$hash]["backtrace"][$bthash]["time"] += $r["time"];
        }

        foreach ($data as $hash => $row) {
            $backtraces = ilUtil::sortArray(array_values($row['backtrace']), "time", "desc", true);
            $btstring = '';
            foreach ($backtraces as $btrow) {
                if ($this->total > 0) {
                    $share = round(100 * $btrow['time'] / $this->total, 2) . '%';
                }
                $bthead = $btrow['count'] . "x - " . round($btrow['time'], 5) . "s " . $share . "\n";
                $btstring .= $bthead . $btrow['trace'] . "\n\n";
            }
            $data[$hash]['backtrace'] = $btstring;
        }

        if (count($data) > 0) {
            $data = ilUtil::sortArray(array_values($data), "time", "desc", true);
        }

        return $data;
    }
    // fau.

    /**
     * Fill table row
     */
    protected function fillRow($a_set)
    {
        $lng = $this->lng;

        // fau: extendBenchmark - add time share info, add backtraces, add groped_by_sql_mode
        if ($this->total > 0) {
            $share = round(100 * $a_set["time"] / $this->total, 2) . '%';
        }
        $expand_id = 'expand' . rand();

        switch ($this->mode) {
            case "by_first_table":
                $this->tpl->setCurrentBlock("td");
                $this->tpl->setVariable("VAL", round($a_set["time"], 5));
                $this->tpl->parseCurrentBlock();

                $this->tpl->setCurrentBlock("td");
                $this->tpl->setVariable("VAL", $share);
                $this->tpl->parseCurrentBlock();

                $this->tpl->setCurrentBlock("td");
                $this->tpl->setVariable("VAL", $a_set["cnt"]);
                $this->tpl->parseCurrentBlock();

                $this->tpl->setCurrentBlock("td");
                $this->tpl->setVariable("VAL", $a_set["table"]);
                $this->tpl->parseCurrentBlock();
                break;

            case "grouped_by_sql":
                $this->tpl->setCurrentBlock("td");
                $this->tpl->setVariable("VAL", round($a_set["time"], 5));
                $this->tpl->parseCurrentBlock();

                $this->tpl->setCurrentBlock("td");
                $this->tpl->setVariable("VAL", $share);
                $this->tpl->parseCurrentBlock();

                $this->tpl->setCurrentBlock("td");
                $this->tpl->setVariable("VAL", $a_set["cnt"]);
                $this->tpl->parseCurrentBlock();

                $this->tpl->setCurrentBlock("td2");
                $this->tpl->setVariable("VAL2", wordwrap($a_set["sql"], 80, "\n", true));
                $this->tpl->setVariable("EXPAND_ID", $expand_id);
                $this->tpl->setVariable("EXPAND_VAL", $a_set["backtrace"]);
                $this->tpl->parseCurrentBlock();

                $this->tpl->setCurrentBlock("toggle");
                $this->tpl->setVariable("EXPAND_ID", $expand_id);
                $this->tpl->setVariable("EXPAND_IMG", ilUtil::getImagePath("tree_col.svg"));
                $this->tpl->setVariable("EXPAND_TXT", $lng->txt('details'));
                $this->tpl->parseCurrentBlock();
                break;


            case "slowest_first":
            case "sorted_by_sql":
            default:
                $this->tpl->setCurrentBlock("td");
                $this->tpl->setVariable("VAL", round($a_set["time"], 5));
                $this->tpl->parseCurrentBlock();

                $this->tpl->setCurrentBlock("td");
                $this->tpl->setVariable("VAL", $share);
                $this->tpl->parseCurrentBlock();

                $this->tpl->setCurrentBlock("td2");
                $this->tpl->setVariable("VAL2", wordwrap($a_set["sql"], 80, "\n", true));
                $this->tpl->setVariable("EXPAND_ID", $expand_id);
                $this->tpl->setVariable("EXPAND_VAL", $a_set["backtrace"]);
                $this->tpl->parseCurrentBlock();

                $this->tpl->setCurrentBlock("toggle");
                $this->tpl->setVariable("EXPAND_ID", $expand_id);
                $this->tpl->setVariable("EXPAND_IMG", ilUtil::getImagePath("tree_col.svg"));
                $this->tpl->setVariable("EXPAND_TXT", $lng->txt('details'));
                $this->tpl->parseCurrentBlock();
// fau.
                break;
        }
    }
}
