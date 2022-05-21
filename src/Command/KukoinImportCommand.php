<?php

declare(strict_types=1);

namespace App\Command;

use App\Document\ObjectValue\Action;
use App\Document\ObjectValue\Date;
use App\Document\ObjectValue\Exchange;
use App\Document\ObjectValue\Money;
use App\Document\ObjectValue\Order;
use App\Service\TradeService;
use App\Utils\KukoinExchangeUtils;
use League\Csv\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class KukoinImportCommand extends Command
{
    protected static $defaultName = 'crypto:import:trades:kukoin';

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
            'tradeCreatedAt',
            'orderId',
            'symbol',
            'side',
            'price',
            'size',
            'funds',
            'fee',
            'liquidity',
            'feeCurrency',
            'orderType',
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
        $symbol = $this->removeMarketPair($record['symbol']);
        $this->tradeService->create(
            $this->createOrder($symbol, $record['size']),
            $this->createExchange($record),
            $this->createDate($record),
            $this->createAction($record),
            $this->createMoney($this->calculateOrderPrice($record, $symbol)),
            $this->createFeeMoney($record)
        );
    }

    private function createOrder(string $symbol, $amount): Order
    {
        return Order::create($symbol, (float) $this->removeMarketSymbol($amount, $symbol));
    }

    private function createExchange(array $record): Exchange
    {
        return Exchange::create($record['orderId'], KukoinExchangeUtils::getDefaultExchangeName(), $record['orderId']);
    }

    private function createDate(array $record): Date
    {
        return Date::createFromString($record['tradeCreatedAt']);
    }

    private function createAction(array $record): Action
    {
        return Action::createFromName($record['side']);
    }

    private function createMoney(float $value): Money
    {
        return Money::USDT($value);
    }

    private function createFeeMoney(array $record): Money
    {
        if ('USDT' !== $record['feeCurrency']) {
            throw new \Exception('Fee currency arent USDT');
        }

        $value = (float) $record['fee'];

        return $this->createMoney($value);
    }

    private function removeMarketPair(string $element): string
    {
        return str_replace('-USDT', '', $element);
    }

    private function removeMarketSymbol(string $element, string $symbol)
    {
        return str_ireplace($symbol, '', $element);
    }

    private function calculateOrderPrice(array $record, string $symbol): float
    {
        return (float) ((float) $record['funds'] + (float) $record['fee']) / $record['size'];
    }
}
