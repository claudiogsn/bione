<?php
/**
 * Orders Active Record
 * @author  <your-name-here>
 */
class Orders extends TRecord
{
    const TABLENAME = 'orders';
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
        parent::addAttribute('data_montagem');
        parent::addAttribute('data_recolhimento');
        parent::addAttribute('status');
        parent::addAttribute('contato_montagem');
        parent::addAttribute('local_montagem');
        parent::addAttribute('endereco');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }


}
