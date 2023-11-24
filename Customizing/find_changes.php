<?php
if (php_sapi_name() != 'cli') {
    die('only cli allowed');
}
chdir(dirname(__FILE__) . "/..");
findChanges('fau:', '/fau:\s+(\w+)\s+/', 'fau_changes.log');

function findChanges($grep_pattern, $preg_pattern, $logfile) {
    echo "searching for '$grep_pattern' changes...\n";
    $lines = [];
    $changes = [];
    exec("grep -r -e '$grep_pattern'", $lines);
    foreach ($lines as $line) {
        $matches = [];
        preg_match($preg_pattern, $line, $matches);
        if (isset($matches[1])) {
            $found = $matches[1];
            if (!isset($changes[$found])) {
                $changes[$found] = 1;
            }
            else {
                $changes[$found]++;
            }
        }
    }

    $log = "";
    ksort($changes);
    foreach ($changes as $name => $count) {
        $line = $name . "\t" . $count . "\n";
        echo $line;
        $log .= $line;
    }
    file_put_contents(dirname(__FILE__) . '/' . $logfile, $log);
}
