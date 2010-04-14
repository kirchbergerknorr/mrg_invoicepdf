<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category  Symmetrics
 * @package   Symmetrics_InvoicePdf
 * @author    symmetrics gmbh <info@symmetrics.de>
 * @author    Torsten Walluhn <tw@symmetrics.de>
 * @copyright 2010 symmetrics gmbh
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.symmetrics.de/
 */

/**
 * Abstract Pdf Rendering class
 *
 * @category  Symmetrics
 * @package   Symmetrics_InvoicePdf
 * @author    symmetrics gmbh <info@symmetrics.de>
 * @author    Torsten Walluhn <tw@symmetrics.de>
 * @copyright 2010 symmetrics gmbh
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.symmetrics.de/
 */
 
 abstract class Symmetrics_InvoicePdf_Model_Pdf_Abstract extends Varien_Object
 {
    /**
     * Zend PDF object
     *
     * @var Zend_Pdf
     */
    protected $_pdf;
    
    protected $_height;
    
    protected $_width;
    
    
    const XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID = 'sales_pdf/invoice/put_order_id';
    const XML_PATH_SALES_PDF_SHIPMENT_PUT_ORDER_ID = 'sales_pdf/shipment/put_order_id';
    const XML_PATH_SALES_PDF_CREDITMEMO_PUT_ORDER_ID = 'sales_pdf/creditmemo/put_order_id';
    
    const PAGE_POSITION_LEFT = 40;
    const PAGE_POSITION_RIGHT = 555;
    
    abstract public function getPdf();
    
    /**
     * Cunstructor to initialize the PDF object
     *
     */
    protected function _construct()
    {
        $this->_setPdf(new Zend_Pdf());
        $this->_height = 0;
        $this->_width = 0;
    }
    
    /**
     * Returns the total width in points of the string using the specified font and
     * size.
     *
     * This is not the most efficient way to perform this calculation. I'm
     * concentrating optimization efforts on the upcoming layout manager class.
     * Similar calculations exist inside the layout manager class, but widths are
     * generally calculated only after determining line fragments.
     *
     * @param string $string
     * @param Zend_Pdf_Resource_Font $font
     * @param float $fontSize Font size in points
     * @return float
     */
    public function widthForStringUsingFontSize($string, $font, $fontSize)
    {
        $drawingString = '"libiconv"' == ICONV_IMPL ? iconv('UTF-8', 'UTF-16BE//IGNORE', $string) : @iconv('UTF-8', 'UTF-16BE', $string);

        $characters = array();
        for ($i = 0; $i < strlen($drawingString); $i++) {
            $characters[] = (ord($drawingString[$i++]) << 8) | ord($drawingString[$i]);
        }
        $glyphs = $font->glyphNumbersForCharacters($characters);
        $widths = $font->widthsForGlyphs($glyphs);
        $stringWidth = (array_sum($widths) / $font->getUnitsPerEm()) * $fontSize;
        return $stringWidth;

    }

    /**
     * Before getPdf processing
     *
     * @return void
     */
    protected function _beforeGetPdf() {
        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);
    }

    /**
     * After getPdf processing
     *
     * @return void
     */
    protected function _afterGetPdf() {
        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(true);
    }
    
    /**
     * Set PDF object
     *
     * @param Zend_Pdf $pdf
     *
     * @return Mage_Sales_Model_Order_Pdf_Abstract
     */
    protected function _setPdf(Zend_Pdf $pdf)
    {
        $this->_pdf = $pdf;
        return $this;
    }
    
    /**
     * Retrieve PDF object
     *
     * @throws Mage_Core_Exception
     *
     * @return Zend_Pdf
     */
    protected function _getPdf()
    {
        if (!$this->_pdf instanceof Zend_Pdf) {
            Mage::throwException(Mage::helper('sales')->__('Please define PDF object before using'));
        }

        return $this->_pdf;
    }
    

    protected function _setFontRegular($object, $size = 10)
    {
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $object->setFont($font, $size);
        return $font;
    }

    protected function _setFontBold($object, $size = 10)
    {
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $object->setFont($font, $size);
        return $font;
    }

    protected function _setFontItalic($object, $size = 10)
    {
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_ITALIC);
        $object->setFont($font, $size);
        return $font;
    }
    
    /**
     * Create new page and assign to PDF object
     *
     * @param array $settings
     *
     * @return Zend_Pdf_Page
     */
    public function newPage(Varien_Object $settings)
    {
        $pageSize = ($settings->hasPageSize()) ? $settings->getPageSize() : Zend_Pdf_Page::SIZE_A4;
        $page = $this->_getPdf()->newPage($pageSize);
        $this->insertAddressFooter($page, $settings->getStore());
        $this->_getPdf()->pages[] = $page;
        // $this->y = 800;

        return $page;
    }
    
    /**
     * Insert the store logo to the Pdf
     *
     * @param &$page Zend_Pdf_Page Page to insert logo
     * @param $store integer       store Id to get logo
     *
     * @return Zend_Pdf_Page
     */
    protected function insertLogo(&$page, $store = null)
    {
        $image = Mage::getStoreConfig('sales/identity/logo', $store);
        if ($image) {
            $image = Mage::getStoreConfig('system/filesystem/media', $store) . '/sales/store/logo/' . $image;
            if (is_file($image)) {
                $image = Zend_Pdf_Image::imageWithPath($image);
                $page->drawImage($image, 25, 800, 125, 825);
            }
        }
        //return $page;
    }

    /**
     * Insert the store address to the Pdf
     *
     * @param &$page Zend_Pdf_Page Page to insert address
     * @param $store integer       store Id to get address
     *
     * @return Zend_Pdf_Page
     */
    protected function insertAddressFooter(&$page, $store = null)
    {
        $config = false;
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->_setFontRegular($page, 5);

        $footerLinePos = 47;
        
        $heightCount = 0;
        $lineSpacing = 6;
        $width = 20;
        
        $page->setLineWidth(0.4);
        $page->drawLine($width, $footerLinePos, $page->getWidth() - $width, $footerLinePos);
        
        $page->setLineWidth(0);
        
        /* if Symmetrics_Imprint - Modul is installed, get data from there */
        if (Mage::getConfig()->getNode('modules/Symmetrics_Imprint')) {
            $data = Mage::getStoreConfig('general/imprint', $store);
            $config = Mage::getModel('Mage_Core_Model_Config_System')->load('Symmetrics_Imprint');
        } else {
            $data = explode('\n', Mage::getStoreConfig('sales/identity/address', $store));
        }
        
        foreach ($data as $key => $value){
            if ($value == '') {
                continue;
            } else {
                $height = 40 - $lineSpacing * $heightCount;
                
                if ($config) {
                    /* get labels from fields in system.xml */
                    $element = $config->getNode('sections/general/groups/imprint/fields/' . $key);
                    $element = $element[0];
                    $elementData = $element->asArray();
                    if (isset($elementData['hide_in_invoice_pdf'])) {
                        /* don`t show this field */
                        continue;
                    } else {
                        /* TODO: translate */
                        $label = Mage::helper('imprint')->__($elementData['label']);
                        $value = $label . ': ' . $value;
                    }
                }
                $page->drawText(trim(strip_tags($value)), $width, $height, 'UTF-8');
                
                $heightCount++;                
                if ($heightCount == 4) {
                    $width += 100;
                    $heightCount = 0;
                }
            }
        }
        
        $this->_setFontRegular($page);
        
        return $page;
    }

    /**
     * Format address
     *
     * @param string $address
     * @return array
     */
    protected function _formatAddress($address)
    {
        $return = array();
        foreach (explode('|', $address) as $str) {
            foreach (Mage::helper('core/string')->str_split($str, 65, true, true) as $part) {
                if (empty($part)) {
                    continue;
                }
                $return[] = $part;
            }
        }
        return $return;
    }
    
    protected function _insertOrderInfoRow(&$page, $key, $value)
    {
        $width = $page->getWidth() - 40;
        $font = $this->_setFontRegular($page, 8);
        
        $page->drawText(
            $key, 
            $width - 135, 
            $this->_height, 
            'UTF-8'
        );
        
        if (is_array($value)) {
            foreach ($value as $valueRow) {
                $valueRow  = trim($valueRow);
                $page->drawText(
                    $valueRow, 
                    $width - 10 - $this->widthForStringUsingFontSize($valueRow, $font, 8),
                    $this->_height, 
                    'UTF-8'
                );
                $this->_height += 14;
            }
        } else { 
            $page->drawText(
                $value, 
                $width - 10 - $this->widthForStringUsingFontSize($value, $font, 8),
                $this->_height, 
                'UTF-8'
            );
            $this->_height += 14;
        }
        
    }
    
    protected function _insertOrderInfo(&$page, $order, $putOrderId)
    {
        $this->_height = 600;
        
        $this->_insertOrderInfoRow(
            $page,
            Mage::helper('sales')->__('Order Date: '),
            Mage::helper('core')->formatDate(
                $order->getCreatedAtStoreDate(),
                'medium',
                false
            )
        );

        if ($putOrderId) {
            $this->_insertOrderInfoRow(
                $page,
                Mage::helper('sales')->__('Order # '),
                $order->getRealOrderId()
            );
        }
        
        /* Payment */
        $paymentInfo = Mage::helper('payment')->getInfoBlock($order->getPayment())
            ->setIsSecureMode(true)
            ->toPdf();

        $payment = explode('{{pdf_row_separator}}', $paymentInfo);
        foreach ($payment as $key => $value){
            if (strip_tags(trim($value)) == ''){
                unset($payment[$key]);
            }
        }
        reset($payment);
        
        $this->_insertOrderInfoRow(
            $page,
            Mage::helper('sales')->__('Payment Method:'),
            $payment
        );
        
    }
    
    protected function _insertBillingAddress(&$page, $billingAddress)
    {
        $billingAddress = $this->_formatAddress($billingAddress->format('pdf'));
        $this->_height = 725;
        $this->_width = 40;
        $font = $this->_setFontRegular($page, 10);
        
        foreach ($billingAddress as $addressItem) {
            $page->drawText(
                $addressItem,
                $this->_width,
                $this->_height,
                'UTF-8'
            );
            
            $this->_height -= 14;
        }
    }
    
    protected function setSubject(&$page, $title)
    {
        $this->_setFontBold($page, 16);
        $page->drawText(
            $title,
            40,
            600,
            'UTF-8'
        );
        $this->_setFontRegular($page);
    }
    
    protected function insertOrder(&$page, $order, $putOrderId = true)
    {
        /* @var $order Mage_Sales_Model_Order */

        $this->_insertOrderInfo($page, $order, $putOrderId);

        /* Billing Address */
        $this->_insertBillingAddress($page, $order->getBillingAddress());

        
        /* Shipping Address and Method */
/*        if (!$order->getIsVirtual()) {
            /* Shipping Address */
/*            $shippingAddress = $this->_formatAddress($order->getShippingAddress()->format('pdf'));

            $shippingMethod  = $order->getShippingDescription();
        }

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->_setFontRegular($page);
        $page->drawText(Mage::helper('sales')->__('SOLD TO:'), 35, 740 , 'UTF-8');

        if (!$order->getIsVirtual()) {
            $page->drawText(Mage::helper('sales')->__('SHIP TO:'), 285, 740 , 'UTF-8');
        }
        else {
            $page->drawText(Mage::helper('sales')->__('Payment Method:'), 285, 740 , 'UTF-8');
        }

        if (!$order->getIsVirtual()) {
            $y = 730 - (max(count($billingAddress), count($shippingAddress)) * 10 + 5);
        }
        else {
            $y = 730 - (count($billingAddress) * 10 + 5);
        }

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
        $page->drawRectangle(25, 730, 570, $y);
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->_setFontRegular($page);
        $this->y = 720;

        foreach ($billingAddress as $value){
            if ($value!=='') {
                $page->drawText(strip_tags(ltrim($value)), 35, $this->y, 'UTF-8');
                $this->y -=10;
            }
        }

        if (!$order->getIsVirtual()) {
            $this->y = 720;
            foreach ($shippingAddress as $value){
                if ($value!=='') {
                    $page->drawText(strip_tags(ltrim($value)), 285, $this->y, 'UTF-8');
                    $this->y -=10;
                }

            }

            $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
            $page->setLineWidth(0.5);
            $page->drawRectangle(25, $this->y, 275, $this->y-25);
            $page->drawRectangle(275, $this->y, 570, $this->y-25);

            $this->y -=15;
            $this->_setFontBold($page);
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
            $page->drawText(Mage::helper('sales')->__('Payment Method'), 35, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('sales')->__('Shipping Method:'), 285, $this->y , 'UTF-8');

            $this->y -=10;
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));

            $this->_setFontRegular($page);
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));

            $paymentLeft = 35;
            $yPayments   = $this->y - 15;
        }
        else {
            $yPayments   = 720;
            $paymentLeft = 285;
        }

        foreach ($payment as $value){
            if (trim($value)!=='') {
                $page->drawText(strip_tags(trim($value)), $paymentLeft, $yPayments, 'UTF-8');
                $yPayments -=10;
            }
        }

        if (!$order->getIsVirtual()) {
            $this->y -=15;

            $page->drawText($shippingMethod, 285, $this->y, 'UTF-8');

            $yShipments = $this->y;


            $totalShippingChargesText = "(" . Mage::helper('sales')->__('Total Shipping Charges') . " " . $order->formatPriceTxt($order->getShippingAmount()) . ")";

            $page->drawText($totalShippingChargesText, 285, $yShipments-7, 'UTF-8');
            $yShipments -=10;
            $tracks = $order->getTracksCollection();
            if (count($tracks)) {
                $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
                $page->setLineWidth(0.5);
                $page->drawRectangle(285, $yShipments, 510, $yShipments - 10);
                $page->drawLine(380, $yShipments, 380, $yShipments - 10);
                //$page->drawLine(510, $yShipments, 510, $yShipments - 10);

                $this->_setFontRegular($page);
                $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
                //$page->drawText(Mage::helper('sales')->__('Carrier'), 290, $yShipments - 7 , 'UTF-8');
                $page->drawText(Mage::helper('sales')->__('Title'), 290, $yShipments - 7, 'UTF-8');
                $page->drawText(Mage::helper('sales')->__('Number'), 385, $yShipments - 7, 'UTF-8');

                $yShipments -=17;
                $this->_setFontRegular($page, 6);
                foreach ($order->getTracksCollection() as $track) {

                    $CarrierCode = $track->getCarrierCode();
                    if ($CarrierCode!='custom')
                    {
                        $carrier = Mage::getSingleton('shipping/config')->getCarrierInstance($CarrierCode);
                        $carrierTitle = $carrier->getConfigData('title');
                    }
                    else
                    {
                        $carrierTitle = Mage::helper('sales')->__('Custom Value');
                    }

                    //$truncatedCarrierTitle = substr($carrierTitle, 0, 35) . (strlen($carrierTitle) > 35 ? '...' : '');
                    $truncatedTitle = substr($track->getTitle(), 0, 45) . (strlen($track->getTitle()) > 45 ? '...' : '');
                    //$page->drawText($truncatedCarrierTitle, 285, $yShipments , 'UTF-8');
                    $page->drawText($truncatedTitle, 300, $yShipments , 'UTF-8');
                    $page->drawText($track->getNumber(), 395, $yShipments , 'UTF-8');
                    $yShipments -=7;
                }
            } else {
                $yShipments -= 7;
            }

            $currentY = min($yPayments, $yShipments);

            // replacement of Shipments-Payments rectangle block
            $page->drawLine(25, $this->y + 15, 25, $currentY);
            $page->drawLine(25, $currentY, 570, $currentY);
            $page->drawLine(570, $currentY, 570, $this->y + 15);

            $this->y = $currentY;
            $this->y -= 15;
        }
        */
    }

}