<?php declare(strict_types=1);

namespace FAU\Tools;

use ILIAS\DI\Container;
use Throwable;

/**
 * Tools needed for data processing
 */
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


    /**
     * Convert a unix timestamp to a string timestamp stored in the database
     * Respect the time zone of ILIAS
     */
    public function unixToDbTimestamp(?int $unix_timestamp): ?string {

        if (empty($unix_timestamp)) {
            return null;
        }

        try {
            $datetime = new \ilDateTime($unix_timestamp, IL_CAL_UNIX);
            return $datetime->get(IL_CAL_DATETIME);
        }
        catch (Throwable $throwable) {
            return null;
        }
    }


    /**
     * Convert a string timestamp stored in the database to a unix timestamp
     * Respect the time zone of ILIAS
     */
    public function dbTimestampToUnix(?string $db_timestamp): ?int
    {
        if (empty($db_timestamp)) {
            return null;
        }

        try {
            $datetime = new \ilDateTime($db_timestamp, IL_CAL_DATETIME);
            return $datetime->get(IL_CAL_UNIX);
        }
        catch (Throwable $throwable) {
            return null;
        }
    }

    /**
     * Convert a unix timestamp to a string timestamp stored in the database
     * Respect the time zone of ILIAS
     */
    public function unixToDbDate(?int $unix_timestamp): ?string {

        if (empty($unix_timestamp)) {
            return null;
        }

        try {
            $date = new \ilDate($unix_timestamp, IL_CAL_UNIX);
            return $date->get(IL_CAL_DATE);
        }
        catch (Throwable $throwable) {
            return null;
        }
    }


    /**
     * Convert a string timestamp stored in the database to a unix timestamp
     * Respect the time zone of ILIAS
     */
    public function dbDateToUnix(?string $db_date): ?int
    {
        if (empty($db_date)) {
            return null;
        }

        try {
            $date = new \ilDate($db_date, IL_CAL_DATE);
            return $date->get(IL_CAL_UNIX);
        }
        catch (Throwable $throwable) {
            return null;
        }
    }


}