<?php
class PatrimonioList extends TPage
{
    private $form;
    public function __construct($param)
    {
        parent::__construct();

        $username = TSession::getValue('username');
        $token = TSession::getValue('sessionid');


        if ($_SERVER['SERVER_NAME'] == "localhost") {
            $link = "http://localhost/bione/external/listPatrimonios.php?username={$username}&token={$token}";
        } else {
            $link = "https://bionetecnologia.com.br/crm/external/listPatrimonios.php?username={$username}&token={$token}";
        }

        $iframe = new TElement('iframe');
        $iframe->id = "iframe_external";
        $iframe->src = $link;
        $iframe->frameborder = "0";
        $iframe->scrolling = "yes";
        $iframe->width = "100%";
        $iframe->height = "800px";

        parent::add($iframe);
    }
    function onFeed($param){
        // $id = $param['key'];
    }
    function onEdit($param){
        // $id = $param['key'];
    }
}