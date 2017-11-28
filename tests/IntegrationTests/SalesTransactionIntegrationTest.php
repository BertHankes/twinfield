<?php

namespace PhpTwinfield\IntegrationTests;

use PhpTwinfield\ApiConnectors\TransactionApiConnector;
use PhpTwinfield\BaseTransaction;
use PhpTwinfield\BaseTransactionLine;
use PhpTwinfield\DomDocuments\TransactionsDocument;
use PhpTwinfield\Mappers\TransactionMapper;
use PhpTwinfield\Response\Response;
use PhpTwinfield\SalesTransaction;
use PhpTwinfield\SalesTransactionLine;
use PhpTwinfield\Secure\Login;
use PhpTwinfield\Secure\Service;

/**
 * @covers SalesTransaction
 * @covers SalesTransactionLine
 * @covers TransactionsDocument
 * @covers TransactionMapper
 * @covers TransactionApiConnector
 */
class SalesTransactionIntegrationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Login|\PHPUnit_Framework_MockObject_MockObject
     */
    private $login;

    /**
     * @var Service|\PHPUnit_Framework_MockObject_MockObject
     */
    private $service;

    /**
     * @var TransactionApiConnector|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transactionApiConnector;

    protected function setUp()
    {
        parent::setUp();

        $this->login   = $this->createMock(Login::class);
        $this->service = $this->createMock(Service::class);

        $this->login
            ->expects($this->any())
            ->method('process')
            ->willReturn(true);

        $this->transactionApiConnector = $this->createPartialMock(
            TransactionApiConnector::class,
            ['getLogin', 'createService']
        );

        $this->transactionApiConnector
            ->expects($this->any())
            ->method('createService')
            ->willReturn($this->service);

        $this->transactionApiConnector
            ->expects($this->any())
            ->method('getLogin')
            ->willReturn($this->login);
    }

    public function testGetSalesTransactionWorks()
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML(file_get_contents(realpath(__DIR__ . '/resources/salesTransactionGetResponse.xml')));
        $response = new Response($domDocument);

        $this->service
            ->expects($this->any())
            ->method('send')
            ->with($this->isInstanceOf(\PhpTwinfield\Request\Read\Transaction::class))
            ->willReturn($response);

        /** @var SalesTransaction[] $salesTransactions */
        $salesTransactions = $this->transactionApiConnector->get(SalesTransaction::class, 'SLS', '201300095', '001');
        $salesTransaction  = reset($salesTransactions);

        $this->assertInstanceOf(SalesTransaction::class, $salesTransaction);
        $this->assertSame(BaseTransaction::DESTINY_TEMPORARY, $salesTransaction->getDestiny());
        $this->assertNull($salesTransaction->getAutoBalanceVat());
        $this->assertSame('false', $salesTransaction->getRaiseWarning());
        $this->assertSame('001', $salesTransaction->getOffice());
        $this->assertSame('SLS', $salesTransaction->getCode());
        $this->assertSame(201300095, $salesTransaction->getNumber());
        $this->assertSame('2013/05', $salesTransaction->getPeriod());
        $this->assertSame('EUR', $salesTransaction->getCurrency());
        $this->assertSame('20130502', $salesTransaction->getDate());
        $this->assertSame('import', $salesTransaction->getOrigin());
        $this->assertNull($salesTransaction->getFreetext1());
        $this->assertNull($salesTransaction->getFreetext2());
        $this->assertNull($salesTransaction->getFreetext3());
        $this->assertSame('20130506', $salesTransaction->getDueDate());
        $this->assertSame('20130-6000', $salesTransaction->getInvoiceNumber());
        $this->assertSame('+++100/0160/01495+++', $salesTransaction->getPaymentReference());
        $this->assertSame('', $salesTransaction->getOriginReference());

        /** @var SalesTransactionLine[] $salesTransactionLines */
        $salesTransactionLines = $salesTransaction->getLines();
        $this->assertCount(3, $salesTransactionLines);

        $this->assertArrayHasKey('1', $salesTransactionLines);
        $totalLine = $salesTransactionLines['1'];
        $this->assertSame(BaseTransactionLine::TYPE_TOTAL, $totalLine->getType());
        $this->assertSame('1', $totalLine->getId());
        $this->assertSame('1300', $totalLine->getDim1());
        $this->assertSame('1000', $totalLine->getDim2());
        $this->assertSame(BaseTransactionLine::DEBIT, $totalLine->getDebitCredit());
        $this->assertSame(121.00, $totalLine->getValue());
        $this->assertSame(121.00, $totalLine->getBaseValue());
        $this->assertSame(1.0, $totalLine->getRate());
        $this->assertSame(156.53, $totalLine->getRepValue());
        $this->assertSame(1.293600000, $totalLine->getRepRate());
        $this->assertSame('', $totalLine->getDescription());
        $this->assertSame(BaseTransactionLine::MATCHSTATUS_AVAILABLE, $totalLine->getMatchStatus());
        $this->assertSame(2, $totalLine->getMatchLevel());
        $this->assertSame(121.00, $totalLine->getBaseValueOpen());
        $this->assertNull($totalLine->getVatCode());
        $this->assertNull($totalLine->getVatValue());
        $this->assertSame(21.00, $totalLine->getVatTotal());
        $this->assertSame(21.00, $totalLine->getVatBaseTotal());
        $this->assertSame(121.00, $totalLine->getValueOpen());
        $this->assertNull($totalLine->getPerformanceType());
        $this->assertNull($totalLine->getPerformanceCountry());
        $this->assertNull($totalLine->getPerformanceVatNumber());
        $this->assertNull($totalLine->getPerformanceDate());

        $this->assertArrayHasKey('2', $salesTransactionLines);
        $detailLine = $salesTransactionLines['2'];
        $this->assertSame(BaseTransactionLine::TYPE_DETAIL, $detailLine->getType());
        $this->assertSame('2', $detailLine->getId());
        $this->assertSame('8020', $detailLine->getDim1());
        $this->assertNull($detailLine->getDim2());
        $this->assertSame(BaseTransactionLine::CREDIT, $detailLine->getDebitCredit());
        $this->assertSame(100.00, $detailLine->getValue());
        $this->assertSame(100.00, $detailLine->getBaseValue());
        $this->assertSame(1.0, $detailLine->getRate());
        $this->assertSame(129.36, $detailLine->getRepValue());
        $this->assertSame(1.293600000, $detailLine->getRepRate());
        $this->assertSame('Outfit', $detailLine->getDescription());
        $this->assertSame(BaseTransactionLine::MATCHSTATUS_NOTMATCHABLE, $detailLine->getMatchStatus());
        $this->assertNull($detailLine->getMatchLevel());
        $this->assertNull($detailLine->getBaseValueOpen());
        $this->assertSame('VH', $detailLine->getVatCode());
        $this->assertSame(21.00, $detailLine->getVatValue());
        $this->assertNull($detailLine->getVatTotal());
        $this->assertNull($detailLine->getVatBaseTotal());
        $this->assertNull($detailLine->getValueOpen());
        $this->assertNull($detailLine->getPerformanceType());
        $this->assertNull($detailLine->getPerformanceCountry());
        $this->assertNull($detailLine->getPerformanceVatNumber());
        $this->assertNull($detailLine->getPerformanceDate());

        $this->assertArrayHasKey('3', $salesTransactionLines);
        $vatLine = $salesTransactionLines['3'];
        $this->assertSame(BaseTransactionLine::TYPE_VAT, $vatLine->getType());
        $this->assertSame('3', $vatLine->getId());
        $this->assertSame('1530', $vatLine->getDim1());
        $this->assertNull($vatLine->getDim2());
        $this->assertSame(BaseTransactionLine::CREDIT, $vatLine->getDebitCredit());
        $this->assertSame(21.00, $vatLine->getValue());
        $this->assertSame(21.00, $vatLine->getBaseValue());
        $this->assertSame(1.0, $vatLine->getRate());
        $this->assertSame(27.17, $vatLine->getRepValue());
        $this->assertSame(1.293600000, $vatLine->getRepRate());
        $this->assertNull($vatLine->getDescription());
        $this->assertNull($vatLine->getMatchStatus());
        $this->assertNull($vatLine->getMatchLevel());
        $this->assertNull($vatLine->getBaseValueOpen());
        $this->assertSame('VH', $vatLine->getVatCode());
        $this->assertNull($vatLine->getVatValue());
        $this->assertNull($vatLine->getVatTotal());
        $this->assertNull($vatLine->getVatBaseTotal());
        $this->assertNull($vatLine->getValueOpen());
        $this->assertNull($vatLine->getPerformanceType());
        $this->assertNull($vatLine->getPerformanceCountry());
        $this->assertNull($vatLine->getPerformanceVatNumber());
        $this->assertNull($vatLine->getPerformanceDate());
    }

    public function testSendSalesTransactionWorks()
    {
        $salesTransaction = new SalesTransaction();
        $salesTransaction
            ->setDestiny(SalesTransaction::DESTINY_TEMPORARY)
            ->setRaiseWarning('false')
            ->setCode('SLS')
            ->setCurrency('EUR')
            ->setDate('20130502')
            ->setPeriod('2013/05')
            ->setInvoiceNumber('20130-6000')
            ->setPaymentReference('+++100/0160/01495+++')
            ->setOffice('001')
            ->setDueDate('20130506');

        $totalLine = new SalesTransactionLine();
        $totalLine
            ->setType(BaseTransactionLine::TYPE_TOTAL)
            ->setId('1')
            ->setDim1('1300')
            ->setDim2('1000')
            ->setValue(121.00)
            ->setDebitCredit(BaseTransactionLine::DEBIT)
            ->setDescription('');

        $detailLine = new SalesTransactionLine();
        $detailLine
            ->setType(BaseTransactionLine::TYPE_DETAIL)
            ->setId('2')
            ->setDim1('8020')
            ->setValue(100.00)
            ->setDebitCredit(BaseTransactionLine::CREDIT)
            ->setDescription('Outfit')
            ->setVatCode('VH');

        $salesTransaction
            ->addLine($totalLine)
            ->addLine($detailLine);

        $this->service
            ->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(TransactionsDocument::class))
            ->willReturnCallback(function (TransactionsDocument $transactionsDocument) {
                $this->assertXmlStringEqualsXmlString(
                    file_get_contents(realpath(__DIR__ . '/resources/salesTransactionSendRequest.xml')),
                    $transactionsDocument->saveXML()
                );

                return new Response($transactionsDocument);
            });

        $this->transactionApiConnector->send([$salesTransaction]);
    }
}
