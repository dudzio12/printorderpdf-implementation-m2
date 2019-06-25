<?php

namespace Fooman\PrintOrderPdf\UnitTest\Model\Pdf;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Test for
 * @see Fooman\PrintOrderPdf\Model\Pdf\Order
 */
class OrderTest extends \Fooman\PhpunitBridge\BaseUnitTestCase
{
    /**
     * @var \Fooman\PrintOrderPdf\Model\Pdf\Order
     */
    protected $object;

    public function setUp()
    {
        $objectManager = new ObjectManager($this);

        $pdfConfigMock = $this->getPdfConfigMock();
        $directoryMock = $this->getDirectoryMock();
        $filesystemMock = $this->getFileSystemMock($directoryMock);

        $storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->expects($this->any())->method('getBaseUrl')->will($this->returnValue('/'));
        $storeManagerMock = $this->getMockForAbstractClass(\Magento\Store\Model\StoreManagerInterface::class);
        $storeManagerMock->expects($this->any())->method('getStore')->will($this->returnValue($storeMock));

        $scopeConfigMock = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $localeDataMock = $this->createMock(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class);
        $inlineTranslationMock = $this->createMock(\Magento\Framework\Translate\Inline\StateInterface::class);
        $localeResolverMock = $this->createMock(\Magento\Framework\Locale\ResolverInterface::class);

        $paymentDataMock = $this->getPaymentDataMock();

        $pdfTotalFactoryMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Pdf\Total\Factory::class,
            ['create']
        );

        $pdfTotalFactoryMock->expects($this->any())->method('create')->will(
            $this->returnValue($objectManager->getObject(\Magento\Sales\Model\Order\Pdf\Total\DefaultTotal::class))
        );

        $pdfItemsFactoryMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Pdf\ItemsFactory::class,
            ['get']
        );
        $pdfItemsFactoryMock->expects($this->any())->method('get')->will(
            $this->returnValue($objectManager->getObject(\Magento\Sales\Model\Order\Pdf\Items\Invoice\DefaultInvoice::class))
        );

        $orderConstructorArgs = [
            'paymentData'       => $paymentDataMock,
            'string'            => $objectManager->getObject(\Magento\Framework\Stdlib\StringUtils::class),
            'scopeConfig'       => $scopeConfigMock,
            'filesystem'        => $filesystemMock,
            'pdfConfig'         => $pdfConfigMock,
            'pdfTotalFactory'   => $pdfTotalFactoryMock,
            'pdfItemsFactory'   => $pdfItemsFactoryMock,
            'localeDate'        => $localeDataMock,
            'inlineTranslation' => $inlineTranslationMock,
            'storeManager'      => $storeManagerMock,
            'localeResolver'    => $localeResolverMock,
            []
        ];

        $this->object = $objectManager->getObject(\Fooman\PrintOrderPdf\Model\Pdf\Order::class, $orderConstructorArgs);
    }

    public function testGetPdf()
    {
        $orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            [
                'getBillingAddress',
                'getShippingAddress',
                'getStore',
                'getPayment',
                'getOrderCurrency',
                'getAllItems',
                'getStoreId'
            ]
        );

        $orderMock->expects($this->any())->method('getStoreId')->will(
            $this->returnValue(\Magento\Store\Model\Store::DISTRO_STORE_ID)
        );

        $orderItemMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Item::class,
            ['getProductType', 'getSku', 'getName']
        );
        $orderItemMock->expects($this->any())->method('getProductType')->will(
            $this->returnValue(
                'default'
            )
        );
        $orderItemMock->expects($this->any())->method('getSku')->will(
            $this->returnValue('Item SKU')
        );
        $orderItemMock->expects($this->any())->method('getName')->will(
            $this->returnValue('Item Name')
        );

        $orderParentItemMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Item::class,
            ['getParentItem', 'getSku', 'getName']
        );
        $orderParentItemMock->expects($this->any())->method('getParentItem')->will(
            $this->returnValue(true)
        );
        $orderParentItemMock->expects($this->any())->method('getSku')->will(
            $this->returnValue('Parent Item SKU')
        );
        $orderParentItemMock->expects($this->any())->method('getName')->will(
            $this->returnValue('Parent Item Name')
        );

        $orderMock->expects($this->any())->method('getAllItems')->will(
            $this->returnValue([$orderParentItemMock, $orderItemMock])
        );

        $addressMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Address::class,
            ['format']
        );
        $addressMock->expects($this->any())->method('format')->will(
            $this->returnValue(
                'Street Line 1 with a very long Street name and number 1234567890|Street Line 2|City|Country'
            )
        );
        $orderMock->expects($this->any())->method('getBillingAddress')->will($this->returnValue($addressMock));
        $orderMock->expects($this->any())->method('getShippingAddress')->will($this->returnValue($addressMock));

        $paymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $currencyMock = $this->createMock(\Magento\Directory\Model\Currency::class);

        $orderMock->expects($this->any())->method('getPayment')->will($this->returnValue($paymentMock));
        $orderMock->expects($this->any())->method('getOrderCurrency')->will($this->returnValue($currencyMock));

        $pdf = $this->object->getPdf([$orderMock]);
        $this->assertInstanceOf('Zend_Pdf', $pdf);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPdfConfigMock()
    {
        $pdfConfigMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Pdf\Config::class,
            ['getRenderersPerProduct', 'getTotals']
        );
        $pdfConfigMock->expects($this->any())->method('getRenderersPerProduct')->will(
            $this->returnValue(['default' => '>\Magento\Sales\Model\Order\Pdf\Items\Invoice\DefaultInvoice'])
        );

        $pdfConfigMock->expects($this->any())->method('getTotals')->will(
            $this->returnValue(['grand_total' => ['source_field' => 'grand_total']])
        );
        return $pdfConfigMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDirectoryMock()
    {
        $directoryMock = $this->createPartialMock(
            \Magento\Framework\Filesystem\Directory\Write::class,
            ['getAbsolutePath']
        );
        $directoryMock->expects($this->any())->method('getAbsolutePath')->will(
            $this->returnCallback(
                function ($argument) {
                    if (strpos($argument, 'lib/internal/LinLibertineFont/') === 0
                        || strpos($argument, 'lib/internal/GnuFreeFont/') === 0) {
                        $argument = str_replace('lib/internal/', '', $argument);
                        return __DIR__ . '/_files/' . $argument;
                    }
                    return dirname(__DIR__, 8) . '/' . $argument;
                }
            )
        );
        return $directoryMock;
    }

    /**
     * @param $directoryMock
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFileSystemMock($directoryMock)
    {
        $filesystemMock = $this->createPartialMock(
            \Magento\Framework\Filesystem::class,
            ['getDirectoryRead','getDirectoryWrite']
        );
        $filesystemMock->expects($this->any())->method('getDirectoryRead')->will($this->returnValue($directoryMock));
        $filesystemMock->expects($this->any())->method('getDirectoryWrite')->will($this->returnValue($directoryMock));
        return $filesystemMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPaymentDataMock()
    {
        $paymentDataMock = $this->createPartialMock(
            \Magento\Payment\Helper\Data::class,
            ['getInfoBlock']
        );

        $blockMock = $this->createPartialMock(
            \Magento\Framework\View\Element\Template::class,
            ['toPdf']
        );
        $blockMock->expects($this->any())->method('toPdf')->will($this->returnValue('PAYMENT INFO'));

        $paymentDataMock->expects($this->any())->method('getInfoBlock')->will($this->returnValue($blockMock));
        return $paymentDataMock;
    }
}
