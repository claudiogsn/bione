<?php
/**
 * OsPagamento Active Record
 * @author  <your-name-here>
 */
class OsPagamento extends TRecord
{
    const TABLENAME = 'os_pagamento';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('evento_id');
        parent::addAttribute('cliente_id');
        parent::addAttribute('num_controle');
        parent::addAttribute('forma_pg');
        parent::addAttribute('valor_pg');
        parent::addAttribute('data_prog');
        parent::addAttribute('data_pg');
        parent::addAttribute('status');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }


}
