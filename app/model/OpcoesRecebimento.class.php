<?php
/**
 * OpcoesRecebimento Active Record
 * @author  <your-name-here>
 */
class OpcoesRecebimento extends TRecord
{
    const TABLENAME = 'opcoes_recebimento';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('codigo');
        parent::addAttribute('tipo');
        parent::addAttribute('nome');
        parent::addAttribute('descricao');
        parent::addAttribute('prazo');
        parent::addAttribute('taxa');
        parent::addAttribute('status');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }


}
