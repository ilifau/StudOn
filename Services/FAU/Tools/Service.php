<?php declare(strict_types=1);

namespace FAU\Tools;

use ILIAS\DI\Container;

class Service
{
    protected Container $dic;

    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
    }


    /**
     * Quote a text for Export in Excel or CSV
     */
    public function quoteForExport(?string $text) : string
    {
        $text = (string) $text;
        $text = str_replace('"', '', $text);
        $text = str_replace("'", '', $text);
        $text = str_replace("'", '', $text);
        $text = str_replace(",", ' ', $text);
        $text = str_replace(";", ' ', $text);
        $text = str_replace("\n", ' / ', $text);

        return $text;
    }


}