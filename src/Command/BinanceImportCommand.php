<?php

declare(strict_types=1);

namespace App\Command;

use App\Document\ObjectValue\Action;
use App\Document\ObjectValue\Date;
use App\Document\ObjectValue\Exchange;
use App\Document\ObjectValue\Money;
use App\Document\ObjectValue\Order;
use App\Service\BinanceExchangeService;
use App\Service\TradeService;
use App\Utils\BinanceExchangeUtils;
use App\Utils\CryptoUtils;
use League\Csv\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BinanceImportCommand extends Command
{
    protected static $defaultName = 'crypto:import:trades:binance';

    private $tradeService;
    private $binanceExchangeService;

    public function __construct(
        TradeService $tradeService,
        BinanceExchangeService $binanceExchangeService
    ) {
        $this->tradeService = $tradeService;
        $this->binanceExchangeService = $binanceExchangeService;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'CSV file path')
            ->setDescription('Automatically update wallets')
            ->setHelp('Automatically update wallets')
        ;
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
            'Date(UTC)',
            'OrderNo',
            'Pair',
            'Type',
            'Side',
            'Order Price',
            'Order Amount',
            'Time',
            'Executed',
            'Average Price',
            'Trading total',
            'Status',
        ];

        return $headers === $file->getHeader();
    }

    private function importRecords(Reader $file): void
    {
        foreach ($file->getRecords() as $record) {
            if ('FILLED' !== $record['Status']) {
                continue;
            }

            $this->createTrade($record);
        }
    }

    private function createTrade(array $record): void
    {
        $symbol = $this->removeMarketPair($record['Pair']);
        $this->tradeService->create(
            $this->createOrder($symbol, $record['Executed']),
            $this->createExchange($record),
            $this->createDate($record),
            $this->createAction($record),
            $this->createMoney($this->calculateOrderPrice($record, $symbol)),
            $this->createMoney(0)
        );
    }

    private function createOrder(string $symbol, $amount): Order
    {
        return Order::create($symbol, (float) $this->removeMarketSymbol($amount, $symbol));
    }

    private function createExchange(array $record): Exchange
    {
        return Exchange::create($record['OrderNo'], BinanceExchangeUtils::getDefaultExchangeName(), $record['OrderNo']);
    }

    private function createDate(array $record): Date
    {
        return Date::createFromString($record['Date(UTC)']);
    }

    private function createAction(array $record): Action
    {
        return Action::createFromName($record['Side']);
    }

    private function createMoney(float $value): Money
    {
        return Money::USDT($value);
    }

    private function removeMarketPair(string $element)
    {
        foreach (CryptoUtils::getPairsOfCrypto() as $pair) {
            if (0 === stripos($element, $pair)) {
                continue;
            }
            $element = str_ireplace($pair, '', $element);
        }

        return $element;
    }

    private function removeMarketSymbol(string $element, string $symbol)
    {
        return str_ireplace([$symbol, ','], '', $element);
    }

    private function calculateOrderPrice(array $record, string $symbol): float
    {
        $amount = (float) $this->removeMarketSymbol($record['Executed'], $symbol);

        if (false !== stripos($record['Trading total'], 'BNB')) {
            $data = $this->binanceExchangeService->getPriceOfPairAtTime('BNBUSDT', (string) (strtotime($record['Date(UTC)']) * 1000));
            $element = reset($data);
            $BNBPrice = (float) $this->removeMarketSymbol($record['Trading total'], $symbol) * (float) $element['close'];

            $autoCreateType = ('sell' === strtolower($record['Side'])) ? 'BUY' : 'SELL';
            $this->createAutoConvertTrade($record, $element['close'], $BNBPrice, $autoCreateType, 'BNB');

            return $BNBPrice / (float) $this->removeMarketSymbol($record['Executed'], $symbol);
        }

        if (false !== stripos($record['Trading total'], 'BTC')) {
            $data = $this->binanceExchangeService->getPriceOfPairAtTime('BTCUSDT', (string) (strtotime($record['Date(UTC)']) * 1000));
            $element = reset($data);
            $BTCPrice = (float) $this->removeMarketSymbol($record['Trading total'], $symbol) * (float) $element['close'];

            $autoCreateType = ('sell' === strtolower($record['Side'])) ? 'BUY' : 'SELL';
            $this->createAutoConvertTrade($record, $element['close'], $BTCPrice, $autoCreateType, 'BTC');

            return $BTCPrice / (float) $this->removeMarketSymbol($record['Executed'], $symbol);
        }

        if (false !== stripos($record['Trading total'], 'ETH')) {
            $data = $this->binanceExchangeService->getPriceOfPairAtTime('ETHUSDT', (string) (strtotime($record['Date(UTC)']) * 1000));
            $element = reset($data);
            $ETHPrice = (float) $this->removeMarketSymbol($record['Trading total'], $symbol) * (float) $element['close'];

            $autoCreateType = ('sell' === strtolower($record['Side'])) ? 'BUY' : 'SELL';
            $this->createAutoConvertTrade($record, $element['close'], $ETHPrice, $autoCreateType, 'ETH');

            return $ETHPrice / (float) $this->removeMarketSymbol($record['Executed'], $symbol);
        }

        $total = (float) $this->removeMarketPair($record['Trading total']);

        return $total / $amount;
    }

    private function createAutoConvertTrade(
        array $record,
        string $orderPrice,
        float $symbolPrice,
        string $type,
        string $symbol
    ): void {
        $element = [
            'Date(UTC)' => $record['Date(UTC)'],
            'OrderNo' => $record['OrderNo'].'-conversion',
            'Pair' => strtoupper($symbol.'USDT'),
            'Type' => $record['Type'],
            'Side' => strtoupper($type),
            'Order Price' => (float) $orderPrice,
            'Order Amount' => (string) $this->removeMarketSymbol($record['Trading total'], strtoupper($symbol)),
            'Time' => $record['Time'],
            'Executed' => (string) $this->removeMarketSymbol($record['Trading total'], strtoupper($symbol)),
            'Average Price' => (float) $orderPrice,
            'Trading total' => (string) $symbolPrice,
            'Status' => 'FILLED',
        ];
        $this->createTrade($element);
    }
}
