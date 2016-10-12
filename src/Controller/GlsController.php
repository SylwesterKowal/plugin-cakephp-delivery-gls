<?php
namespace Gls\Controller;

use Gls\Controller\AppController;

/**
 * Gls Controller
 *
 * @property \Gls\Model\Table\GlsTable $Gls
 */
class GlsController extends GlsAppController
{
    const GLS_ID = 1;
    private $store;
    private $recivesData;
    private $delivery_id;

    /**
     * Index method
     *
     * @return \Cake\Network\Response|null
     */
    public function index()
    {
        $this->setModels();
        $this->recivesData = $this->request->session()->read('Recives.data');
        $this->saveConfig();

        $this->store = $this->Stores->find()
            ->where(['code' => $this->recivesData['CD'], 'curier_id' => self::GLS_ID])
            ->first();

        if ($this->store) {
            $this->set('setConfig', true);

            if ($this->submit()) {
                $this->saveDelivery();
            }

        } else {
            $this->set('setConfig', false);
        }
    }

    private function saveDelivery()
    {
        $entity = $this->Deliveries->newEntity();

        $entity = $this->Deliveries->patchEntity($entity, $this->prepareDeliveryData());
        if ($this->Deliveries->save($entity)) {
            $this->Flash->success(__('The Delivery has been saved.'));

            return $this->redirect(['action' => 'end']);
        } else {
            $this->Flash->error(__('The Delivery could not be saved. Please, try again.'));
        }
    }


