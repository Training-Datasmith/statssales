<?php

class Validate
{
    public static function isLoadedObject($object)
    {
        return is_object($object) && isset($object->id) && (int) $object->id > 0;
    }

    public static function isModuleName($name)
    {
        return is_string($name) && preg_match('/^[a-z0-9_]+$/i', $name);
    }
}

class Tools
{
  /** @var array */
    public static $values = array();

    public static function getValue($key)
    {
        return array_key_exists($key, self::$values) ? self::$values[$key] : false;
    }

    public static function safeOutput($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    public static function file_exists_cache($path)
    {
        return file_exists($path);
    }

    public static function displayError()
    {
        return 'Error';
    }

    public static function resetValues()
    {
        self::$values = array();
    }
}

class Configuration
{
    /** @var array */
    public static $store = array('PS_CURRENCY_DEFAULT' => 1, 'PS_STATS_RENDER' => 'graphnvd3');

    public static function get($key)
    {
        return array_key_exists($key, self::$store) ? self::$store[$key] : null;
    }

    public static function resetStore()
    {
        self::$store = array('PS_CURRENCY_DEFAULT' => 1, 'PS_STATS_RENDER' => 'graphnvd3');
    }
}

class Currency
{
    /** @var array */
    public static $map = array(1 => 'EUR');

    public $id;
    public $iso_code;

    public function __construct($id)
    {
        $this->id = (int) $id;
        if (!isset(self::$map[$this->id])) {
            throw new RuntimeException('Unknown currency id');
        }
        $this->iso_code = self::$map[$this->id];
    }

    public static function resetMap()
    {
        self::$map = array(1 => 'EUR');
    }
}

class Country
{
    /** @var array */
    public static $byLang = array();

    public static function getCountries($langId)
    {
        $langId = (int) $langId;
        if (isset(self::$byLang[$langId])) {
            return self::$byLang[$langId];
        }

        return array();
    }

    public static function resetCountries()
    {
        self::$byLang = array();
    }
}

class Employee
{
    public $id;
    public $stats_date_from;
    public $stats_date_to;

    public function __construct($id)
    {
        $this->id = (int) $id;
        $this->stats_date_from = '2020-06-01';
        $this->stats_date_to = '2020-06-20';
    }

    public function update()
    {
        return true;
    }
}

class LocaleSpy
{
    /** @var array */
    public static $calls = array();

    public static function reset()
    {
        self::$calls = array();
    }

    public static function formatPrice($amount, $iso)
    {
        self::$calls[] = array($amount, $iso);
        if (!is_numeric($amount)) {
            return 'PRICE:' . $iso . ':INVALID';
        }

        return 'PRICE:' . $iso . ':' . number_format((float) $amount, 2, '.', '');
    }
}

class TestLocale
{
    public function formatPrice($amount, $iso)
    {
        return LocaleSpy::formatPrice($amount, $iso);
    }
}

class Context
{
    /** @var Context */
    private static $instance;

    public $language;
    public $employee;
    private $locale;

    public function __construct()
    {
        $this->language = new stdClass();
        $this->language->id = 7;
        $this->employee = new Employee(1);
        $this->locale = new TestLocale();
    }

    public static function getContext()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function resetContext()
    {
        self::$instance = new self();
    }

    public function getCurrentLocale()
    {
        return $this->locale;
    }

    public function getTranslator()
    {
        return new stdClass();
    }
}

class ModuleTranslatorSpy
{
    /** @var array */
    public static $log = array();

    public static function reset()
    {
        self::$log = array();
    }
}

class Module
{
    public $name;
    public $tab;
    public $version;
    public $author;
    public $need_instance;
    public $displayName;
    public $description;
    public $ps_versions_compliancy;
    public $context;

    /** @var bool */
    public static $parentInstallReturns = true;

    /** @var array */
    public static $registeredHooks = array();

    /** @var array */
    public static $unregisteredHooks = array();

