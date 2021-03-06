<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thelia\Coupon\Type;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Collection\ObjectCollection;
use Thelia\Condition\ConditionCollection;
use Thelia\Condition\ConditionEvaluator;
use Thelia\Condition\Implementation\MatchForTotalAmount;
use Thelia\Condition\Operators;
use Thelia\Coupon\FacadeInterface;
use Thelia\Model\CartItem;
use Thelia\Model\CountryQuery;
use Thelia\Model\CurrencyQuery;
use Thelia\Model\Product;
use Thelia\Model\ProductQuery;

/**
 * @author Franck Allimant <franck@cqfdev.fr>
 */
class FreeProductTest extends TestCase
{
    /** @var Product */
    public $freeProduct;
    public $originalPrice;
    public $originalPromo;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $currency = CurrencyQuery::create()->filterByCode('EUR')->findOne();

        // Find a product
        $this->freeProduct = ProductQuery::create()
            ->joinProductSaleElements('pse_join')
            ->addJoinCondition('pse_join', 'is_default = ?', 1, null, \PDO::PARAM_INT)
            ->findOne()
        ;

        if (null === $this->freeProduct) {
            $this->markTestSkipped("You can't run this test as there's no product with associated product_sale_elements");
        }

        $this->originalPrice = $this->freeProduct->getDefaultSaleElements()->getPricesByCurrency($currency)->getPrice();
        $this->originalPromo = $this->freeProduct->getDefaultSaleElements()->getPromo();

