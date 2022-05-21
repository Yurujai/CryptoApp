<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ExchangeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExchangesController extends AbstractController
{
    protected $exchangeService;

    public function __construct(ExchangeService $exchangeService)
    {
        $this->exchangeService = $exchangeService;
    }

    /**
     * @Route("/exchanges", name="crypto_exchanges")
     */
    public function exchanges(): Response
    {
        return $this->render('/exchanges/template.html.twig', [
            'exchangeList' => $this->exchangeService->getListOfExchanges(),
        ]);
    }
}
