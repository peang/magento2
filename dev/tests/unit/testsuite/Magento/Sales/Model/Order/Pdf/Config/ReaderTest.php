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
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2013 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Magento\Sales\Model\Order\Pdf\Config;

class ReaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Sales\Model\Order\Pdf\Config\Reader
     */
    protected $_model;

    /**
     * @var \Magento\Config\FileResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_fileResolverMock;

    /**
     * @var \Magento\Sales\Model\Order\Pdf\Config\Converter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_converter;

    /**
     * @var \Magento\Sales\Model\Order\Pdf\Config\SchemaLocator
     */
    protected $_schemaLocator;

    /**
     * @var \Magento\Config\ValidationStateInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_validationState;

    protected function setUp()
    {
        $this->_fileResolverMock = $this->getMock('Magento\Config\FileResolverInterface');
        $this->_fileResolverMock
            ->expects($this->once())
            ->method('get')
            ->with('pdf.xml', 'scope')
            ->will($this->returnValue(array(
                __DIR__ . '/_files/pdf_one.xml',
                __DIR__ . '/_files/pdf_two.xml',
            )));

        $this->_converter = $this->getMock('Magento\Sales\Model\Order\Pdf\Config\Converter', array('convert'));

        $moduleReader = $this->getMock(
            'Magento\Core\Model\Config\Modules\Reader', array('getModuleDir'), array(), '', false
        );

        $moduleReader->expects($this->once())
            ->method('getModuleDir')->with('etc', 'Magento_Sales')
            ->will($this->returnValue('stub'));

        $this->_schemaLocator = new \Magento\Sales\Model\Order\Pdf\Config\SchemaLocator($moduleReader);
        $this->_validationState = $this->getMock('Magento\Config\ValidationStateInterface');
        $this->_validationState->expects($this->once())->method('isValidated')->will($this->returnValue(false));

        $this->_model = new \Magento\Sales\Model\Order\Pdf\Config\Reader(
            $this->_fileResolverMock,
            $this->_converter,
            $this->_schemaLocator,
            $this->_validationState,
            'pdf.xml'
        );
    }

    public function testRead()
    {
        $expectedResult = new \stdClass();
        $constraint = function (\DOMDOcument $actual) {
            try {
                $expected = __DIR__ . '/_files/pdf_merged.xml';
                \PHPUnit_Framework_Assert::assertXmlStringEqualsXmlFile($expected, $actual->saveXML());
                return true;
            } catch (\PHPUnit_Framework_AssertionFailedError $e) {
                return false;
            }
        };

        $this->_converter
            ->expects($this->once())
            ->method('convert')
            ->with($this->callback($constraint))
            ->will($this->returnValue($expectedResult))
        ;

        $this->assertSame($expectedResult, $this->_model->read('scope'));
    }
}
