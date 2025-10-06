<?php

declare(strict_types=1);

namespace Application\Services\Instrument;

use Application\Domain\DomainExceptions\MongodbException;
use Application\Model\InstrumentModel\Exceptions\InvalidInstrumentValueException;
use Application\Model\InstrumentModel\InstrumentObject;
use Application\Util\DatetimeUtil;

class InstrumentListService
{

    /**
     * @var InstrumentsPersistence
     */
    protected $persistence;


    public function __construct(
      InstrumentsPersistence $persistence
    ) {
        $this->persistence = $persistence;
    }


    /**
     * @param int        $limit
     * @param int        $skip
     * @param \DateTime  $expiredBefore
     * @param float|null $bidIsAtLeast
     *
     * @return InstrumentObject[]
     * @throws MongodbException
     */
    public function expiredInstrumentsBefore(
      int $limit,
      int $skip,
      \DateTime $expiredBefore,
      ?float $bidIsAtLeast
    ): array {
        $filter = [];
        $filter[InstrumentObject::expiry]
          = ['$lt' => DatetimeUtil::toMongodbUtcDateTime($expiredBefore)];
        if ($bidIsAtLeast !== null) {
            $filter[InstrumentObject::bid] = ['$gte' => $bidIsAtLeast];
        }
        $pipeline = [];
        $pipeline[] = ['$match' => $filter];
        $pipeline[] = ['$limit' => $limit];
        $pipeline[] = ['$skip' => $skip];

        $documentsCursor = $this->persistence->aggregate($pipeline);
        $instruments = [];
        foreach ($documentsCursor as $mongoDocument) {
            try {
                $instruments[]
                  = InstrumentObject::fromMongoDocument($mongoDocument);
            } catch (InvalidInstrumentValueException $exception) {
            }
        }
        return $instruments;
    }

    public function allInstrumentsPortfolio(
      int $showProfits,
      int $structureId
    ): array {
        try {
            $rootPath = realpath(__DIR__ . '/../../../../../');
            $dataFile = $rootPath . '/data/source/instruments-data.json';
            $propertiesFile = $rootPath
              . '/data/source/instruments-properties.json';
            //        echo $dataFile; exit;

            if (!file_exists($dataFile) || !file_exists($propertiesFile)) {
                return [
                  'error' => 'One or both data files are missing.',
                ];
            }

            $dataContent = json_decode(file_get_contents($dataFile), true);
            $propertiesContent = json_decode(file_get_contents($propertiesFile),
              true);

            $merged_json = [];

            foreach ($dataContent['data'] as $entry) {
                foreach ($entry['instruments'] as $instrument) {
                    $isin = $instrument['isin'];
                    $structure = $instrument['properties']['structure'] ??
                      -1;
                    if ($structureId < 0 || $structureId == $structure) {
                        $merged_json[$isin] = ['isin'=> $isin];
                        $merged_json[$isin] = array_merge($merged_json[$isin],
                          $instrument['properties']);
                    }
                }
            }

            foreach ($propertiesContent['instruments'] as $instrumentEntry) {
                foreach ($instrumentEntry as $isin => $details) {
                    if ($showProfits == 1
                      && !empty($details['currentSellPrice'])
                      && !empty($details['currentBuyPrice'])
                    ) {
                        $details["profit"] = (int)$details['currentSellPrice']
                          - (int)$details['currentBuyPrice'];

                        $profit_percent = (int)$details["profit"]
                          * 100 / (int)$details['currentBuyPrice'];
                        $profit_percent = round($profit_percent, 2);
                        $details["profitPercentage"] = $profit_percent . "%";
                    }

                    if (isset($merged_json[$isin])) {
                        $merged_json[$isin] = array_merge($merged_json[$isin],
                          $details);
                    }
                }
            }

            return $merged_json;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

}