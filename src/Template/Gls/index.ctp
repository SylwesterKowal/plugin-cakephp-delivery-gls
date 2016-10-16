<?php
/**
 * Created by 21w.pl
 * User: Sylwester Kowal
 * Date: 2016-10-12
 * Time: 09:37
 */

if (!$setConfig) {
    echo $this->Form->create($store);
    echo $this->Form->input('cod_store_code', ['options' => $gls['ME']]);
    echo $this->Form->input('api_username');
    echo $this->Form->input('api_password');
    echo $this->Form->input('api_url');
    echo $this->Form->input('api_parameters');

    echo $this->Form->button(__('Submit'));
    echo $this->Form->end();
}


?>
<br/>
<hr>
<?php

if(isset($errors) && count($errors) > 0){
    foreach ($errors as $ek => $err){
        echo $err.'<br/>';
    }
}