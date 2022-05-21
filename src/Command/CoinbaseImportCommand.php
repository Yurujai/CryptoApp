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

class CoinbaseImportCommand extends Command
{
    protected static $defaultName = 'crypto:import:trades:coinbase';

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
            'Timestamp',
            'Transaction Type',
            'Asset',
            'Quantity Transacted',
            'Spot Price Currency',
            'Spot Price at Transaction',
            'Subtotal',
            'Total (inclusive of fees)',
            'Fees',
            'Notes',
        ];

        return $headers === $file->getHeader();
    }

    private function importRecords(Reader $file): void
    {
        foreach ($file->getRecords() as $record) {
            if ('SEND' === strtoupper($record['Transaction Type'])) {
                continue;
            }

            if (in_array(strtoupper($record['Transaction Type']), ['BUY', 'SELL', 'REWARDS INCOME'])) {
                if ('REWARDS INCOME' === strtoupper($record['Transaction Type'])) {
                    $record['Transaction Type'] = 'STAKING';
                }
                $this->createTrade($record);
            } else {
                $this->createAutoConvertTrade($record);
            }
        }
    }

    private function createTrade(array $record): void
    {
        $symbol = $record['Asset'];

        $this->tradeService->create(
            $this->createOrder($symbol, $record['Quantity Transacted']),
            $this->createExchange($record),
            $this->createDate($record),
            $this->createAction($record),
            $this->createMoney($this->calculateOrderPrice($record)),
            $this->createMoney((float) $record['Fees'])
        );
    }

    private function createOrder(string $symbol, $amount): Order
    {
        return Order::create($symbol, (float) $amount);
    }

    private function createExchange(array $record): Exchange
    {
        $transactionId = md5($record['Timestamp']);

        return Exchange::create($transactionId, 'coinbase', $transactionId);
    }

    private function createDate(array $record): Date
    {
        return Date::createFromString($record['Timestamp']);
    }

    private function createAction(array $record): Action
    {
        return Action::createFromName($record['Transaction Type']);
    }

    private function createMoney(float $value): Money
    {
        return Money::EUR($value);
    }

    private function calculateOrderPrice(array $record): float
    {
        return (float) $record['Spot Price at Transaction'];
    }

    private function createAutoConvertTrade(array $record): void
    {
        $this->createSellConvertTrade($record);

        $this->createBuyConvertTrade($record);
    }

    private function createSellConvertTrade(array $record): void
    {
        $element = $record;
        $element['Transaction Type'] = 'SELL';

        $this->createTrade($element);
    }

    private function createBuyConvertTrade(array $record): void
    {
        $notes = $record['Notes'];
        $stringData = explode(' ', $notes);

        $element = [
            'Timestamp' => $record['Timestamp'],
            'Transaction Type' => 'BUY',
            'Asset' => $stringData[5],
            'Quantity Transacted' => $stringData[4],
            'Spot Price Currency' => 'EUR',
            'Spot Price at Transaction' => (float) ((float) $record['Subtotal'] / (float) $stringData[4]),
            'Subtotal' => $record['Subtotal'],
            'Total (inclusive of fees)' => $record['Subtotal'],
            'Fees' => 0,
            'Notes' => '',
        ];

        $this->createTrade($element);
    }
}
