<?php

declare(strict_types=1);

namespace App\Command;

use App\Document\ObjectValue\Action;
use App\Document\ObjectValue\Date;
use App\Document\ObjectValue\Exchange;
use App\Document\ObjectValue\Money;
use App\Document\ObjectValue\Order;
use App\Event\TradeEvents;
use App\Service\TradeService;
use App\Utils\BitvavoExchangeUtils;
use League\Csv\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class BitvavoImportCommand extends Command
{
    protected static $defaultName = 'crypto:import:trades:bitvavo';

    private $tradeService;
    private $eventDispatcher;

    public function __construct(
        TradeService $tradeService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->tradeService = $tradeService;
        $this->eventDispatcher = $eventDispatcher;

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

        $event = new Event();
        $this->eventDispatcher->dispatch($event, TradeEvents::TRADE_UPDATE_EVENT);

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
            'timestamp',
            'type',
            'currency',
            'amount',
            'status',
            'address',
            'method',
            'txid',
        ];

        return $headers === $file->getHeader();
    }

    private function importRecords(Reader $file): void
    {
        foreach ($file->getRecords() as $record) {
            if ('staking' === $record['type']) {
                $this->createTrade($record);
            }
        }
    }

    private function createTrade(array $record): void
    {
        $symbol = $record['currency'];
        $this->tradeService->create(
            $this->createOrder($symbol, $record['amount']),
            $this->createExchange($record),
            $this->createDate($record),
            $this->createAction($record),
            $this->createMoney(0),
            $this->createFeeMoney($record)
        );
    }

    private function createOrder(string $symbol, $amount): Order
    {
        return Order::create($symbol, (float) $amount);
    }

    private function createExchange(array $record): Exchange
    {
        return Exchange::create(md5($record['timestamp']), BitvavoExchangeUtils::getDefaultExchangeName(), md5($record['timestamp']));
    }

    private function createDate(array $record): Date
    {
        $date = explode(' ', $record['timestamp']);

        $stringDate = $date[0].'/'.$date[1].'/'.$date[3].' '.$date[4];

        $dateTime = \DateTime::createFromFormat('D/M/Y H:i:s', $stringDate);

        return Date::createFromDateTime($dateTime);
    }

    private function createAction(array $record): Action
    {
        return Action::createFromName('STAKING');
    }

    private function createMoney(float $value): Money
    {
        return Money::EUR($value);
    }

    private function createFeeMoney(array $record): Money
    {
        return $this->createMoney(0);
    }
}
