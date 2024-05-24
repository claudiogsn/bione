<?php
/**
 * Servico Active Record
 * @author  <your-name-here>
 */
class Servico extends TRecord
{
    const TABLENAME = 'servico';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('descricao');
        parent::addAttribute('valor_servico');
        parent::addAttribute('custo_servico');
        parent::addAttribute('terceirizado');
        parent::addAttribute('status');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
        parent::addAttribute('fornecedor_id');
    }


}
