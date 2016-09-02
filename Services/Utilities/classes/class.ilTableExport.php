<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/** 
* fim: [export] helper class to export tables (obsolete!)
* May currently export CVS or EXCEL
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id$
* 
* @ingroup ServicesUtilities 
*/
class ilTableExport
{
	var $values = array();
	var $params = array();

	var $next_row = 0;
	var $next_col = 0;
	var $cols = 0;
	var $rows = 0;

	/**
	 * Constructor
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function __construct()
	{
	}

	/**
	 * Set a parameter
	 *
	 * @access public
	 * @param 	string  name
	 * @param 	string  value
	 */
	public function setPar($a_name, $a_value)
	{
		$this->params[$a_name] = $a_value;
	}

	/**
	 * Get a parameter
	 *
	 * @access public
	 * @param 	string  name
	 * @return 	string  value
	 */
	public function getPar($a_name)
	{
		return $this->params[$a_name];
	}
	
	
	/**
	 * Add new row
	 * next row is increased
	 * next column is set to 0
	 *
	 * @access public
	 */
	public function addRow()
	{
		$this->next_col = 0;
		$this->next_row += 1;
		$this->rows = max($this->rows, $this->next_row);
	}
	
	
	/**
	 * Add Column. Will be quoted automatically
	 * Value is written to the next column
	 * Afterwards next column is increased
	 *
	 * @access public
	 * @param 	string  value
	 * @param   string  format
	 */
	public function addColumn($a_value)
	{
		$this->values[$this->next_row][$this->next_col] = $a_value;

		$this->next_col += 1;
		$this->cols = max($this->cols, $this->next_col);
	}

	
	/**
	 * Get the full CSV string
	 *
	 * @access public
	 */
	public function getCSVString()
	{
		include_once('Services/Utilities/classes/class.ilCSVWriter.php');
		$csv = new ilCSVWriter();

		for ($row = 0; $row < $this->rows; $row++)
		{
			for ($col = 0; $col < $this->cols; $col++)
			{
				$csv->addColumn($this->values[$row][$col]);
			}
			$csv->addRow();
		}
		
		return $csv->getCSVString();
	}


	/**
	 * Write Excel file
	 *
	 * @param   string  full server path of the file
	 * @access public
	 */
	public function writeExcelFile($a_filepath)
	{
		global $lng;
		
		include_once "./Services/Excel/classes/class.ilExcelWriterAdapter.php";
		include_once "./Services/Excel/classes/class.ilExcelUtils.php";

        $adapter = new ilExcelWriterAdapter($a_filepath, FALSE);
        $workbook = $adapter->getWorkbook();
		$workbook->setVersion(8); // Use Excel97/2000 Format
		$worksheet = $workbook->addWorksheet(ilExcelUtils::_convert_text($lng->txt("export")));

		if ($this->getPar("hasHeadline"))
		{
			$format = $workbook->addFormat();
			$format->setBold();
			$format->setColor('black');
			$format->setPattern(1);
			$format->setFgColor('silver');
		}

		for ($row = 0; $row < $this->rows; $row++)
		{
			for ($col = 0; $col <$this->cols; $col++)
			{
                $worksheet->write($row, $col, ilExcelUtils::_convert_text($this->values[$row][$col]), $format);
			}
			$format = 0;
		}

		$workbook->close();
	}
}

?>