    public function submit()
    {
        $hClient = new \SoapClient($this->store->api_url);
        try {

            $oCredit = new \stdClass();
            $oCredit->user_name = $this->store->api_username;
            $oCredit->user_password = $this->store->api_password;

            $oClient = $hClient->adeLogin($oCredit);
            $szSession = $oClient->return->session;

            $oCons = new \stdClass();
            $oCons->session = $szSession;
            $oCons->consign_prep_data = new \stdClass();
            $oCons->consign_prep_data->rname1 = substr($this->recivesData['NA'], 0, 60);
            $oCons->consign_prep_data->rname2 = substr($this->recivesData['NA'], 61, 120);
            $oCons->consign_prep_data->rname3 = substr($this->recivesData['NA'], 120, 180);

            $oCons->consign_prep_data->rcountry = $this->recivesData['CO'];
            $oCons->consign_prep_data->rzipcode = $this->recivesData['ZI'];
            $oCons->consign_prep_data->rcity = $this->recivesData['CI'];
            $oCons->consign_prep_data->rstreet = $this->recivesData['ST'];

            $oCons->consign_prep_data->rphone = $this->recivesData['TE'];
            $oCons->consign_prep_data->rcontact = $this->recivesData['EM'];

            $oCons->consign_prep_data->references = $this->recivesData['ID'];
            $oCons->consign_prep_data->notes = $this->recivesData['HO'];

            if ($this->store->cod_store_code == $this->recivesData['PE']) {
                $oCons->consign_prep_data->srv_bool = new \stdClass();
                $oCons->consign_prep_data->srv_bool->cod = 1;
                $oCons->consign_prep_data->srv_bool->cod_amount = $this->recivesData['VA'];
            }

            /*
            $oCons->consign_prep_data->sendaddr = new stdClass();
            $oCons->consign_prep_data->sendaddr->name1 = 'SendAddr Name1';
            $oCons->consign_prep_data->sendaddr->name2 = 'SendAddr Name2';
            $oCons->consign_prep_data->sendaddr->name3 = 'SendAddr Name3';
            $oCons->consign_prep_data->sendaddr->country = 'PL';
            $oCons->consign_prep_data->sendaddr->zipcode = '88-100';
            $oCons->consign_prep_data->sendaddr->city = 'Inowroclaw';
            $oCons->consign_prep_data->sendaddr->street= 'Batorego 12';
            */


            /*
            $oCons->consign_prep_data->srv_bool->daw = 1;
            $oCons->consign_prep_data->srv_daw = new stdClass();
            $oCons->consign_prep_data->srv_daw->name = 'DAW Name';
            $oCons->consign_prep_data->srv_daw->building = 'DAW Building';
            $oCons->consign_prep_data->srv_daw->floor = 'DAW Floor';
            $oCons->consign_prep_data->srv_daw->room = 'DAW Room';
            $oCons->consign_prep_data->srv_daw->phone = 'DAW Phone';
            $oCons->consign_prep_data->srv_daw->altrec = 'DAW AltRec';
            */

            /*
            $oCons->consign_prep_data->srv_bool->ident = 1;
            $oCons->consign_prep_data->srv_ident = new stdClass();
            $oCons->consign_prep_data->srv_ident->name = 'IDENT Name';
            $oCons->consign_prep_data->srv_ident->country = 'PL';
            $oCons->consign_prep_data->srv_ident->zipcode = '61-138';
            $oCons->consign_prep_data->srv_ident->city = 'Poznan';
            $oCons->consign_prep_data->srv_ident->street = 'Srebrna 15';
            $oCons->consign_prep_data->srv_ident->date_birth = '2014-01-01';
            $oCons->consign_prep_data->srv_ident->identity = 'YG654G8HR';
            $oCons->consign_prep_data->srv_ident->ident_doctype = 1;
            $oCons->consign_prep_data->srv_ident->nation = 'polskie';
            $oCons->consign_prep_data->srv_ident->spages = 1;
            $oCons->consign_prep_data->srv_ident->ssign = 2;
            $oCons->consign_prep_data->srv_ident->sdealsend = 3;
            $oCons->consign_prep_data->srv_ident->sdealrec = 4;
            */

            /*
            $oCons->consign_prep_data->srv_bool->exc = 1;
            $oCons->consign_prep_data->srv_ppe = new stdClass();
            $oCons->consign_prep_data->srv_ppe->sname1 = 'SName1';
            $oCons->consign_prep_data->srv_ppe->sname2 = 'SName2';
            $oCons->consign_prep_data->srv_ppe->sname3 = 'SName3';
            $oCons->consign_prep_data->srv_ppe->scountry = 'PL';
            $oCons->consign_prep_data->srv_ppe->szipcode = '00-950';
            $oCons->consign_prep_data->srv_ppe->scity = 'Warszawa';
            $oCons->consign_prep_data->srv_ppe->sstreet = 'Zlota 4';
            $oCons->consign_prep_data->srv_ppe->sphone = 'SPhone';
            $oCons->consign_prep_data->srv_ppe->scontact = 'SContact';

            $oCons->consign_prep_data->srv_ppe->rname1 = 'RName1';
            $oCons->consign_prep_data->srv_ppe->rname2 = 'RName2';
            $oCons->consign_prep_data->srv_ppe->rname3 = 'RName3';
            $oCons->consign_prep_data->srv_ppe->rcountry = 'PL';
            $oCons->consign_prep_data->srv_ppe->rzipcode = '64-200';
            $oCons->consign_prep_data->srv_ppe->rcity = 'Wolsztyn';
            $oCons->consign_prep_data->srv_ppe->rstreet = 'Parowozowa 12';
            $oCons->consign_prep_data->srv_ppe->rphone = 'RPhone';
            $oCons->consign_prep_data->srv_ppe->rcontact = 'RContact';
            */
            $oCons->consign_prep_data->parcels = new \stdClass();
            $oCons->consign_prep_data->weight = $this->recivesData['TW'];
            $oCons->consign_prep_data->quantity = 1; // overwrited by ParcelsArray

            $maxWeightNetto = $this->getMaxParcelWeights($oCons, $hClient);
            $maxWeightBrutto = $maxWeightNetto + 3;
            if ($this->recivesData['TW'] >= $maxWeightBrutto && $maxWeightNetto != 0) {

                $oParcel = new \stdClass();
                $oParcel->reference = $this->recivesData['ID'] . ' P.1';
                $oParcel->weight = $maxWeightNetto;
                $oCons->consign_prep_data->parcels->items[] = $oParcel;

                $oParcel = new \stdClass();
                $oParcel->reference = $this->recivesData['ID'] . ' P.2';
                $oParcel->weight = $this->recivesData['TW'] - $maxWeightNetto;
                $oCons->consign_prep_data->parcels->items[] = $oParcel;

            } else {


                $oParcel = new \stdClass();
                $oParcel->reference = $this->recivesData['ID'] . ' P.1';
                $oParcel->weight = $this->recivesData['TW'];
                $oCons->consign_prep_data->parcels->items[] = $oParcel;
            }


            $oClient = $hClient->adePreparingBox_Insert($oCons);
            $this->delivery_id = $oClient->return->id;

            $oClient = $hClient->adeLogout($oCons);

            return true;

        } catch (SoapFault $fault) {

            //println('Code: ' . $fault->faultcode . ', FaultString: ' . $fault->faultstring);
            return false;
            /* for debug:
            echo '<h2>Request</h2>';
            echo '<pre>' . $hClient->__getLastRequestHeaders() . '</pre>';
            echo '<pre>' . htmlspecialchars( $hClient->__getLastRequest(), ENT_QUOTES ) . '</pre>';
            echo '<h2>Response</h2>';
            echo '<pre>' . $hClient->__getLastResponseHeaders() . '</pre>';
            echo '<pre>' . htmlspecialchars( $hClient->__getLastResponse(), ENT_QUOTES ) . '</pre>';
            */

        }

    }


