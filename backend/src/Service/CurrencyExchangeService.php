<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

class CurrencyExchangeService
{
    private CONST REQUEST_URL = 'http://www.ecb.int/stats/eurofxref/eurofxref-daily.xml';
    private readonly array $ratesList;

    public function __construct()
    {
        $this->ratesList = $this->makeRatesList();
    }

    public function getCurrencyListFromEcb(): array
    {
        return array_keys($this->getRatesList());
    }

    public function getExchangeRate(string $fromCode, string $toCode)
    {
        $ratesList = $this->getRatesList();
        $fromRate = $ratesList[$fromCode]?: null;
        $toRate = $ratesList[$toCode] ?: null;

        if($fromRate && $toRate) {
            return round($toRate/$fromRate, 8);
        }

    }

    /**
     * @return array
     */
    private function getRatesList(): array
    {
        return array_merge(['EUR' => 1], $this->ratesList);
    }

    /**
     * @return array
     */
    private function makeRatesList(): array
    {
        //cache would be nice to avoid angering limited free api todo maybe latter
        $ratesList = [];
        try {
            $client    = HttpClient::create();
            $response  = $client->request('GET', self::REQUEST_URL);
            $content   = $response->getContent();

            $encoder       = new XmlEncoder();
            $xmlDecoded    = $encoder->decode($content, 'xml');
            $exchangeRates = $xmlDecoded['Cube']['Cube']['Cube'] ?? [];
            foreach ($exchangeRates as $item) {
                $ratesList[$item["@currency"]] = (float)$item["@rate"];
            }
        } catch (\Throwable $throwable) {
            //todo maybe latter empty good for now
            //$throwable->getMessage();
        }

        return $ratesList;
    }
}
