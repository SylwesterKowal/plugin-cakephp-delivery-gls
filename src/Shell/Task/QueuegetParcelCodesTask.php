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

/**
 * Class QueueImportProductTo21OrderTask
 * @package App\Shell\Task
 */
class QueuegetParcelCodesTask extends QueueTask
{

    private $orderId;
    private $deliveryId;
    private $delivery;

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
        $this->glsComponent = new GlsComponent(new ComponentRegistry());
        try {
            $this->delivery = $this->glsComponent->init($data)->getDeliveryData($data);
            if ($this->delivery /*&& $importProduct->ststus != 1*/) { // istnieje oraz nie był jeszcze importowany

                $this->out($data['order_id']);

                $result = $this->glsComponent
                    ->init($data)
                    ->connect()
                    ->checkParcelIsSetForOrder()
                    ->saveParcelInStore();

                $this->glsComponent->disconect();

                if (isset($result['pn']) && !empty($result['pn'])) {
                    $this->setTrackParcelNumberInDeliveryData($result['pn']);
                }

                if ($result['err'] == 0) {
                    $this->out($result['pn']);
                    $this->out($result['mess']);
//                    $this->setStatus($data, ['status' => 1, 'errors' => '']);
                    return true;
                } else {
                    $this->out($result['mess']);
//                    $this->hr();
//                    $this->setStatus($data, ['status' => 9, 'errors' => $err]);
                    return false;
                }


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
//        $this->Deliveries = TableRegistry::get('Deliveries');
        $this->Deliveries->updateAll(
            ['parcel_number' => $pn],
            ['Deliveries.order_id' => $this->delivery->order_id, 'Deliveries.code' => $this->delivery->code]);

    }


    private function setStatus($data, $errors)
    {
        $importProduct = $this->getImportProduct($data);
        $importProduct = $this->ImportProducts->patchEntity($importProduct, $errors);
        if ($this->ImportProducts->save($importProduct)) {
            return true;
        } else {
            return false;
        }
    }

    private function setModels()
    {
        $this->loadModel('Deliveries');
    }

}