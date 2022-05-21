<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\SymbolService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatsController extends AbstractController
{
    protected $symbolService;

    public function __construct(SymbolService $symbolService)
    {
        $this->symbolService = $symbolService;
    }

    /**
     * @Route("/crypto/list", name="crypto_symbol_list")
     */
    public function symbolList(): Response
    {
        $cryptoList = $this->symbolService->getListOfSymbols();

        return $this->render('/crypto/list.html.twig', [
            'cryptoList' => $cryptoList,
        ]);
    }
}
