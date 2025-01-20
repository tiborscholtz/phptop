<?php

class PHPTopException extends Exception {}
class PHPTop
{
    private const TOP_CMD = "top -b -n 1";
    private const FILE_DATE_FORMAT = "Y_m_d_H_i_s";
    private const COLUMN_TRANSLATIONS = [
        "PID" => "pid",
        "USER" => "user",
        "PR" => "pr",
        "NI" => "ni",
        "VIRT" => "virt",
        "RES" => "res",
        "SHR" => "shr",
        "S" => "s",
        "%CPU" => "cpu",
        "%MEM" => "mem",
        "TIME+" => "time",
        "COMMAND" => "command",
    ];
    private const LINE_CONFIG = [
        1 => [
            "first_explode_param" => "/,/",
            "second_explode_param" => ":",
            "third_explode_param" => "/\s+/",
            "name" => "tasks",
        ],
        2 => [
            "first_explode_param" => "/,\s/",
            "second_explode_param" => ":",
            "third_explode_param" => "/\s+/",
            "name" => "cpus",
        ],
        3 => [
            "first_explode_param" => "/ {2,}/",
            "second_explode_param" => ":",
            "third_explode_param" => "/\s+/",
            "name" => "mem",
        ],
        4 => [
            "first_explode_param" => "/ {2,}/",
            "second_explode_param" => ":",
            "third_explode_param" => "/\s+/",
            "name" => "swap",
        ],
    ];
    private $currentPhpVersion;
    private array $data;
    private function replace_chars($str)
    {
        return strtolower(
            trim(str_replace(["/", ",", "."], ["_", " ", ""], $str))
        );
    }
    public function __construct($loadData = true, $addDebugData = false)
    {
        if (PHP_OS != "Linux") {
            throw new PHPTopException("Operating system not supported!");
        }
        if ($loadData == true) {
            $this->getData($addDebugData);
        }
        if (!defined("PHP_VERSION_ID")) {
            $version = explode(".", PHP_VERSION);
            $this->currentPhpVersion =
                $version[0] * 10000 + $version[1] * 100 + $version[2];
        } else {
            $this->currentPhpVersion = PHP_VERSION_ID;
        }
    }
    /**
     * Converts the data to JSON format and saves it to a file.
     *
     * This method will convert the internal data to a JSON formatted string and
     * save it to a file. If no filename is provided, the current date and time
     * will be used as the filename. The filename will have a `.json` extension
     * appended if it does not already have one.
     *
     * @param string|null $fileName The name of the file to save the JSON data to.
     *                              If NULL, the filename will be the current date
     *                              and time in the format 'Y_m_d_H_i_s'.
     * @return void
     * @throws RuntimeException If the file cannot be written.
     * @example $obj = new PHPTop(); $obj->toJson(); // Saves to a file with the current date and time as the name
     * @example $obj = new PHPTop(); $obj->toJson('data'); // Saves to 'data.json'
     */
    public function toJson($fileName = null)
    {
        if (!isset($fileName)) {
            $fileName = date(self::FILE_DATE_FORMAT);
        }
        if (!str_ends_with($fileName, ".json")) {
            $fileName = $fileName . ".json";
        }
        if (file_put_contents($fileName, json_encode($this->data)) === false) {
            throw new RuntimeException("Failed to write to file: " . $fileName);
        }
        return;
    }
    /**
     * Outputs the internal data as a JSON response.
     *
     * This method sets the Content-Type header to 'application/json' and outputs the
     * internal data as a JSON formatted string. The JSON output is pretty-printed.
     *
     * @return void
     * @example $obj = new PHPTop(); $obj->writeAsJson(); // Outputs the data as a pretty-printed JSON response and exits
     */
    public function writeAsJson()
    {
        header("Content-Type: application/json");
        echo json_encode($this->data, JSON_PRETTY_PRINT);
    }

