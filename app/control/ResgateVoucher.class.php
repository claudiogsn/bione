<?php
class ResgateVoucher extends TPage
{
    private $form;
    public function __construct($param)
    {
        parent::__construct();

        $username = TSession::getValue('username');


        if($_SERVER['SERVER_NAME'] == "localhost"){
            $link = "http://".$_SERVER['SERVER_NAME']."/crm-deck/external/resgate.php?username={$username}";
        }else{
            $link = "https://".$_SERVER['SERVER_NAME']."/external/resgate.php?username={$username}";
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
        $id = $param['key'];
    }
}