<?php

namespace FAU\Ilias;


use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use ILIAS\DI\Container;

class AbstractExport
{
    const TYPE_EXCEL = 'excel';
    const TYPE_CSV = 'csv';

    protected string $delimiter = ';';
    protected string $enclosure = '"';

    protected $header_style = array(
        'font' => array(
            'bold' => true
        ),
        'fill' => array(
            'type' => 'solid',
            'color' => array('rgb' => 'DDDDDD'),
        )
    );


    protected Container $dic;
    protected \ilLanguage $lng;
    protected Spreadsheet $spreadsheet;


    /**
     * Constructor
     */
    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        $this->lng = $DIC->language();

        $this->spreadsheet = new Spreadsheet();
    }

    /**
     * @param string $delimiter
     */
    public function setDelimiter(string $delimiter) : void
    {
        $this->delimiter = $delimiter;
    }

    /**
     * @param string $enclosure
     */
    public function setEnclosure(string $enclosure) : void
    {
        $this->enclosure = $enclosure;
    }


    /**
     * Fill the header row of a sheet
     * @param array	$columns (column key => header value)
     * @return array mapping (column key => column letter)
     */
    protected function fillHeaderRow(array $columns) : array
    {
        $worksheet = $this->spreadsheet->getActiveSheet();

        $col = 1;
        $mapping = array();
        foreach ($columns as $key => $value)
        {
            $letter = Coordinate::stringFromColumnIndex($col++);
            $mapping[$key] = $letter;
            $coordinate = $letter.'1';
            $cell = $worksheet->getCell($coordinate);
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            $cell->getStyle()->applyFromArray($this->header_style);
            $cell->getStyle()->getAlignment()->setWrapText(true);
        }
        return $mapping;
    }

    /**
     * Fill a sheet row with data
     * @param array 				$data		(column key => header value)
     * @param array					$mapping 	(column key => column letter)
     * @param int					$row 		row number
     */
    protected function fillRowData(array $data, array $mapping, int $row)
    {
        $worksheet = $this->spreadsheet->getActiveSheet();

        foreach ($data as $key => $value)
        {
            $coordinate = $mapping[$key].(string) $row;
            $cell = $worksheet->getCell($coordinate);
            $cell->setValue($value);
            $cell->getStyle()->getAlignment()->setWrapText(true);
        }
    }

    /**
     * Adjust the sizes of the Excel columns
     */
    protected function adjustSizes($range = null)
    {
        $worksheet = $this->spreadsheet->getActiveSheet();

        $range = $range ?? range('A', $worksheet->getHighestColumn());
        foreach ($range as $columnID)
        {
            $worksheet->getColumnDimension($columnID)->setAutoSize(true);
        }
    }


    /**
     * Build an Excel Export file
     * @param string	$name	name of the file (without extension)
     * @param string	$type	type constant for the export
     * @return string   full path of the created file
     */
    public function buildExportFile(string $name, string $type = self::TYPE_EXCEL) : string
    {
        $name = \ilUtil::getASCIIFilename($name);
        $directory = \ilUtil::ilTempnam();
        \ilUtil::makeDirParents($directory);

        switch ($type)
        {
            case self::TYPE_EXCEL:
                $file = $directory . '/' . $name . '.xlsx';
                $writer = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
                $writer->save($file);
                break;

            case self::TYPE_CSV:
                $file = $directory . '/' . $name . '.csv';
                /** @var Csv $writer */
                $writer = IOFactory::createWriter($this->spreadsheet, 'Csv');
                $writer->setDelimiter($this->delimiter);
                $writer->setEnclosure($this->enclosure);
                $writer->save($file);
                break;
        }
        return $file;
    }
}