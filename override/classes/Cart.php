<?php

/**
 * Cart
 *
 * @author Oleg Kachinsky <logansoleg@gmail.com>
 */
class Cart extends CartCore
{
    /**
     * Return cart products
     *
     * @param bool $refresh
     * @param bool $id_product
     * @param null $id_country
     *
     * @return array Products
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getProducts($refresh = false, $id_product = false, $id_country = null)
    {
        if (!$this->id) {
            return [];
        }
        // Product cache must be strictly compared to NULL, or else an empty cart will add dozens of queries
        if ($this->_products !== null && !$refresh) {
            // Return product row with specified ID if it exists
            if (is_int($id_product)) {
                foreach ($this->_products as $product) {
                    if ($product['id_product'] == $id_product) {
                        return [$product];
                    }
                }

                return [];
            }

            return $this->_products;
        }

        // Build query
        $sql = new DbQuery();

        // Build SELECT
        $sql->select('cp.`id_product_attribute`, cp.`id_product`, cp.`quantity` AS cart_quantity, cp.id_shop, pl.`name`, p.`is_virtual`,
						pl.`description_short`, pl.`available_now`, pl.`available_later`, product_shop.`id_category_default`, p.`id_supplier`,
						p.`id_manufacturer`, p.`items_in_pack`, product_shop.`on_sale`, product_shop.`ecotax`, product_shop.`additional_shipping_cost`,
						product_shop.`available_for_order`, product_shop.`price`, product_shop.`active`, product_shop.`unity`, product_shop.`unit_price_ratio`,
						stock.`quantity` AS quantity_available, p.`width`, p.`height`, p.`depth`, stock.`out_of_stock`, p.`weight`,
						p.`date_add`, p.`date_upd`, IFNULL(stock.quantity, 0) as quantity, pl.`link_rewrite`, cl.`link_rewrite` AS category,
						CONCAT(LPAD(cp.`id_product`, 10, 0), LPAD(IFNULL(cp.`id_product_attribute`, 0), 10, 0), IFNULL(cp.`id_address_delivery`, 0)) AS unique_id, cp.id_address_delivery,
						product_shop.advanced_stock_management, ps.product_supplier_reference supplier_reference, IFNULL(sp.`reduction_type`, 0) AS reduction_type');

        // Build FROM
        $sql->from('cart_product', 'cp');

        // Build JOIN
        $sql->leftJoin('product', 'p', 'p.`id_product` = cp.`id_product`');
        $sql->innerJoin('product_shop', 'product_shop', '(product_shop.`id_shop` = cp.`id_shop` AND product_shop.`id_product` = p.`id_product`)');
        $sql->leftJoin('product_lang', 'pl', '
			p.`id_product` = pl.`id_product`
			AND pl.`id_lang` = ' . (int) $this->id_lang . Shop::addSqlRestrictionOnLang('pl', 'cp.id_shop')
        );

        $sql->leftJoin('category_lang', 'cl', '
			product_shop.`id_category_default` = cl.`id_category`
			AND cl.`id_lang` = ' . (int) $this->id_lang . Shop::addSqlRestrictionOnLang('cl', 'cp.id_shop')
        );

        $sql->leftJoin('product_supplier', 'ps', 'ps.`id_product` = cp.`id_product` AND ps.`id_product_attribute` = cp.`id_product_attribute` AND ps.`id_supplier` = p.`id_supplier`');

        $sql->leftJoin('specific_price', 'sp', 'sp.`id_product` = cp.`id_product`'); // AND 'sp.`id_shop` = cp.`id_shop`

        // @todo test if everything is ok, then refactorise call of this method
        $sql->join(Product::sqlStock('cp', 'cp'));

        // Build WHERE clauses
        $sql->where('cp.`id_cart` = ' . (int) $this->id);
        if ($id_product) {
            $sql->where('cp.`id_product` = ' . (int) $id_product);
        }
        $sql->where('p.`id_product` IS NOT NULL');

        // Build GROUP BY
        $sql->groupBy('unique_id');

        // Build ORDER BY
        $sql->orderBy('cp.`date_add`, p.`id_product`, cp.`id_product_attribute` ASC');

        if (Customization::isFeatureActive()) {
            $sql->select('cu.`id_customization`, cu.`quantity` AS customization_quantity');
            $sql->leftJoin('customization', 'cu',
                'p.`id_product` = cu.`id_product` AND cp.`id_product_attribute` = cu.`id_product_attribute` AND cu.`id_cart` = '
                . (int) $this->id);
        } else {
            $sql->select('NULL AS customization_quantity, NULL AS id_customization');
        }

        if (Combination::isFeatureActive()) {
            $sql->select('
				product_attribute_shop.`price` AS price_attribute, product_attribute_shop.`ecotax` AS ecotax_attr,
				IF (IFNULL(pa.`reference`, \'\') = \'\', p.`reference`, pa.`reference`) AS reference,
				(p.`weight`+ pa.`weight`) weight_attribute,
				IF (IFNULL(pa.`ean13`, \'\') = \'\', p.`ean13`, pa.`ean13`) AS ean13,
				IF (IFNULL(pa.`upc`, \'\') = \'\', p.`upc`, pa.`upc`) AS upc,
				pai.`id_image` as pai_id_image, il.`legend` as pai_legend,
				IFNULL(product_attribute_shop.`minimal_quantity`, product_shop.`minimal_quantity`) as minimal_quantity,
				IF(product_attribute_shop.wholesale_price > 0,  product_attribute_shop.wholesale_price, product_shop.`wholesale_price`) wholesale_price
			');

            $sql->leftJoin('product_attribute', 'pa', 'pa.`id_product_attribute` = cp.`id_product_attribute`');
            $sql->leftJoin('product_attribute_shop', 'product_attribute_shop', '(product_attribute_shop.`id_shop` = cp.`id_shop` AND product_attribute_shop.`id_product_attribute` = pa.`id_product_attribute`)');
            $sql->leftJoin('product_attribute_image', 'pai', 'pai.`id_product_attribute` = pa.`id_product_attribute`');
            $sql->leftJoin('image_lang', 'il', 'il.`id_image` = pai.`id_image` AND il.`id_lang` = '
                                               . (int) $this->id_lang);
        } else {
            $sql->select(
                'p.`reference` AS reference, p.`ean13`,
				p.`upc` AS upc, product_shop.`minimal_quantity` AS minimal_quantity, product_shop.`wholesale_price` wholesale_price'
            );
        }
        $result = Db::getInstance()->executeS($sql);

        // Reset the cache before the following return, or else an empty cart will add dozens of queries
        $products_ids = [];
        $pa_ids       = [];
        if ($result) {
            foreach ($result as $row) {
                $products_ids[] = $row['id_product'];
                $pa_ids[]       = $row['id_product_attribute'];
            }
        }
        // Thus you can avoid one query per product, because there will be only one query for all the products of the cart
        Product::cacheProductsFeatures($products_ids);
        Cart::cacheSomeAttributesLists($pa_ids, $this->id_lang);

        $this->_products = [];
        if (empty($result)) {
            return [];
        }

        $cart_shop_context = Context::getContext()->cloneContext();
        foreach ($result as &$row) {
            if (isset($row['ecotax_attr']) && $row['ecotax_attr'] > 0) {
                $row['ecotax'] = (float) $row['ecotax_attr'];
            }

            $row['stock_quantity'] = (int) $row['quantity'];
            // for compatibility with 1.2 themes
            $row['quantity'] = (int) $row['cart_quantity'];

            if (isset($row['id_product_attribute']) && (int) $row['id_product_attribute']
                && isset($row['weight_attribute'])
            ) {
                $row['weight'] = (float) $row['weight_attribute'];
            }

            if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice') {
                $address_id = (int) $this->id_address_invoice;
            } else {
                $address_id = (int) $row['id_address_delivery'];
            }
            if (!Address::addressExists($address_id)) {
                $address_id = null;
            }

            if ($cart_shop_context->shop->id != $row['id_shop']) {
                $cart_shop_context->shop = new Shop((int) $row['id_shop']);
            }

            $address            = Address::initialize($address_id, true);
            $id_tax_rules_group = Product::getIdTaxRulesGroupByIdProduct((int) $row['id_product'], $cart_shop_context);
            $tax_calculator     = TaxManagerFactory::getManager($address, $id_tax_rules_group)->getTaxCalculator();

            $row['price'] = Product::getPriceStatic(
                (int) $row['id_product'],
                false,
                isset($row['id_product_attribute']) ? (int) $row['id_product_attribute'] : null,
                6,
                null,
                false,
                true,
                $row['cart_quantity'],
                false,
                (int) $this->id_customer ? (int) $this->id_customer : null,
                (int) $this->id,
                $address_id,
                $specific_price_output,
                false,
                true,
                $cart_shop_context
            );

            switch (Configuration::get('PS_ROUND_TYPE')) {
                case Order::ROUND_TOTAL:
                    $row['total']    = $row['price'] * (int) $row['cart_quantity'];
                    $row['total_wt'] = $tax_calculator->addTaxes($row['price']) * (int) $row['cart_quantity'];
                    break;
                case Order::ROUND_LINE:
                    $row['total']    = Tools::ps_round($row['price']
                                                       * (int) $row['cart_quantity'], _PS_PRICE_COMPUTE_PRECISION_);
                    $row['total_wt'] = Tools::ps_round($tax_calculator->addTaxes($row['price'])
                                                       * (int) $row['cart_quantity'], _PS_PRICE_COMPUTE_PRECISION_);
                    break;

                case Order::ROUND_ITEM:
                default:
                    $row['total'] = Tools::ps_round($row['price'], _PS_PRICE_COMPUTE_PRECISION_)
                                    * (int) $row['cart_quantity'];
                    $row['total_wt']
                                  =
                        Tools::ps_round($tax_calculator->addTaxes($row['price']), _PS_PRICE_COMPUTE_PRECISION_)
                        * (int) $row['cart_quantity'];
                    break;
            }

            $row['price_wt']          = $tax_calculator->addTaxes($row['price']);
            $row['description_short'] = Tools::nl2br($row['description_short']);

            if (!isset($row['pai_id_image']) || $row['pai_id_image'] == 0) {
                $cache_id
                    = 'Cart::getProducts_' . '-pai_id_image-' . (int) $row['id_product'] . '-' . (int) $this->id_lang
                      . '-' . (int) $row['id_shop'];
                if (!Cache::isStored($cache_id)) {
                    $row2 = Db::getInstance()->getRow('
						SELECT image_shop.`id_image` id_image, il.`legend`
						FROM `' . _DB_PREFIX_ . 'image` i
						JOIN `' . _DB_PREFIX_
                                                      . 'image_shop` image_shop ON (i.id_image = image_shop.id_image AND image_shop.cover=1 AND image_shop.id_shop='
                                                      . (int) $row['id_shop'] . ')
						LEFT JOIN `' . _DB_PREFIX_
                                                      . 'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = '
                                                      . (int) $this->id_lang . ')
						WHERE i.`id_product` = ' . (int) $row['id_product'] . ' AND image_shop.`cover` = 1'
                    );
                    Cache::store($cache_id, $row2);
                }
                $row2 = Cache::retrieve($cache_id);
                if (!$row2) {
                    $row2 = ['id_image' => false, 'legend' => false];
                } else {
                    $row = array_merge($row, $row2);
                }
            } else {
                $row['id_image'] = $row['pai_id_image'];
                $row['legend']   = $row['pai_legend'];
            }

            $row['reduction_applies']         = ($specific_price_output && (float) $specific_price_output['reduction']);
            $row['quantity_discount_applies'] = ($specific_price_output
                                                 && $row['cart_quantity']
                                                    >= (int) $specific_price_output['from_quantity']);
            $row['id_image']                  = Product::defineProductImage($row, $this->id_lang);
            $row['allow_oosp']                = Product::isAvailableWhenOutOfStock($row['out_of_stock']);
            $row['features']                  = Product::getFeaturesStatic((int) $row['id_product']);

            if (array_key_exists($row['id_product_attribute'] . '-' . $this->id_lang, self::$_attributesLists)) {
                $row = array_merge($row, self::$_attributesLists[$row['id_product_attribute'] . '-' . $this->id_lang]);
            }

            $row = Product::getTaxesInformations($row, $cart_shop_context);

            $this->_products[] = $row;
        }

        return $this->_products;
    }

    /**
     * Return useful informations for cart
     *
     * @param null $id_lang
     * @param bool $refresh
     *
     * @return array Cart details
     */
    public function getSummaryDetails($id_lang = null, $refresh = false)
    {
        $context = Context::getContext();
        if (!$id_lang) {
            $id_lang = $context->language->id;
        }

        $delivery = new Address((int) $this->id_address_delivery);
        $invoice  = new Address((int) $this->id_address_invoice);

        // New layout system with personalization fields
        $formatted_addresses = [
            'delivery' => AddressFormat::getFormattedLayoutData($delivery),
            'invoice'  => AddressFormat::getFormattedLayoutData($invoice)
        ];

        $base_total_tax_inc = $this->getOrderTotal(true);
        $base_total_tax_exc = $this->getOrderTotal(false);

        $total_tax = $base_total_tax_inc - $base_total_tax_exc;

        if ($total_tax < 0) {
            $total_tax = 0;
        }

        $currency = new Currency($this->id_currency);

        $products                = $this->getProducts($refresh);
        $gift_products           = [];
        $cart_rules              = $this->getCartRules();
        $total_shipping          = $this->getTotalShippingCost();
        $total_shipping_tax_exc  = $this->getTotalShippingCost(null, false);
        $total_products_wt       = $this->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        $total_products          = $this->getOrderTotal(false, Cart::ONLY_PRODUCTS);
        $total_discounts         = $this->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
        $total_discounts_tax_exc = $this->getOrderTotal(false, Cart::ONLY_DISCOUNTS);

        // The cart content is altered for display
        foreach ($cart_rules as &$cart_rule) {

            // If the cart rule is automatic (wihtout any code) and include free shipping, it should not be displayed as a cart rule but only set the shipping cost to 0
            if ($cart_rule['free_shipping']
                && (empty($cart_rule['code'])
                    || preg_match('/^' . CartRule::BO_ORDER_CODE_PREFIX . '[0-9]+/', $cart_rule['code']))
            ) {
                $cart_rule['value_real'] -= $total_shipping;
                $cart_rule['value_tax_exc'] -= $total_shipping_tax_exc;
                $cart_rule['value_real']    = Tools::ps_round($cart_rule['value_real'],
                    (int) $context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                $cart_rule['value_tax_exc'] = Tools::ps_round($cart_rule['value_tax_exc'],
                    (int) $context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                if ($total_discounts > $cart_rule['value_real']) {
                    $total_discounts -= $total_shipping;
                }
                if ($total_discounts_tax_exc > $cart_rule['value_tax_exc']) {
                    $total_discounts_tax_exc -= $total_shipping_tax_exc;
                }

                // Update total shipping
                $total_shipping         = 0;
                $total_shipping_tax_exc = 0;
            }
            if ($cart_rule['gift_product']) {
                foreach ($products as $key => &$product) {
                    if (empty($product['gift']) && $product['id_product'] == $cart_rule['gift_product']
                        && $product['id_product_attribute'] == $cart_rule['gift_product_attribute']
                    ) {
                        // Update total products
                        $total_products_wt = Tools::ps_round($total_products_wt - $product['price_wt'],
                            (int) $context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        $total_products    = Tools::ps_round($total_products - $product['price'],
                            (int) $context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);

                        // Update total discounts
                        $total_discounts         = Tools::ps_round($total_discounts - $product['price_wt'],
                            (int) $context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        $total_discounts_tax_exc = Tools::ps_round($total_discounts_tax_exc - $product['price'],
                            (int) $context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);

                        // Update cart rule value
                        $cart_rule['value_real']    = Tools::ps_round($cart_rule['value_real'] - $product['price_wt'],
                            (int) $context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        $cart_rule['value_tax_exc'] = Tools::ps_round($cart_rule['value_tax_exc'] - $product['price'],
                            (int) $context->currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);

                        // Update product quantity
                        $product['total_wt'] = Tools::ps_round($product['total_wt'] - $product['price_wt'],
                            (int) $currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        $product['total']    = Tools::ps_round($product['total'] - $product['price'],
                            (int) $currency->decimals * _PS_PRICE_COMPUTE_PRECISION_);
                        $product['cart_quantity']--;

                        if (!$product['cart_quantity']) {
                            unset($products[$key]);
                        }

                        // Add a new product line
                        $gift_product                  = $product;
                        $gift_product['cart_quantity'] = 1;
                        $gift_product['price']         = 0;
                        $gift_product['price_wt']      = 0;
                        $gift_product['total_wt']      = 0;
                        $gift_product['total']         = 0;
                        $gift_product['gift']          = true;
                        $gift_products[]               = $gift_product;

                        break; // One gift product per cart rule
                    }
                }
            }
        }

        foreach ($cart_rules as $key => &$cart_rule) {
            if ((float) $cart_rule['value_real'] == 0 && (int) $cart_rule['free_shipping'] == 0) {
                unset($cart_rules[$key]);
            }
        }

        return [
            'delivery'                  => $delivery,
            'delivery_state'            => State::getNameById($delivery->id_state),
            'invoice'                   => $invoice,
            'invoice_state'             => State::getNameById($invoice->id_state),
            'formattedAddresses'        => $formatted_addresses,
            'products'                  => array_values($products),
            'gift_products'             => $gift_products,
            'discounts'                 => array_values($cart_rules),
            'is_virtual_cart'           => (int) $this->isVirtualCart(),
            'total_discounts'           => $total_discounts,
            'total_discounts_tax_exc'   => $total_discounts_tax_exc,
            'total_wrapping'            => $this->getOrderTotal(true, Cart::ONLY_WRAPPING),
            'total_wrapping_tax_exc'    => $this->getOrderTotal(false, Cart::ONLY_WRAPPING),
            'total_shipping'            => $total_shipping,
            'total_shipping_tax_exc'    => $total_shipping_tax_exc,
            'total_products_wt'         => $total_products_wt,
            'total_products'            => $total_products,
            'total_price'               => $base_total_tax_inc,
            'total_tax'                 => $total_tax,
            'total_price_without_tax'   => $base_total_tax_exc,
            'is_multi_address_delivery' => $this->isMultiAddressDelivery()
                                           || ((int) Tools::getValue('multi-shipping') == 1),
            'free_ship'                 => $total_shipping ? 0 : 1,
            'carrier'                   => new Carrier($this->id_carrier, $id_lang),
        ];
    }
}
