<?php
/**
 * Material Active Record
 * @author  <your-name-here>
 */
class Material extends TRecord
{
    const TABLENAME = 'material';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('fabricante');
        parent::addAttribute('modelo');
        parent::addAttribute('categoria');
        parent::addAttribute('saldo_estoque');
        parent::addAttribute('custo_material');
        parent::addAttribute('valor_locacao');
        parent::addAttribute('status');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
        parent::addAttribute('patrimonio');
        parent::addAttribute('sublocado');
        parent::addAttribute('custo_locacao');
        parent::addAttribute('fornecedor_id');
    }


}
