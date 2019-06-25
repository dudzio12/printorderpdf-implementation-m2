<?php
/**
 * @author     Kristof Ringleff
 * @package    Fooman_PrintOrderPdf
 * @copyright  Copyright (c) 2015 Fooman Limited (http://www.fooman.co.nz)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Fooman\PrintOrderPdf\Model;

use Fooman\PhpunitBridge\BaseUnitTestCase;

/**
 * @magentoAppArea adminhtml
 */
class OrderTest extends BaseUnitTestCase
{

    private $objectManager;

    private $pdf;

    public function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->pdf =  $this->objectManager->create(
            \Fooman\PrintOrderPdf\Model\Pdf\Order::class
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testGetPdf()
    {
        $order = $this->prepareOrder();
        $this->assertInstanceOf('Zend_Pdf', $this->pdf->getPdf([$order]));
    }

    /**
     * @magentoDataFixture Magento/Bundle/_files/order_item_with_bundle_and_options.php
     */
    public function testGetPdfWithBundleItem()
    {
        $order = $this->prepareOrder();
        $this->assertInstanceOf('Zend_Pdf', $this->pdf->getPdf([$order]));
    }

    /**
     * @return mixed
     */
    protected function prepareOrder()
    {
        $order = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\Sales\Model\Order::class
        )->loadByIncrementId('100000001');

        foreach ($order->getAllItems() as $orderItem) {
            if (!$orderItem->getSku()) {
                $orderItem->setSku('Test_sku');
            }
        }
        return $order;
    }
}
