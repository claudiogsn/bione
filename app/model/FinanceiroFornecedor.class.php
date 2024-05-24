<?php
/**
 * FinanceiroFornecedor Active Record
 * @author  <your-name-here>
 */
class FinanceiroFornecedor extends TRecord
{
    const TABLENAME = 'financeiro_fornecedor';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('codigo');
        parent::addAttribute('nome');
        parent::addAttribute('razao');
        parent::addAttribute('endereco');
        parent::addAttribute('bairro');
        parent::addAttribute('cidade');
        parent::addAttribute('estado');
        parent::addAttribute('cep');
        parent::addAttribute('cpf_cnpj');
        parent::addAttribute('insc_est');
        parent::addAttribute('insc_mun');
        parent::addAttribute('email');
        parent::addAttribute('fone');
        parent::addAttribute('status');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }


}
