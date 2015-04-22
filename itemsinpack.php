<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * ItemsInPack
 *
 * @author Oleg Kachinsky <logansoleg@gmail.com>
 */
class ItemsInPack extends Module
{
    /**
     * @var boolean error
     */
    protected $_errors = false;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->name          = 'itemsinpack';
        $this->tab           = 'items_in_pack';
        $this->version       = '1.0';
        $this->author        = 'Oleg';
//        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Item in pack');
        $this->description = $this->l('Module for display items in pack\'s.');
    }

    /**
     * Install
     *
     * @return bool
     */
    public function install()
    {
        if (!parent::install()
            || !$this->alterTable('add')
            || !$this->registerHook('header')
            || !$this->registerHook('actionProductUpdate')
            || !$this->registerHook('displayProductButtons')
            || !$this->registerHook('displayAdminProductsExtra')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Un install
     *
     * @return bool
     */
    public function uninstall()
    {
        if (!parent::uninstall() || !$this->alterTable('remove')) {
            return false;
        }

        return true;
    }

    /**
     * Alter table
     *
     * @param string $method Method
     *
     * @return bool
     */
    public function alterTable($method)
    {
        switch ($method) {
            case 'add':
                $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'product ADD `items_in_pack` INT(10) DEFAULT 1';
                break;
            case 'remove':
                $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'product DROP COLUMN `items_in_pack`';
                break;
        }

        if (!Db::getInstance()->Execute($sql)) {
            return false;
        }

        return true;
    }


    /**
     * Hook header
     *
     * @param array $params
     */
    public function hookHeader($params)
    {
        $this->context->controller->addJS($this->_path . '/js/itemsinpack.js');
    }

    /**
     * Hook action product update
     *
     * @param array $params
     */
    public function hookActionProductUpdate($params)
    {
        $idProduct = (int) Tools::getValue('id_product');

        if (!Db::getInstance()->update('product', [
            'items_in_pack' => Tools::getValue('items-in-pack')
        ], ' id_product = ' . $idProduct)
        ) {
            $this->context->controller->_errors[] = Tools::displayError('Error: ') . mysql_error();
        }
    }

    /**
     * Hook display product buttons
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayProductButtons($params)
    {
        if (Validate::isLoadedObject($product = new Product((int) Tools::getValue('id_product')))) {
            $this->prepareNewTab();

            return $this->display(__FILE__, 'product.tpl');
        }
    }

    /**
     * Hook display admin products extra
     *
     * @param array $params Params
     *
     * @return string
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        if (Validate::isLoadedObject($product = new Product((int) Tools::getValue('id_product')))) {
            $this->prepareNewTab();

            return $this->display(__FILE__, 'itemsinpack.tpl');
        }
    }

    /**
     * Get custom field
     *
     * @param integer $idProduct
     *
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function getCustomField($idProduct)
    {
        $result = Db::getInstance()->ExecuteS('SELECT items_in_pack FROM ' . _DB_PREFIX_ . 'product WHERE id_product = ' . (int) $idProduct);

        if (!$result) {
            return [];
        }

        return $result[0]['items_in_pack'];
    }

    /**
     * Prepare new tab
     */
    public function prepareNewTab()
    {
        $idProduct = (int) Tools::getValue('id_product');

        $this->context->smarty->assign('itemsInPack', $this->getCustomField($idProduct));
    }
}

?>
