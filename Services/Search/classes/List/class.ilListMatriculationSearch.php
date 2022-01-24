<?php
/**
 *  fau: searchMatriculations - new class ilListMatriculationSearch.
 * Performs Mysql search for a list of matriculation numbers
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @package ilias-search
 *
*/
include_once 'Services/Search/classes/class.ilUserSearch.php';

class ilListMatriculationSearch extends ilUserSearch
{
    /**
     * @var array list of matriculation numbers
     */
    protected $matriculations = [];


    /**
     * Parse a list of matriculations
     * @param string $input
     * @return bool list is not empty
     */
    public function parseMatriculationList($input)
    {
        $input = preg_replace('/\r/', ',', $input);   // carriage return to comma
        $input = preg_replace('/\n/', ',', $input);   // newline to comma
        $input = preg_replace('/;/', ',', $input);   // semicolon to comma
        $array = explode(',', $input);

        $this->matriculations = [];
        foreach ($array as $element) {
            $t = trim($element);
            if (!empty($t)) {
                $this->matriculations[] = $t;
            }
        }

        return (!empty($this->matriculations));
    }

    /**
     * Create the locate string
     * @return string
     */
    public function __createLocateString()
    {
        return '';
    }

    /**
     * Create the where condition
     * @return string
     */
    public function __createWhereCondition()
    {
        global $DIC;

        return ' WHERE ('
            . $DIC->database()->in('matriculation', $this->matriculations, false, 'text')
            . ') ';
    }
}
