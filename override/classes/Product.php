<?php

/**
 * Product
 *
 * @author Oleg Kachinsky <logansoleg@gmail.com>
 */
class Product extends ProductCore
{

    /**
     * @param null    $id_product
     * @param bool    $full
     * @param null    $id_lang
     * @param null    $id_shop
     * @param Context $context
     */
    public function __construct($id_product = null, $full = false, $id_lang = null, $id_shop = null, Context $context = null)
    {
        self::$definition['fields']['items_in_pack'] = [
            'type'     => self::TYPE_INT,
            'validate' => 'isInt',
            'shop'     => true
        ];

        parent::__construct($id_product = null, $full = false, $id_lang = null, $id_shop = null, $context);
    }

    /**
     * @var integer Product items in pack
     */
    public $items_in_pack;
}
