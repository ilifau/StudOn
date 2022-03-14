<?php
/* Copyright (c) 2016 Richard Klees <richard.klees@concepts-and-training.de> Extended GPL, see docs/LICENSE */

namespace ILIAS\Setup\CLI;

use ILIAS\Setup\AgentFinder;
use ILIAS\Setup\ArrayEnvironment;
use ILIAS\Setup\Objective\Tentatively;
use ILIAS\Setup\Metrics;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ILIAS\Setup\Agent;
use ILIAS\Setup\Environment;

/**
 * Command to output status information about the installation.
 */
class StatusCommand extends Command
{
    use HasAgent;
    use ObjectiveHelper;

    protected static $defaultName = "status";

    public function __construct(AgentFinder $agent_finder)
    {
        parent::__construct();
        $this->agent_finder = $agent_finder;
    }

    public function configure()
    {
        $this->setDescription("Collect and show status information about the installation.");
        $this->configureCommandForPlugins();
    }


    public function execute(InputInterface $input, OutputInterface $output)
    {
        $agent = $this->getRelevantAgent($input);

        $output->write($this->getMetrics($agent)->toYAML() . "\n");
    }

    public function getMetrics(Agent $agent) : Metrics\Metric
    {
        // ATTENTION: Don't do this (in general), please have a look at the comment
        // in ilIniFilesLoadedObjective.
        \ilIniFilesLoadedObjective::$might_populate_ini_files_as_well = false;

        $environment = new ArrayEnvironment([
            // fau: clientByUrl - add client id to environment for status
            Environment::RESOURCE_CLIENT_ID => $this->getClientIdFromIliasIni()
            // fau.

        ]);
        $storage = new Metrics\ArrayStorage();
        $objective = new Tentatively(
            $agent->getStatusObjective($storage)
        );

        $this->achieveObjective($objective, $environment);

        $metric = $storage->asMetric();
        list($config, $other) = $metric->extractByStability(Metrics\Metric::STABILITY_CONFIG);
        if ($other) {
            $values = $other->getValue();
        } else {
            $values = [];
        }
        if ($config) {
            $values["config"] = $config;
        }

        return new Metrics\Metric(
            Metrics\Metric::STABILITY_MIXED,
            Metrics\Metric::TYPE_COLLECTION,
            $values
        );
    }

    // fau: clientByUrl - new function getClientIdFromIliasIni
    /**
     * Get the client id from the ILIAS ini file
     * (needed in setup for migration command which has no config parameter)
     * @return string|null
     */
    protected function getClientIdFromIliasIni(): ?string
    {
        $path = dirname(__DIR__, 3) . "/ilias.ini.php";
        if (file_exists($path)) {
            $ini = new \ilIniFile($path);
            $ini->read();
            $client_id = $ini->readVariable('clients', 'default');
            if (!empty($client_id)) {
                return $client_id;
            }
        }
        return null;
    }
    // fau.

}