        $this->freeProduct->getDefaultSaleElements()->setPromo(false)->save();
    }

    /**
     * Generate adapter stub.
     *
     * @param int    $cartTotalPrice   Cart total price
     * @param string $checkoutCurrency Checkout currency
     * @param string $i18nOutput       Output from each translation
     *
     * @return MockObject
     */
    public function generateFacadeStub($cartTotalPrice = 400, $checkoutCurrency = 'EUR', $i18nOutput = '')
    {
        $stubFacade = $this->getMockBuilder('\Thelia\Coupon\BaseFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $currencies = CurrencyQuery::create();
        $currencies = $currencies->find();
        $stubFacade->expects($this->any())
            ->method('getAvailableCurrencies')
            ->willReturn($currencies);

        $stubFacade->expects($this->any())
            ->method('getCartTotalPrice')
            ->willReturn($cartTotalPrice);

        $stubFacade->expects($this->any())
            ->method('getCheckoutCurrency')
            ->willReturn($checkoutCurrency);

        $stubFacade->expects($this->any())
            ->method('getConditionEvaluator')
            ->willReturn(new ConditionEvaluator());

        $stubTranslator = $this->getMockBuilder('\Thelia\Core\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $stubTranslator->expects($this->any())
            ->method('trans')
            ->willReturn($i18nOutput);

        $stubFacade->expects($this->any())
            ->method('getTranslator')
            ->willReturn($stubTranslator);

        $stubDispatcher = $this->getMockBuilder('\Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $stubDispatcher->expects($this->any())
            ->method('dispatch')
            ->willReturnCallback(function ($cartEvent, $dummy): void {
                $ci = new CartItem();
                $ci
                    ->setId(3)
                    ->setPrice(123)
                    ->setPromo(0)
                    ->setProductId($this->freeProduct->getId())
                ;

                $cartEvent->setCartItem($ci);
            });

        $stubFacade->expects($this->any())
            ->method('getDispatcher')
            ->willReturn($stubDispatcher);

        $stubSession = $this->getMockBuilder('\Thelia\Core\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $stubSession->expects($this->any())
            ->method('get')
            ->will($this->onConsecutiveCalls(-1, 3));

        $stubRequest = $this->getMockBuilder('\Thelia\Core\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $stubRequest->expects($this->any())
            ->method('getSession')
            ->willReturn($stubSession);

        $stubFacade->expects($this->any())
            ->method('getRequest')
            ->willReturn($stubRequest);

        $country = CountryQuery::create()
            ->findOneByByDefault(1);

        $stubFacade->expects($this->any())
            ->method('getDeliveryCountry')
            ->willReturn($country);

        return $stubFacade;
    }

    public function generateMatchingCart(MockObject $stubFacade, $count)
    {
        $product1 = ProductQuery::create()->addAscendingOrderByColumn('RAND()')->findOne();

        $product2 = ProductQuery::create()->filterById($product1->getId(), Criteria::NOT_IN)->addAscendingOrderByColumn('RAND()')->findOne();

        $cartItem1Stub = $this->getMockBuilder('\Thelia\Model\CartItem')
            ->disableOriginalConstructor()
            ->getMock();

        $cartItem1Stub
            ->expects($this->any())
            ->method('getProduct')
            ->willReturn($product1)
        ;
        $cartItem1Stub
            ->expects($this->any())
            ->method('getQuantity')
            ->willReturn(1)
        ;
        $cartItem1Stub
            ->expects($this->any())
            ->method('getPrice')
            ->willReturn(100)
        ;

        $cartItem2Stub = $this->getMockBuilder('\Thelia\Model\CartItem')
            ->disableOriginalConstructor()
            ->getMock();

        $cartItem2Stub
            ->expects($this->any())
            ->method('getProduct')
            ->willReturn($product2);

        $cartItem2Stub
            ->expects($this->any())
            ->method('getQuantity')
            ->willReturn(2)
        ;
        $cartItem2Stub
            ->expects($this->any())
            ->method('getPrice')
            ->willReturn(150)

        ;

        $cartStub = $this->getMockBuilder('\Thelia\Model\Cart')
            ->disableOriginalConstructor()
            ->getMock();

        if ($count == 1) {
            $ret = [$cartItem1Stub];
        } else {
            $ret = [$cartItem1Stub, $cartItem2Stub];
        }

        $cartStub
            ->expects($this->any())
            ->method('getCartItems')
            ->willReturn($ret);

        $stubFacade->expects($this->any())
            ->method('getCart')
            ->willReturn($cartStub);

        return [$product1->getId(), $product2->getId()];
    }

    public function generateNoMatchingCart(MockObject $stubFacade): void
    {
        $product2 = new Product();
        $product2->setId(30);

        $cartItem2Stub = $this->getMockBuilder('\Thelia\Model\CartItem')
            ->disableOriginalConstructor()
            ->getMock();

        $cartItem2Stub->expects($this->any())
            ->method('getProduct')
            ->willReturn($product2)
        ;
        $cartItem2Stub->expects($this->any())
            ->method('getQuantity')
            ->willReturn(2)
        ;
        $cartItem2Stub
            ->expects($this->any())
            ->method('getPrice')
            ->willReturn(11000)
        ;

        $cartStub = $this->getMockBuilder('\Thelia\Model\Cart')
            ->disableOriginalConstructor()
            ->getMock();

        $cartStub
            ->expects($this->any())
            ->method('getCartItems')
            ->willReturn([$cartItem2Stub]);

        $stubFacade->expects($this->any())
            ->method('getCart')
            ->willReturn($cartStub);
    }

    public function testSet(): void
    {
        $stubFacade = $this->generateFacadeStub();

        $coupon = new FreeProduct($stubFacade);

        $date = new \DateTime();

        $coupon->set(
            $stubFacade,
            'TEST',
            'TEST Coupon',
            'This is a test coupon title',
            'This is a test coupon description',
            ['percentage' => 10.00, 'products' => [10, 20], 'offered_product_id' => $this->freeProduct->getId(), 'offered_category_id' => 1],
            true,
            true,
            true,
            true,
            254,
            $date->setTimestamp(strtotime('today + 3 months')),
            new ObjectCollection(),
            new ObjectCollection(),
            false
        );

        $condition1 = new MatchForTotalAmount($stubFacade);
        $operators = [
            MatchForTotalAmount::CART_TOTAL => Operators::SUPERIOR,
            MatchForTotalAmount::CART_CURRENCY => Operators::EQUAL,
        ];
        $values = [
            MatchForTotalAmount::CART_TOTAL => 40.00,
            MatchForTotalAmount::CART_CURRENCY => 'EUR',
        ];
        $condition1->setValidatorsFromForm($operators, $values);

        $condition2 = new MatchForTotalAmount($stubFacade);
        $operators = [
            MatchForTotalAmount::CART_TOTAL => Operators::INFERIOR,
            MatchForTotalAmount::CART_CURRENCY => Operators::EQUAL,
        ];
        $values = [
            MatchForTotalAmount::CART_TOTAL => 400.00,
            MatchForTotalAmount::CART_CURRENCY => 'EUR',
        ];
        $condition2->setValidatorsFromForm($operators, $values);

        $conditions = new ConditionCollection();
        $conditions[] = $condition1;
        $conditions[] = $condition2;
        $coupon->setConditions($conditions);

        $this->assertEquals('TEST', $coupon->getCode());
        $this->assertEquals('TEST Coupon', $coupon->getTitle());
        $this->assertEquals('This is a test coupon title', $coupon->getShortDescription());
        $this->assertEquals('This is a test coupon description', $coupon->getDescription());

        $this->assertTrue($coupon->isCumulative());
        $this->assertTrue($coupon->isRemovingPostage());
        $this->assertTrue($coupon->isAvailableOnSpecialOffers());
        $this->assertTrue($coupon->isEnabled());

        $this->assertEquals(254, $coupon->getMaxUsage());
        $this->assertEquals($date, $coupon->getExpirationDate());
    }

    public function testMatchOne(): void
    {
        $this->markTestSkipped('Coupon test disbaled');

        $stubFacade = $this->generateFacadeStub();

        $coupon = new FreeProduct($stubFacade);

        $date = new \DateTime();

        $coupon->set(
            $stubFacade,
            'TEST',
            'TEST Coupon',
            'This is a test coupon title',
            'This is a test coupon description',
            ['percentage' => 10.00, 'products' => [10, 20], 'offered_product_id' => $this->freeProduct->getId(), 'offered_category_id' => 1],
            true,
            true,
            true,
            true,
            254,
            $date->setTimestamp(strtotime('today + 3 months')),
            new ObjectCollection(),
            new ObjectCollection(),
            false
        );

        $products = $this->generateMatchingCart($stubFacade, 1);

        $coupon->product_list = $products;

        $this->assertEquals(123.00, $coupon->exec());
    }

    public function testMatchSeveral(): void
    {
        $this->markTestSkipped('Coupon test disbaled');

        $stubFacade = $this->generateFacadeStub();

        $coupon = new FreeProduct($stubFacade);

        $date = new \DateTime();

        $coupon->set(
            $stubFacade,
            'TEST',
            'TEST Coupon',
            'This is a test coupon title',
            'This is a test coupon description',
            ['percentage' => 10.00, 'products' => [10, 20], 'offered_product_id' => $this->freeProduct->getId(), 'offered_category_id' => 1],
            true,
            true,
            true,
            true,
            254,
            $date->setTimestamp(strtotime('today + 3 months')),
            new ObjectCollection(),
            new ObjectCollection(),
            false
        );

        $products = $this->generateMatchingCart($stubFacade, 2);

        $coupon->product_list = $products;

        $this->assertEquals(123.00, $coupon->exec());
    }

    public function testNoMatch(): void
    {
        $stubFacade = $this->generateFacadeStub();

        $coupon = new FreeProduct($stubFacade);

        $date = new \DateTime();

        $coupon->set(
            $stubFacade,
            'TEST',
            'TEST Coupon',
            'This is a test coupon title',
            'This is a test coupon description',
            ['percentage' => 10.00, 'products' => [10, 20], 'offered_product_id' => $this->freeProduct->getId(), 'offered_category_id' => 1],
            true,
            true,
            true,
            true,
            254,
            $date->setTimestamp(strtotime('today + 3 months')),
            new ObjectCollection(),
            new ObjectCollection(),
            false
        );

        $this->generateNoMatchingCart($stubFacade);

        $this->assertEquals(0.00, $coupon->exec());
    }

    public function testGetName(): void
    {
        $stubFacade = $this->generateFacadeStub(399, 'EUR', 'Coupon test name');

        /** @var FacadeInterface $stubFacade */
        $coupon = new FreeProduct($stubFacade);

        $actual = $coupon->getName();
        $expected = 'Coupon test name';
        $this->assertEquals($expected, $actual);
    }

    public function testGetToolTip(): void
    {
        $tooltip = 'Coupon test tooltip';
        $stubFacade = $this->generateFacadeStub(399, 'EUR', $tooltip);

        /** @var FacadeInterface $stubFacade */
        $coupon = new FreeProduct($stubFacade);

        $actual = $coupon->getToolTip();
        $expected = $tooltip;
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        if (null !== $this->freeProduct) {
            $this->freeProduct->getDefaultSaleElements()->setPromo($this->originalPromo)->save();
        }
    }
}
