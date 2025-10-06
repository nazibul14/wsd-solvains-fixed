<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Domain\DomainExceptions\MongodbException;
use Application\Module;
use Application\Services\Instrument\InstrumentListService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

/**
 * @method \Laminas\Http\PhpEnvironment\Response getResponse()
 * @method \Laminas\Http\PhpEnvironment\Request getRequest()
 */
class PortfolioController extends AbstractActionController
{

    /**
     * @var InstrumentListService
     */
    protected $instrumentListService;


    public function __construct(
      InstrumentListService $instrumentListService
    ) {
        $this->instrumentListService = $instrumentListService;
    }


    public function portfolioAction(): JsonModel
    {
        $return = Module::newAppJsonModel()->setStatusError();
        try {
            $portfollio = $this->instrumentListService->allInstrumentsPortfolio(
              (int) $this->params()->fromQuery('showProfits', 0),
              (int) $this->params()->fromQuery('structureId', -1)
            );

        } catch (\Throwable $exception) {
            $this->getResponse()->setStatusCode(503);
            return $return
              ->setMessage('server error occurred')
              ->setThrowableFromSolvians($exception);
        }
        $this->getResponse()->setStatusCode(200);
        return $return->setStateStatusOk()
          ->setVariable('count', count($portfollio))
          ->setData($portfollio);
    }

}