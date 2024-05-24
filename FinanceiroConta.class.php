<?php
/**
 * FinanceiroConta Active Record
 * @author  <your-name-here>
 */
class FinanceiroConta extends TRecord
{
    const TABLENAME = 'financeiro_conta';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('doc');
        parent::addAttribute('tipo');
        parent::addAttribute('valor');
        parent::addAttribute('entidade');
        parent::addAttribute('forma_pg');
        parent::addAttribute('opcao_receb');
        parent::addAttribute('cpf_cnpj');
        parent::addAttribute('banco');
        parent::addAttribute('emissao');
        parent::addAttribute('vencimento');
        parent::addAttribute('inc_ope');
        parent::addAttribute('data_baixa');
        parent::addAttribute('status');
        parent::addAttribute('created_at');
        parent::addAttribute('updated_at');
    }


}
