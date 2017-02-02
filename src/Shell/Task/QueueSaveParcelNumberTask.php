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

/**
 * Class QueueImportProductTo21OrderTask
 * @package App\Shell\Task
 */
class QueueSaveParcelNumberTask extends QueueTask
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

    public function setParcelNumber($ParcelNumber)
    {
        $this->parcelNumber = $ParcelNumber;
        return $this;
    }


    public function add()
    {
        $data = [
            'order_id' => $this->orderId,
            'delivery_id' => $this->deliveryId,
            'parcel_number' => $this->parcelNumber
        ];
        return (bool)$this->QueuedJobs->createJob('SaveParcelNumber', $data);
    }

    public function run(array $data, $id)
    {
        try {
            $connection = ConnectionManager::get('default');
            $results = $connection->execute('SET session wait_timeout=2000');


            $this->glsComponent = new GlsComponent(new ComponentRegistry());

            $this->deliveryId = $data['delivery_id'];
            $this->orderId = $data['order_id'];


            $result = $this->glsComponent
                ->init($data)
                ->saveParcelInStore();

            return $result;


        } catch (\Exception $e) {
            $this->log($e->getMessage());
            return false;
        }
        return false;
    }

}