    /** @var array */
    public static $engineCalls = array();

    protected $_csv = '';

    public function __construct()
    {
        $this->context = Context::getContext();
    }

    public function install()
    {
        return self::$parentInstallReturns;
    }

    public function registerHook($hook)
    {
        self::$registeredHooks[] = $hook;

        return true;
    }

    public function unregisterHook($hook)
    {
        self::$unregisteredHooks[] = $hook;

        return true;
    }

    public static function resetModuleStatics()
    {
        self::$parentInstallReturns = true;
        self::$registeredHooks = array();
        self::$unregisteredHooks = array();
        self::$engineCalls = array();
    }

    protected function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        ModuleTranslatorSpy::$log[] = array('id' => $id, 'parameters' => $parameters, 'domain' => $domain);
        $translated = $id;
        $translated = htmlspecialchars($translated, ENT_NOQUOTES, 'UTF-8');
        if (!empty($parameters) && self::isSprintfString($id)) {
            $translated = vsprintf($translated, $parameters);
        } elseif (!empty($parameters)) {
            $translated = strtr($translated, $parameters);
        }

        return $translated;
    }

    private static function isSprintfString($string)
    {
        $regexSprintf = '#(?:%%|%(?:[0-9]+\$)?[+-]?(?:[ 0]|\'.)?-?[0-9]*(?:\.[0-9]+)?[bcdeufFosxX])#';
        $regexClassic = '/%\w+%/';

        return (bool) preg_match_all($regexSprintf, $string)
            && !(bool) preg_match_all($regexClassic, $string);
    }
}

class Shop
{
    const SHARE_ORDER = 'order';

    /** @var int */
    public static $contextShopId = 1;

    public static function getContextShopID()
    {
        return self::$contextShopId;
    }

    public static function getContextListShopID($share = false)
    {
        return array(self::$contextShopId);
    }

    public static function addSqlRestriction($share = false, $alias = null)
    {
        if ($alias) {
            $alias .= '.';
        }

        return ' AND ' . $alias . 'id_shop IN (' . implode(', ', self::getContextListShopID($share)) . ') ';
    }

    public static function resetShop()
    {
        self::$contextShopId = 1;
    }
}

class Db
{
    /** @var Db */
    private static $instance;

    /** @var mysqli */
    private $link;

    private function __construct()
    {
        $host = getenv('STATSSALES_DB_HOST') ? getenv('STATSSALES_DB_HOST') : '127.0.0.1';
        $port = getenv('STATSSALES_DB_PORT') ? (int) getenv('STATSSALES_DB_PORT') : 3306;
        $user = getenv('STATSSALES_DB_USER') ? getenv('STATSSALES_DB_USER') : 'statssales';
        $pass = getenv('STATSSALES_DB_PASSWORD') ? getenv('STATSSALES_DB_PASSWORD') : 'statssales';
        $name = getenv('STATSSALES_DB_NAME') ? getenv('STATSSALES_DB_NAME') : 'statssales_test';

        $this->link = mysqli_init();
        if (!$this->link) {
            throw new RuntimeException('mysqli_init failed');
        }
        if (!mysqli_real_connect($this->link, $host, $user, $pass, $name, $port)) {
            throw new RuntimeException('mysqli_connect failed: ' . mysqli_connect_error());
        }
        mysqli_set_charset($this->link, 'utf8');
    }

    public static function getInstance($slave = false)
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getRow($sql)
    {
        $result = mysqli_query($this->link, $sql);
        if ($result === false) {
            throw new RuntimeException('SQL getRow error: ' . mysqli_error($this->link) . ' SQL: ' . $sql);
        }
        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        if ($row === null) {
            return false;
        }

        return $row;
    }

    public function executeS($sql)
    {
        $result = mysqli_query($this->link, $sql);
        if ($result === false) {
            throw new RuntimeException('SQL executeS error: ' . mysqli_error($this->link) . ' SQL: ' . $sql);
        }
        $rows = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        mysqli_free_result($result);

        return $rows;
    }

