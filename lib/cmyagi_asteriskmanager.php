<?
namespace Istline\Call;

class CMyAGI_AsteriskManager extends \Istline\Call\AGI_AsteriskManager
{
    function __construct($config = NULL, $optconfig = array(), $test)
    {
        if (!is_null($config) && file_exists($config))
            $this->config = parse_ini_file($config, true);
        elseif (file_exists(DEFAULT_PHPAGI_CONFIG))
            $this->config = parse_ini_file(DEFAULT_PHPAGI_CONFIG, true);

        foreach ($optconfig as $var => $val)
            $this->config['asmanager'][$var] = $val;

        if (!isset($this->config['asmanager']['server'])) $this->config['asmanager']['server'] = 'localhost';
        if (!isset($this->config['asmanager']['port'])) $this->config['asmanager']['port'] = 5038;
        if (!isset($this->config['asmanager']['username'])) $this->config['asmanager']['username'] = 'phpagi';
        if (!isset($this->config['asmanager']['secret'])) $this->config['asmanager']['secret'] = 'phpagi';
        $this->wtimeout = $test ? 100 : 18000;
    }

    function wait_response($stopevent = false, $stopParam = false, $stopValue = false)
    {
        $timeout = false;
        $t = time();
        do {
            $type = NULL;
            $parameters = array();

            $buffer = trim(fgets($this->socket, 4096));
            while ($buffer != '') {
                $a = strpos($buffer, ':');
                if ($a) {
                    if (!count($parameters)) {
                        $type = strtolower(substr($buffer, 0, $a));
                        if (substr($buffer, $a + 2) == 'Follows') {
                            $parameters['data'] = '';
                            $buff = fgets($this->socket, 4096);
                            while (substr($buff, 0, 6) != '--END ') {
                                $parameters['data'] .= $buff;
                                $buff = fgets($this->socket, 4096);
                            }
                        }
                    }
                    $parameters[substr($buffer, 0, $a)] = substr($buffer, $a + 2);
                }
                $buffer = trim(fgets($this->socket, 4096));
            }
            switch ($type) {
                case '':
                    $timeout = $allow_timeout;
                    break;
                case 'event':
                    $this->process_event($parameters);
                    if ($stopevent && !is_array($stopevent)) {
                        if (isset($parameters["Event"]) && $parameters["Event"] == $stopevent && isset($parameters[$stopParam]) && $parameters[$stopParam] == $stopValue) {
                            $timeout = true;
                        }
                    } elseif ($stopevent) {
                        foreach ($stopevent as $ev) {
                            if (isset($parameters["Event"]) && $parameters["Event"] == $ev && isset($parameters[$stopParam]) && $parameters[$stopParam] == $stopValue) {
                                $timeout = true;
                            }
                        }
                    }
                    break;
                case 'response':
                    break;
                default:
                    $this->log('Unhandled response packet from Manager: ' . print_r($parameters, true));
                    break;
            }
        } while ($type != 'response' && !$timeout && (time() - $t) < $this->wtimeout);
        return $parameters;
    }
}
