<?php

namespace AlineVentosa\LeftQty\Block;

use Magento\Catalog\Model\ProductFactory;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;


/**
 * Class LeftQty
 * @package AlineVentosa\LeftQty\Block
 */
class LeftQty extends \Magento\Framework\View\Element\Template
{

    /**
     * @var GetProductSalableQtyInterface
     */
    protected $salebleqty;

    /**
     * @var StockResolverInterface
     */
    protected $stockresolver;

    /**
     * @var StoreManagerInterface
     */
    protected $storemanager;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var ProductFactory
     */
    protected $product;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var GetStockBySalesChannelInterface
     */
    protected $stockBySalesChannel;

    /**
     * @var SalesChannelInterface
     */
    protected $salesChannelInterface;
    /**
     /**
     * @var StockRepositoryInterface;
     */
    protected $stockRepositoryInterface;

    /**
    * @var GetStockItemDataInterface;
     */
     protected $stockItemDataInterface;

     /*
     * LeftQty constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\App\Request\Http $request
     * @param ProductFactory $product
     * @param StoreManagerInterface $storemanager
     * @param GetProductSalableQtyInterface $salebleqty
     * @param StockResolverInterface
     * @param \Magento\Framework\Registry $registry
     * @param GetStockBySalesChannelInterface $stockBySalesChannel
     * @param SalesChannelInterface $salesChannelInterface
     * @param StockReporsitoryInterface $stockRepositoryIn
     * @param GetStockItemDataInterface $stockItemDataInterface;
     * @param array $data
     */

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Registry $registry,
        ProductFactory $product,
        StoreManagerInterface $storemanager,
        GetProductSalableQtyInterface $salebleqty,
        StockResolverInterface $stockresolver,
        GetStockBySalesChannelInterface $stockBySalesChannel,
        SalesChannelInterface $salesChannelInterface,
        StockRepositoryInterface $stockRepositoryInterface,
        GetStockItemDataInterface $stockItemDataInterface,
        array $data = [])
    {
        $this->request = $request;
        $this->product = $product;
        $this->registry =$registry;
        $this->storemanager = $storemanager;
        $this->salebleqty = $salebleqty;
        $this->stockresolver = $stockresolver;
        $this->stockBySalesChannel = $stockBySalesChannel;
        $this->salesChannelInterface = $salesChannelInterface;
        $this->stockRepositoryInterface = $stockRepositoryInterface;
        $this->stockItemDataInterface = $stockItemDataInterface;
        parent::__construct($context, $data);
    }

    public function getCurrentProduct()
    {
        /* @var \Magento\Framework\Registry */
        return $this->registry->registry('current_product');
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function saleble()
    {
        $productId = $this->getCurrentProduct()->getId();
        //$productId = $this->request->getParam('id');
        $websiteCode = $this->storemanager->getWebsite()->getCode();
        $stockDetails = $this->stockresolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
        $stockId = $stockDetails->getStockId();
        $productDetails = $this->product->create()->load($productId);
        $sku = $productDetails->getSku();
        $proType = $productDetails->getTypeId();
        $this->salesChannelInterface->setType(SalesChannelInterface::TYPE_WEBSITE);
        $this->salesChannelInterface->setCode($websiteCode);
        $stock_rpsi = $this->stockRepositoryInterface->getList();

        foreach($stock_rpsi->getItems() as $stk){
            $a = $stk;
        }

        $result = array();
        if ($proType == 'configurable') {
            $product = $this->getCurrentProduct();
            $productTypeInstance = $product->getTypeInstance();
            $usedProducts = $productTypeInstance->getUsedProducts($product);
            foreach($usedProducts as $p){
                $p_id = $p->getId();
                $p_details = $this->product->create()->load($p_id);
                $p_sku = $p_details->getSku();
                $stockData = array();
                foreach($stock_rpsi->getItems() as $stk) {
                    $stockId = $stk->getStockId();
                    $stockName = $stk->getName();
                    $stockItemData = $this->stockItemDataInterface->execute($p_sku, $stockId);
                    if (is_null($stockItemData)){
                        continue;
                    }
                    $stock = round($stockItemData["quantity"]);
                    $stockData[] = array($stockName, $stock);
                }
                $result[$p_id] = $stockData;
            }
            return $result;
        }

        if ($proType != 'configurable' && $proType != 'bundle' && $proType != 'grouped') {
            $stockQty = $this->salebleqty->execute($sku, $stockId);
            return $stockQty;
	} else {
            return '';
        }
    }
}