    public function execute($sql)
    {
        if (mysqli_query($this->link, $sql) === false) {
            throw new RuntimeException('SQL execute error: ' . mysqli_error($this->link) . ' SQL: ' . $sql);
        }

        return true;
    }

    public function insert($table, $data)
    {
        $cols = array();
        $vals = array();
        foreach ($data as $k => $v) {
            $cols[] = '`' . $k . '`';
            if ($v === null) {
                $vals[] = 'NULL';
            } elseif (is_int($v) || is_float($v)) {
                $vals[] = (string) $v;
            } else {
                $vals[] = "'" . mysqli_real_escape_string($this->link, $v) . "'";
            }
        }
        $sql = 'INSERT INTO `' . $table . '` (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
        $this->execute($sql);

        return (int) mysqli_insert_id($this->link);
    }

    public function getLink()
    {
        return $this->link;
    }

    public static function ensureSchema()
    {
        $db = self::getInstance();
        $prefix = _DB_PREFIX_;
        $statements = array(
            "CREATE TABLE IF NOT EXISTS `{$prefix}orders` (
                `id_order` INT NOT NULL AUTO_INCREMENT,
                `id_shop` INT NOT NULL DEFAULT 1,
                `id_address_delivery` INT NOT NULL DEFAULT 0,
                `current_state` INT NOT NULL DEFAULT 0,
                `conversion_rate` DECIMAL(13,6) NOT NULL DEFAULT 1,
                `total_paid_tax_excl` DECIMAL(20,6) NOT NULL DEFAULT 0,
                `valid` TINYINT(1) NOT NULL DEFAULT 1,
                `invoice_date` DATETIME NULL,
                `date_add` DATETIME NULL,
                PRIMARY KEY (`id_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}order_slip` (
                `id_order_slip` INT NOT NULL AUTO_INCREMENT,
                `id_order` INT NOT NULL,
                `conversion_rate` DECIMAL(13,6) NOT NULL DEFAULT 1,
                `total_products_tax_excl` DECIMAL(20,6) NOT NULL DEFAULT 0,
                `total_shipping_tax_excl` DECIMAL(20,6) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_order_slip`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}order_state` (
                `id_order_state` INT NOT NULL,
                `logable` TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (`id_order_state`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}order_state_lang` (
                `id_order_state` INT NOT NULL,
                `id_lang` INT NOT NULL,
                `name` VARCHAR(64) NOT NULL,
                PRIMARY KEY (`id_order_state`, `id_lang`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}order_history` (
                `id_order_history` INT NOT NULL AUTO_INCREMENT,
                `id_order` INT NOT NULL,
                `id_order_state` INT NOT NULL,
                `date_add` DATETIME NOT NULL,
                PRIMARY KEY (`id_order_history`),
                KEY `id_order` (`id_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}order_detail` (
                `id_order_detail` INT NOT NULL AUTO_INCREMENT,
                `id_order` INT NOT NULL,
                `product_quantity` INT NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_order_detail`),
                KEY `id_order` (`id_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$prefix}address` (
                `id_address` INT NOT NULL AUTO_INCREMENT,
                `id_country` INT NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_address`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        );
        foreach ($statements as $sql) {
            $db->execute($sql);
        }
        $db->execute("INSERT IGNORE INTO `{$prefix}order_state` (`id_order_state`, `logable`) VALUES (2, 1), (3, 1)");
    }

    public static function truncateAll()
    {
        $db = self::getInstance();
        $prefix = _DB_PREFIX_;
        foreach (array('order_history', 'order_detail', 'order_slip', 'orders', 'order_state_lang', 'address') as $t) {
            $db->execute('TRUNCATE TABLE `' . $prefix . $t . '`');
        }
    }
}

abstract class ModuleGraph extends Module
{
    protected $_employee;
    protected $_values = array();
    protected $_legend = array();
    protected $_titles = array('main' => null, 'x' => null, 'y' => null);
    protected $_render;
    protected $_id_lang;

    abstract protected function getData($layers);

    public function setEmployee($id_employee)
    {
        $this->_employee = new Employee($id_employee);
        $ctx = Context::getContext()->employee;
        $this->_employee->stats_date_from = $ctx->stats_date_from;
        $this->_employee->stats_date_to = $ctx->stats_date_to;
    }

    public function setLang($id_lang)
    {
        $this->_id_lang = $id_lang;
    }

    protected function setDateGraph($layers, $legend = false)
    {
        $from_array = getdate(strtotime($this->_employee->stats_date_from));
        $to_array = getdate(strtotime($this->_employee->stats_date_to));

        if ($this->_employee->stats_date_from == $this->_employee->stats_date_to) {
            if ($legend) {
                for ($i = 0; $i < 24; ++$i) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; ++$j) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = ($i % 2) ? '' : sprintf('%02dh', $i);
                }
            }
            if (is_callable(array($this, 'setDayValues'))) {
                $this->setDayValues($layers);
            }
        } elseif (strtotime($this->_employee->stats_date_to) - strtotime($this->_employee->stats_date_from) <= 2678400) {
            if ($legend) {
                $days = array();
                if ($from_array['mon'] == $to_array['mon']) {
                    for ($i = $from_array['mday']; $i <= $to_array['mday']; ++$i) {
                        $days[] = $i;
                    }
                } else {
                    $imax = date('t', mktime(0, 0, 0, $from_array['mon'], 1, $from_array['year']));
                    for ($i = $from_array['mday']; $i <= $imax; ++$i) {
                        $days[] = $i;
                    }
                    for ($i = 1; $i <= $to_array['mday']; ++$i) {
                        $days[] = $i;
                    }
                }
                foreach ($days as $i) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; ++$j) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = ($i % 2) ? '' : sprintf('%02d', $i);
                }
            }
            if (is_callable(array($this, 'setMonthValues'))) {
                $this->setMonthValues($layers);
            }
        } elseif (strtotime('-1 year', strtotime($this->_employee->stats_date_to)) < strtotime($this->_employee->stats_date_from)) {
            if ($legend) {
                $months = array();
                if ($from_array['year'] == $to_array['year']) {
                    for ($i = $from_array['mon']; $i <= $to_array['mon']; ++$i) {
                        $months[] = $i;
                    }
                } else {
                    for ($i = $from_array['mon']; $i <= 12; ++$i) {
                        $months[] = $i;
                    }
                    for ($i = 1; $i <= $to_array['mon']; ++$i) {
                        $months[] = $i;
                    }
                }
                foreach ($months as $i) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; ++$j) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = sprintf('%02d', $i);
                }
            }
            if (is_callable(array($this, 'setYearValues'))) {
                $this->setYearValues($layers);
            }
        } else {
            if ($legend) {
                $years = array();
                for ($i = $from_array['year']; $i <= $to_array['year']; ++$i) {
                    $years[] = $i;
                }
                foreach ($years as $i) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; ++$j) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = sprintf('%04d', $i);
                }
            }
            if (is_callable(array($this, 'setAllTimeValues'))) {
                $this->setAllTimeValues($layers);
            }
        }
    }

    protected function csvExport($datas)
    {
        $context = Context::getContext();

        $this->setEmployee($context->employee->id);
        $this->setLang($context->language->id);

        $layers = isset($datas['layers']) ? $datas['layers'] : 1;
        if (isset($datas['option'])) {
            $this->setOption($datas['option'], $layers);
        }
        $this->getData($layers);

        $this->_csv = '';
        if (is_array($this->_titles['main'])) {
            for ($i = 0, $total_main = count($this->_titles['main']); $i <= $total_main; ++$i) {
                if ($i > 0) {
                    $this->_csv .= ';';
                }
                if (isset($this->_titles['main'][$i])) {
                    $this->_csv .= $this->_titles['main'][$i];
                }
            }
        } else {
            $this->_csv .= ';' . $this->_titles['main'];
        }
        $this->_csv .= "\n";
        if (count($this->_legend)) {
            $total = 0;
            if ($datas['type'] == 'pie') {
                foreach ($this->_legend as $key => $legend) {
                    for ($i = 0, $total_main = (is_array($this->_titles['main']) ? count($this->_values) : 1); $i < $total_main; ++$i) {
                        $total += (is_array($this->_values[$i]) ? $this->_values[$i][$key] : $this->_values[$key]);
                    }
                }
            }
            foreach ($this->_legend as $key => $legend) {
                $this->_csv .= $legend . ';';
                for ($i = 0, $total_main = (is_array($this->_titles['main']) ? count($this->_values) : 1); $i < $total_main; ++$i) {
                    if (!isset($this->_values[$i]) || !is_array($this->_values[$i])) {
                        if (isset($this->_values[$key])) {
                            if (is_numeric($this->_values[$key])) {
                                $this->_csv .= $this->_values[$key] / (($datas['type'] == 'pie') ? $total : 1);
                            } else {
                                $this->_csv .= $this->_values[$key];
                            }
                        } else {
                            $this->_csv .= '0';
                        }
                    } else {
                        if (is_numeric($this->_values[$i][$key])) {
                            $this->_csv .= $this->_values[$i][$key] / (($datas['type'] == 'pie') ? $total : 1);
                        } else {
                            $this->_csv .= $this->_values[$i][$key];
                        }
                    }
                    $this->_csv .= ';';
                }
                $this->_csv .= "\n";
            }
        }
        $this->_displayCsv();
    }

    protected function _displayCsv()
    {
        if (ob_get_level() && ob_get_length() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $this->displayName . ' - ' . time() . '.csv"');
        echo $this->_csv;
        exit;
    }

    public function setOption($option, $layers = 1)
    {
    }

    public function engine($params)
    {
        $raw = $params;
        Module::$engineCalls[] = $raw;

        if (!isset($params['layers'])) {
            $params['layers'] = 1;
        }
        if (!isset($params['type'])) {
            $params['type'] = 'column';
        }

        return 'ENGINE:' . $params['type'] . ':' . $params['option'] . ':layers=' . $params['layers'];
    }

    protected static function getEmployee($employee = null, Context $context = null)
    {
        if (!Validate::isLoadedObject($employee)) {
            if (!$context) {
                $context = Context::getContext();
            }
            if (!Validate::isLoadedObject($context->employee)) {
                return false;
            }
            $employee = $context->employee;
        }

        if (empty($employee->stats_date_from) || empty($employee->stats_date_to)
            || $employee->stats_date_from == '0000-00-00' || $employee->stats_date_to == '0000-00-00') {
            if (empty($employee->stats_date_from) || $employee->stats_date_from == '0000-00-00') {
                $employee->stats_date_from = date('Y') . '-01-01';
            }
            if (empty($employee->stats_date_to) || $employee->stats_date_to == '0000-00-00') {
                $employee->stats_date_to = date('Y') . '-12-31';
            }
            $employee->update();
        }

        return $employee;
    }

    public function getDate()
    {
        return ModuleGraph::getDateBetween($this->_employee);
    }

    public static function getDateBetween($employee = null)
    {
        if ($employee = ModuleGraph::getEmployee($employee)) {
            return ' \'' . pSQL($employee->stats_date_from) . ' 00:00:00\' AND \'' . pSQL($employee->stats_date_to) . ' 23:59:59\' ';
        }

        return ' \'' . date('Y-m') . '-01 00:00:00\' AND \'' . date('Y-m-t') . ' 23:59:59\' ';
    }

    public function getLang()
    {
        return $this->_id_lang;
    }
}

function pSQL($string)
{
    return str_replace("'", "\\'", $string);
}
