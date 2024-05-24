<?php
/**
 * OsItem Active Record
 * @author  <your-name-here>
 */
class OsItem extends TRecord
{
    const TABLENAME = 'os_item';
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
        parent::addAttribute('material_id');
        parent::addAttribute('valor');
        parent::addAttribute('custo');
        parent::addAttribute('dias_uso');
        parent::addAttribute('data_inicial');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
        parent::addAttribute('status');
    }


}
