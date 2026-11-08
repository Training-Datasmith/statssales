<?php

class StatssalesFixtures
{
    public static function ensureLogableState($idState, $logable = 1)
    {
        $db = Db::getInstance();
        $db->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'order_state` (`id_order_state`, `logable`) VALUES ('
            . (int) $idState . ', ' . (int) $logable . ') ON DUPLICATE KEY UPDATE `logable` = ' . (int) $logable
        );
    }

    public static function insertStateLang($idState, $idLang, $name)
    {
        Db::getInstance()->insert(_DB_PREFIX_ . 'order_state_lang', array(
            'id_order_state' => (int) $idState,
            'id_lang' => (int) $idLang,
            'name' => $name,
        ));
    }

    public static function insertAddress($idCountry)
    {
        return Db::getInstance()->insert(_DB_PREFIX_ . 'address', array(
            'id_country' => (int) $idCountry,
        ));
    }

    public static function insertOrder(array $opts)
    {
        $defaults = array(
            'id_shop' => 1,
            'id_address_delivery' => 0,
            'current_state' => 2,
            'conversion_rate' => 1,
            'total_paid_tax_excl' => 0,
            'valid' => 1,
            'invoice_date' => '2020-06-10 12:00:00',
            'date_add' => '2020-06-10 12:00:00',
        );
        $logable = isset($opts['logable']) ? (int) $opts['logable'] : 1;
        unset($opts['logable']);
        $row = array_merge($defaults, $opts);
        self::ensureLogableState((int) $row['current_state'], $logable);

        return Db::getInstance()->insert(_DB_PREFIX_ . 'orders', $row);
    }

    public static function insertDetail($idOrder, $qty)
    {
        return Db::getInstance()->insert(_DB_PREFIX_ . 'order_detail', array(
            'id_order' => (int) $idOrder,
            'product_quantity' => (int) $qty,
        ));
    }

    public static function insertSlip($idOrder, $products, $shipping, $rate = 1)
    {
        return Db::getInstance()->insert(_DB_PREFIX_ . 'order_slip', array(
            'id_order' => (int) $idOrder,
            'conversion_rate' => $rate,
            'total_products_tax_excl' => $products,
            'total_shipping_tax_excl' => $shipping,
        ));
    }

    public static function insertHistory($idOrder, $idState, $dateAdd)
    {
        return Db::getInstance()->insert(_DB_PREFIX_ . 'order_history', array(
            'id_order' => (int) $idOrder,
            'id_order_state' => (int) $idState,
            'date_add' => $dateAdd,
        ));
    }
}
