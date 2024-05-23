<?php
/**
 * Campanha Active Record
 * @author  <your-name-here>
 */
class Campanha extends TRecord
{
    const TABLENAME = 'campanha';
    const PRIMARYKEY= 'campanha_id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('descricao');
        parent::addAttribute('data_inicio');
        parent::addAttribute('data_fim');
        parent::addAttribute('ativa');
    }


}
