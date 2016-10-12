<?php
namespace Gls\Controller;

use Gls\Controller\AppController;

/**
 * Gls Controller
 *
 * @property \Gls\Model\Table\GlsTable $Gls
 */
class GlsController extends AppController
{

    /**
     * Index method
     *
     * @return \Cake\Network\Response|null
     */
    public function index()
    {
        $gls = $this->request->session->read('Recives.data');

        $this->set(compact('gls'));
        $this->set('_serialize', ['gls']);
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
    public function add()
    {
        $gl = $this->Gls->newEntity();
        if ($this->request->is('post')) {
            $gl = $this->Gls->patchEntity($gl, $this->request->data);
            if ($this->Gls->save($gl)) {
                $this->Flash->success(__('The gl has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The gl could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('gl'));
        $this->set('_serialize', ['gl']);
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
}
