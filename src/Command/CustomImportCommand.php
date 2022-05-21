<?php

declare(strict_types=1);

namespace App\Command;

use App\Document\ObjectValue\Action;
use App\Document\ObjectValue\Date;
use App\Document\ObjectValue\Exchange;
use App\Document\ObjectValue\Money;
use App\Document\ObjectValue\Order;
use App\Service\TradeService;
use League\Csv\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CustomImportCommand extends Command
{
    protected static $defaultName = 'crypto:import:trades:custom';

    private $tradeService;

    public function __construct(TradeService $tradeService)
    {
        $this->tradeService = $tradeService;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'CSV file path')
            ->setDescription('Automatically update wallets')
            ->setHelp('Automatically update wallets');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->isValidFile($input->getArgument('file'))) {
            return 1;
        }

        $this->importCSVTrades($input->getArgument('file'));

        return 0;
    }

    private function isValidFile(string $file): bool
    {
        return file_exists($file);
    }

    private function importCSVTrades(string $file): void
    {
        $file = $this->readFile($file);
        $file = $this->setReaderOptions($file);
        if (!$this->areValidHeaders($file)) {
            throw new \Exception('CSV headers not valid');
        }

        $this->importRecords($file);
    }

    private function readFile(string $file): Reader
    {
        return Reader::createFromPath($file, 'r');
    }

    private function setReaderOptions(Reader $file): Reader
    {
        $file->setHeaderOffset(0);
        $file->setDelimiter(',');

        return $file;
    }

    private function areValidHeaders(Reader $file): bool
    {
        $headers = [
            'OrderId',
            'Date',
            'Action',
            'Symbol',
            'Amount',
            'Price',
            'Fees',
            'To',
            'Subtotal',
            'Total',
            'Exchange',
        ];

        return $headers === $file->getHeader();
    }

    private function importRecords(Reader $file): void
    {
        foreach ($file->getRecords() as $record) {
            $this->createTrade($record);
        }
    }

    private function createTrade(array $record): void
    {
        $symbol = $record['Symbol'];

        $this->tradeService->create(
            $this->createOrder($symbol, $record['Amount']),
            $this->createExchange($record),
            $this->createDate($record),
            $this->createAction($record),
            $this->createMoney($record, $this->calculateOrderPrice($record)),
            $this->createMoney($record, (float) $record['Fees'])
        );
    }

    private function createOrder(string $symbol, $amount): Order
    {
        return Order::create($symbol, (float) $amount);
    }

    private function createExchange(array $record): Exchange
    {
        return Exchange::create($record['OrderId'], $record['Exchange'], $record['OrderId']);
    }

    private function createDate(array $record): Date
    {
        return Date::createFromString($record['Date']);
    }

    private function createAction(array $record): Action
    {
        return Action::createFromName($record['Action']);
    }

    private function createMoney(array $record, float $value): Money
    {
        if ('EUR' === $record['To']) {
            return Money::EUR($value);
        }
        if ('USD' === $record['To']) {
            return Money::USD($value);
        }

        return Money::USDT($value);
    }

    private function calculateOrderPrice(array $record): float
    {
        return (float) $record['Price'];
    }
}
