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
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;

/**
 * Class QueueImportProductTo21OrderTask
 * @package App\Shell\Task
 */
class QueuegetParcelCodesTask extends QueueTask
{

    private $orderId;
    private $deliveryId;
    private $delivery;
    private $connection;

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
            $this->glsComponent = new GlsComponent(new ComponentRegistry());

            $this->deliveryId = $data['delivery_id'];
            $this->orderId = $data['order_id'];

            $this->out($data['order_id']);

            $result = $this->glsComponent
                ->init($data)
                ->connect()
                ->checkParcelIsSetForOrder()
                ->saveParcelInStore();


            $this->glsComponent->disconect();


            if ($result['err'] == 0) {
                $this->out($result['pn']);
                $this->out($result['mess']);
                return true;
            } else {
                $this->out($result['mess']);
                return false;
            }


        } catch (\Exception $e) {
            $this->log($e->getMessage());
            return false;
        }


        return true;
    }


    /**
     * Zapisuje numer przewozowy w Modelu
     */
    public function setTrackParcelNumberInDeliveryData($pn)
    {
        $this->Deliveries = TableRegistry::get('Deliveries');


        $this->delivery = $this->Deliveries->find()
            ->where([
                'Deliveries.id' => $this->deliveryId,
                'Deliveries.order_id' => $this->orderId])
            ->contain('Stores')
            ->first();

        $this->Deliveries->updateAll(
            ['parcel_number' => $pn],
            ['Deliveries.order_id' => $this->delivery->order_id, 'Deliveries.code' => $this->delivery->code]);

    }

}