    public function writeAsXml()
    {
        $xml = $this->getXmlFormat();
        header("Content-Type: text/xml");
        echo $xml->asXML();
    }
    private function dataToXml($array, &$xml)
    {
        foreach ($array as $key => $value) {
            $key = (is_int($key)) ? "process" : $key;
            if (is_array($value)) {
                $label = $xml->addChild($key);
                $this->dataToXml($value, $label);
            } else {
                $xml->addChild($key, $value);
            }
        }
    }
    /**
     * Converts the data to XML format and saves it to a file.
     *
     * This method will convert the internal data to an XML formatted string and
     * save it to a file. If no filename is provided, the current date and time
     * will be used as the filename. The filename will have a `.xml` extension
     * appended if it does not already have one.
     *
     * @param string|null $fileName The name of the file to save the XML data to.
     *                              If NULL, the filename will be the current date
     *                              and time in the format 'Y_m_d_H_i_s'.
     * @return void
     * @throws RuntimeException If the file cannot be written.
     * @example $obj = new PHPTop(); $obj->toXml(); // Saves to a file with the current date and time as the name
     * @example $obj = new PHPTop(); $obj->toXml('data'); // Saves to 'data.xml'
     */
    private function getXmlFormat()
    {
        $xml = new SimpleXMLElement("<root/>");
        $this->dataToXml($this->data, $xml);
        return $xml;
    }
    public function toXml($fileName = null)
    {
        if (!isset($fileName)) {
            $fileName = date(self::FILE_DATE_FORMAT);
        }
        if (!str_ends_with($fileName, ".xml")) {
            $fileName = $fileName . ".xml";
        }
        $xml = $this->getXmlFormat();
        $xml->asXML("./" . $fileName);
    }
    private function compareCpu($a, $b)
    {
        return $b["cpu"] - $a["cpu"];
    }
    /**
     * Returns the first n processes with the most cpu usage
     *
     * This method will searches the top result, and finds the most cpu intensive tasks, and returns them as a list of associative arrays.
     *
     * @param int|null $amount The amount of processess to return.
     *                              If NULL, it defaults to 5.
     * @return array
     * @example $obj = new PHPTop(); $obj->getProcessesByCpu(); // returns 5 processes with the most cpu usage
     * @example $obj = new PHPTop(); $obj->getProcessesByCpu(10); // returns 10 processes with the most cpu usage
     */
    public function getProcessesByCpu($amount = 5)
    {
        if (is_nan($amount)) {
            throw new InvalidArgumentException("amount is not a number");
        }
        $processes = $this->data["values"];
        usort($processes, [$this, "compareCpu"]);
        return array_slice($processes, 0, $amount);
    }
    private function compareMem($a, $b)
    {
        return intval($b["mem"] - $a["mem"]);
    }
    /**
     * Returns the first n processes with the most memory usage
     *
     * This method will searches the top result, and finds the most memory intensive tasks, and returns them as a list of associative arrays.
     *
     * @param int|null $amount The amount of processess to return.
     *                              If NULL, it defaults to 5.
     * @return array
     * @example $obj = new PHPTop(); $obj->getProcessesByMem(); // returns 5 processes with the most memory usage
     * @example $obj = new PHPTop(); $obj->getProcessesByMem(10); // returns 10 processes with the most memory usage
     */
    public function getProcessesByMem($amount = 5)
    {
        if (is_nan($amount)) {
            throw new InvalidArgumentException("amount is not a number");
        }
        $processes = $this->data["values"];
        usort($processes, [$this, "compareMem"]);
        return array_slice($processes, 0, $amount);
    }
    private function getTop()
    {
        $output = shell_exec(self::TOP_CMD);
        return $output;
    }
    private function getDefaultValues()
    {
        return [
            "basic" => [
                "time" => null,
                "uptime" => null,
                "user_count" => 0,
                "load_avg" => [],
            ],
            "tasks" => [
                "total" => -1,
                "running" => -1,
                "sleeping" => -1,
                "stopped" => -1,
                "zombie" => -1,
            ],
            "cpus" => [
                "us" => -1,
                "sy" => -1,
                "ni" => -1,
                "id" => -1,
                "wa" => -1,
                "hi" => -1,
                "si" => -1,
                "st" => -1,
            ],
            "mem" => [
                "total" => -1,
                "free" => -1,
                "used" => -1,
                "buff_cache" => -1,
            ],
            "swap" => [
                "total" => -1,
                "free" => -1,
                "used" => -1,
                "avail_mem" => -1,
            ],
            "columns" => [],
            "values" => [],
            "process_amount" => [],
            "user_amount" => [],
            "process_cpu_percent" => [],
            "process_memory_percent" => [],
        ];
    }
    private function getData($addDebugData = false)
    {
        try {
            $time_start = microtime(true);
            $result = $this->getDefaultValues();
            if ($addDebugData) {
                $result["time_cmd"] = null;
            }
            $output = $this->getTop();
            $lines = explode("\n", $output);
            foreach ($lines as $i => $v) {
                if (empty(trim($v))) {
                    continue;
                }
                if ($i == 0) {
                    $lineExploded = preg_split("/\s{2,}/", $v, -1, PREG_SPLIT_NO_EMPTY);

                    $averages = explode(":", end($lineExploded))[1] ?? ''; // Safe access
                    $userCountLine = prev($lineExploded); // Move pointer to second last
                    $userCount = preg_split("/\s+/", trim($userCountLine))[0] ?? '';
                    $result["basic"]["load_avg"] = preg_split("/\s+/", str_replace(", ", " ", trim($averages)));
                    $result["basic"]["user_count"] = $userCount;
                    $timeData = preg_split("/\s+/", $v);
                    $result["basic"]["time"] = $timeData[2] ?? null; // Safe access
                    $result["basic"]["uptime"] = trim($timeData[4], ",") ?? null; // Safe access
                    continue;
                }
                $handled = false;
                foreach (self::LINE_CONFIG as $sK => $sV) {
                    if ($i == $sK) {
                        $lineExplodedSecond = preg_split(
                            $sV["first_explode_param"],
                            trim(explode($sV["second_explode_param"], $v)[1])
                        );
                        foreach ($lineExplodedSecond as $les) {
                            $lineExplodedSecondAgain = preg_split(
                                $sV["third_explode_param"],
                                trim($les)
                            );
                            $lineExplodedSecondAgain[0] = trim(
                                $lineExplodedSecondAgain[0],
                                ","
                            );
                            $replacedProp = $this->replace_chars(
                                $lineExplodedSecondAgain[1]
                            );
                            if (isset($result[$sV["name"]][$replacedProp])) {
                                $result[$sV["name"]][$replacedProp] = floatval(
                                    str_replace(
                                        ",",
                                        ".",
                                        $lineExplodedSecondAgain[0]
                                    )
                                );
                            }
                        }
                        $handled = true;
                        continue;
                    }
                }
                if ($handled) {
                    continue;
                }
                if ($i == 6) {
                    $result["columns"] = preg_split("/\s+/", trim($v));
                    continue;
                }
                $valueColumn = trim($v);
                $values = preg_split("/\s+/", $valueColumn);
                $one = [];
                foreach ($values as $oneK => $oneV) {
                    $finalColumnName = $result["columns"][$oneK];
                    if (isset(self::COLUMN_TRANSLATIONS[$finalColumnName])) {
                        $finalColumnName =
                            self::COLUMN_TRANSLATIONS[$finalColumnName];
                    }
                    $one[$finalColumnName] = str_replace(",", ".", trim($oneV));
                }
                if (isset($one["command"])) {
                    if (!isset($result["process_amount"][$one["command"]])) {
                        $result["process_amount"][$one["command"]] = 0;
                    }
                    if (!isset($result["process_cpu_percent"][$one["command"]])) {
                        $result["process_cpu_percent"][$one["command"]] = 0;
                    }
                    if (!isset($result["process_memory_percent"][$one["command"]])) {
                        $result["process_memory_percent"][$one["command"]] = 0;
                    }
                    $result["process_cpu_percent"][$one["command"]] += $one["cpu"];
                    $result["process_memory_percent"][$one["command"]] += floatval($one["mem"]);
                    $result["process_amount"][$one["command"]]++;
                }
                if (isset($one["user"])) {
                    $result["user_amount"][$one["user"]] = ($result["user_amount"][$one["user"]] ?? 0 ) + 1;
                }
                $result["values"][] = $one;
            }
            arsort($result["process_amount"]);
            arsort($result["user_amount"]);
            arsort($result["process_cpu_percent"]);
            arsort($result["process_memory_percent"]);
            if ($addDebugData) {
                $time_end = microtime(true);
                $time = $time_end - $time_start;
                $result["time_cmd"] = $time;
            }
            $this->data = $result;
        } catch (PHPTopException $ex) {
        } catch (Exception $ex) {
        }
    }
}