    private function getMaxParcelWeights($cSess, $hClient)
    {

        try {

            $oClient = $hClient->adeServices_GetMaxParcelWeights($cSess);

            // stdClass Object ( [return] => stdClass Object ( [weight_max_national] => 31.5 [weight_max_international] => 50 ) )
            return $oClient->return->weight_max_national;


        } catch (SoapFault $fault) {

            println('Code: ' . $fault->faultcode . ', FaultString: ' . $fault->faultstring);
            /* for debug:
            echo '<h2>Request</h2>';
            echo '<pre>' . $hClient->__getLastRequestHeaders() . '</pre>';
            echo '<pre>' . htmlspecialchars( $hClient->__getLastRequest(), ENT_QUOTES ) . '</pre>';
            echo '<h2>Response</h2>';
            echo '<pre>' . $hClient->__getLastResponseHeaders() . '</pre>';
            echo '<pre>' . htmlspecialchars( $hClient->__getLastResponse(), ENT_QUOTES ) . '</pre>';
            */

        }
    }

    public function end()
    {
        $this->request->session()->destroy();
    }

    /**
     * View method
     *
     * @param string|null $id Gl id.
     * @return \Cake\Network\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $gl = $this->Gls->get($id, [
            'contain' => []
        ]);

        $this->set('gl', $gl);
        $this->set('_serialize', ['gl']);
    }

    /**
     * Add method
     *
     * @return \Cake\Network\Response|void Redirects on successful add, renders view otherwise.
     */
    public function saveConfig()
    {
        $store = $this->Stores->newEntity();
        if ($this->request->is('post')) {
            $store = $this->Stores->patchEntity($store, $this->prepareData());
            if ($this->Stores->save($store)) {
                $this->Flash->success(__('The GLS Settings has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The GLS settings could not be saved. Please, try again.'));
            }
        }
        $this->set('store', $store);
    }


    /**
     * Delete method
     *
     * @param string|null $id Gl id.
     * @return \Cake\Network\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $gl = $this->Gls->get($id);
        if ($this->Gls->delete($gl)) {
            $this->Flash->success(__('The gl has been deleted.'));
        } else {
            $this->Flash->error(__('The gl could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    private function prepareData()
    {
        $pData = [];
        $pData['curier_id'] = self::GLS_ID;
        $pData['code'] = $this->recivesData['CD'];
        $pData['cod_store_code'] = $this->request->data['cod_store_code'];
        $pData['api_username'] = $this->request->data['api_username'];
        $pData['api_password'] = $this->request->data['api_password'];
        $pData['api_url'] = $this->request->data['api_url'];
        $pData['api_parameters'] = $this->request->data['api_parameters'];
        $pData['created'] = date('Y-m-d H:i:s');

        return $pData;
    }

    private function prepareDeliveryData()
    {
        $pData = [];
        $pData['store_id'] = $this->store->id;
        $pData['code'] = $this->recivesData['CD'];
        $pData['delivery_id'] = $this->delivery_id;
        $pData['delivery'] = $this->request->session()->read('Recives.CryptData');
        $pData['status'] = 1;
        $pData['created'] = date('Y-m-d H:i:s');

        return $pData;
    }

    private function setModels()
    {
        $this->loadModel('Stores');
        $this->loadModel('Deliveries');
    }
}
