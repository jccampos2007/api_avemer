<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Models\Oferta;
use Psr\Http\Message\ResponseInterface as ResponseMessage;
use Psr\Http\Message\ServerRequestInterface as Request;

class OfferController
{
    public function getAvailable(Request $request, ResponseMessage $response): ResponseMessage
    {
        $oferta = new Oferta();
        $offers = $oferta->getAbiertos();

        return Response::json($response, $offers);
    }
}
