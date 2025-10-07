<?php

declare(strict_types=1);

namespace Application\Model\InstrumentModel;

use Application\Model\InstrumentModel\Exceptions\InvalidInstrumentValueException;
use Application\Util\DatetimeUtil;

class InstrumentPortfolioObject implements \JsonSerializable
{

    public const isin = 'isin';

    public const quantity = 'quantity';

    public const structure = 'structure';

    public const buyPrice = 'buyPrice';

    public const currentSellPrice = 'currentSellPrice';

    public const currentBuyPrice = 'currentBuyPrice';

    public const currency = 'currency';

    public const profit = 'profit';

    public const profitPercentage = 'profitPercentage';

    private const DATE_PROPERTIES = [];

    protected $properties = [];


    public function __construct(
      string $isin
    ) {
        $this->setIsin($isin);
    }


    /**
     * @param
     *
     * @return static
     * @throws InvalidInstrumentValueException
     */
    public static function fromDataFile(array $fileContent): self
    {
        if (!is_string($fileContent['isin'] ?? null)) {
            throw new InvalidInstrumentValueException('key "isin" is not part of the document');
        }
        try {
            $return = new self($fileContent['isin']);
            if (isset($fileContent['properties'])) {
                $return->setqQuantity($fileContent['properties']['quantity']
                  ?? null);
                $return->setstructure($fileContent['properties']['structure']
                  ?? null);
            }
        } catch (\TypeError $exception) {
            throw new InvalidInstrumentValueException(
              sprintf('invalid value type received in method: [%s]',
                __METHOD__),
              $exception->getCode(),
              $exception
            );
        }
        return $return;
    }

    public function setPropertiesFromFile(array $fileContent): self
    {
        try {
            $this->setCurrency($fileContent['currency'] ?? "");
            $this->setBuyPrice($fileContent['buyPrice'] ?? "");
            $this->setCurrentSellPrice($fileContent['currentSellPrice'] ??
              -1);
            $this->setCurrentBuyPrice($fileContent['currentBuyPrice'] ?? -1);
        } catch (\TypeError $exception) {
            var_dump($exception->getMessage());
            exit;
        }
        return $this;
    }

    public function calculateProfit(): void
    {
        $profit = $this->getCurrentSellPrice()
          - (float)$this->getBuyPrice();
        $profit = round($profit, 2);
        $this->setProfit($profit);

        $profit_percent = $profit
          * 100 / (float)$this->getBuyPrice();
        $profit_percent = round($profit_percent, 2);
        $this->setProfitPercentage($profit_percent . "%");
    }


    public function jsonSerialize()
    {
        return
          array_map(
            static function (?\DateTime $datetime): ?string {
                return $datetime ? $datetime->format('c') : null;
            },
            array_intersect_key($this->properties,
              array_flip(self::DATE_PROPERTIES))
          )
          +
          $this->properties;
    }


    /**
     * @param string $property
     * @param        $value
     *
     * @return $this
     * @codeCoverageIgnore
     */
    protected function setProp(string $property, $value): self
    {
        $this->properties[$property] = $value;
        return $this;
    }


    protected function getProp(string $property)
    {
        return $this->properties[$property] ?? null;
    }

    public function setIsin(string $isin): self
    {
        return $this->setProp(self::isin, $isin);
    }

    public function getIsin(): string
    {
        return $this->getProp(self::isin);
    }


    public function setStructure(?float $structure): self
    {
        return $this->setProp(self::structure, $structure);
    }


    public function getStructure(): ?int
    {
        return $this->getProp(self::structure);
    }

    public function setBuyPrice(?string $buyPrice): self
    {
        return $this->setProp(self::buyPrice, $buyPrice);
    }

    public function getBuyPrice(): ?string
    {
        return $this->getProp(self::buyPrice);
    }

    public function setCurrentSellPrice(?float $currentSellPrice): self
    {
        return $this->setProp(self::currentSellPrice, $currentSellPrice);
    }

    public function getCurrentSellPrice(): ?float
    {
        return (float)$this->getProp(self::currentSellPrice);
    }

    public function setProfit($profit): self
    {
        return $this->setProp(self::profit, $profit);
    }

    public function getProfit(): ?float
    {
        return $this->getProp(self::profit);
    }

    public function setProfitPercentage(string $profitPercentage): self
    {
        return $this->setProp(self::profitPercentage, $profitPercentage);
    }

    public function getProfitPercentage(): ?string
    {
        return $this->getProp(self::profitPercentage);
    }


    public function setCurrentBuyPrice(?float $currentBuyPrice): self
    {
        return $this->setProp(self::currentBuyPrice, $currentBuyPrice);
    }


    public function getCurrentBuyPrice(): ?float
    {
        return $this->getProp(self::currentBuyPrice);
    }

    public function setCurrency(?string $currency): self
    {
        return $this->setProp(self::currency, $currency);
    }


    public function getCurrency(): ?string
    {
        return $this->getProp(self::currency);
    }

    public function setqQuantity(?int $quantity): self
    {
        return $this->setProp(self::quantity, $quantity);
    }


    public function getQuantity(): ?int
    {
        return $this->getProp(self::quantity);
    }


}