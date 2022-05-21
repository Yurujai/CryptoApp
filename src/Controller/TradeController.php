<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\TradeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TradeController extends AbstractController
{
    private $tradeService;
    private $eventDispatcher;

    public function __construct(
        TradeService $tradeService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->tradeService = $tradeService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Route("/", name="crypto_home")
     */
    public function all(): Response
    {
        return $this->render('/trade/all.html.twig', []);
    }

    /**
     * @Route("/trades/{symbol}", name="crypto_trades_list_by_symbol")
     */
    public function list(Request $request, string $symbol): Response
    {
        $criteria = $this->getRequestYearCriteria($request);
        $criteria['order.symbol'] = $symbol;

        $elements = $this->tradeService->fromCriteria($criteria);

        return $this->render('/trade/list.html.twig', [
            'symbol' => $symbol,
            'elements' => $elements,
            'holdings' => $this->tradeService->holdingsFromCriteria($criteria),
            'numberOfTrades' => count($elements),
            'fees' => $this->tradeService->feesFromCriteria($criteria),
            'profit' => $this->tradeService->calculateProfitFirstInFirstOut($symbol),
        ]);
    }

    /**
     * @Route("/profitAll/{year}", name="crypto_profit_all")
     */
    public function profitAll(int $year): Response
    {
        dump($this->tradeService->calculateProfitFirstInFirstOutAll($year));
        exit;
    }

    /**
     * @Route("/remove/trades/{exchange}", name="crypto_trades_remove_by_exchange")
     */
    public function removeTradesFromExchange(string $exchange): RedirectResponse
    {
        $this->tradeService->removeFromCriteria(['exchange.name' => $exchange]);

        return $this->redirectToRoute('crypto_exchanges');
    }

    private function getRequestYearCriteria(Request $request): array
    {
        $criteria = [];
        if ($request->get('year')) {
            $criteria['date.year'] = $request->query->getInt('year');
        }

        return $criteria;
    }
}
