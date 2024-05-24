<?php
/**
 * Evento Active Record
 * @author  <your-name-here>
 */
class Evento extends TRecord
{
    const TABLENAME = 'evento';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('cliente_id');
        parent::addAttribute('capacidade');
        parent::addAttribute('data_inicio');
        parent::addAttribute('data_fim');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
        parent::addAttribute('local');
        parent::addAttribute('cep');
        parent::addAttribute('endereco');
        parent::addAttribute('bairro');
        parent::addAttribute('cidade');
        parent::addAttribute('estado');
    }


}
