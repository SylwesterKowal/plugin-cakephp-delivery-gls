<?php
/**
 * Created by 21w.pl
 * User: Sylwester Kowal
 * Date: 2016-10-21
 * Time: 12:16
 */


namespace Gls\Shell\Task;

use Cake\Controller\ComponentRegistry;
use Exception;
use Gls\Controller\Component\GlsComponent;
use Import\Controller\Component\ImportServiceComponent;
use Queue\Shell\Task\QueueTask;
use Cake\Log\Log;
use Cake\Datasource\ConnectionManager;
use Gls\Shell\Task\QueueSaveParcelNumberTask;
use Cake\ORM\TableRegistry;

/**
 * Class QueueImportProductTo21OrderTask
 * @package App\Shell\Task
 */
class QueuegetParcelCodesTask extends QueueTask
{

    private $orderId;
    private $deliveryId;
    private $parcelNumber;
    private $delivery;
    protected $connection;

    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function setDeliveryId($deliveryId)
    {
        $this->deliveryId = $deliveryId;
        return $this;
    }


    public function add()
    {
        $data = [
            'order_id' => $this->orderId,
            'delivery_id' => $this->deliveryId,
        ];
        return (bool)$this->QueuedJobs->createJob('getParcelCodes', $data);
    }

    public function run(array $data, $id)
    {
        try {

            $connection = ConnectionManager::get('default');
            $results = $connection->execute('SET session wait_timeout=1000');

            $this->glsComponent = new GlsComponent(new ComponentRegistry());

            $this->deliveryId = $data['delivery_id'];
            $this->orderId = $data['order_id'];


            if ($this->parcelNumber = $this->glsComponent
                ->init($data)
                ->connect()
                ->checkParcelIsSetForOrder()
            ) {

                $this->setTrackParcelNumberInDeliveryData();

                $this->out('Get PN: ' . $this->parcelNumber . ' for order: ' . $this->orderId);

                $saveParcelNumberTask = new QueueSaveParcelNumberTask();
                $saveParcelNumberTask
                    ->setOrderId($this->orderId)
                    ->setDeliveryId($this->deliveryId)
                    ->setParcelNumber($this->parcelNumber)
                    ->add();

                $this->glsComponent->disconect();

            } else {
                $this->out('Not found PN: ' . $this->parcelNumber . ' for order: ' . $this->orderId);

                // zamykamy bierzące zadanie mimo iż nie znalazł się PN i zakładamy kolejne na koniec kolejki
                $this->setOrderId($this->orderId)
                    ->setDeliveryId($this->deliveryId)
                    ->add();

                $this->glsComponent->disconect();

            }
            return true;

        } catch (\Exception $e) {
            $this->log($e->getMessage());
            return false;
        }
        return true;
    }


    /**
     * Zapisuje numer przewozowy w Modelu
     */
    public function setTrackParcelNumberInDeliveryData()
    {
        $this->Deliveries = TableRegistry::get('Deliveries');


        $this->delivery = $this->Deliveries->find()
            ->where([
                'Deliveries.id' => $this->deliveryId,
                'Deliveries.order_id' => $this->orderId])
            ->contain('Stores')
            ->first();

        $this->Deliveries->updateAll(
            ['parcel_number' => $this->parcelNumber],
            ['Deliveries.order_id' => $this->delivery->order_id, 'Deliveries.code' => $this->delivery->code]);

    }

}