<?php
/**
 * Voucher Active Record
 * @author  <your-name-here>
 */
class Voucher extends TRecord
{
    const TABLENAME = 'voucher';
    const PRIMARYKEY= 'voucher_id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    private $campanha;
    private $cliente;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('cpf_cliente');
        parent::addAttribute('campanha_id');
        parent::addAttribute('codigo');
        parent::addAttribute('data_criacao');
        parent::addAttribute('data_uso');
        parent::addAttribute('usuario_uso_id');
        parent::addAttribute('ip_uso');
        parent::addAttribute('ip_criacao');
    }

    
    /**
     * Method set_campanha
     * Sample of usage: $voucher->campanha = $object;
     * @param $object Instance of Campanha
     */
    public function set_campanha(Campanha $object)
    {
        $this->campanha = $object;
        $this->campanha_id = $object->id;
    }
    
    /**
     * Method get_campanha
     * Sample of usage: $voucher->campanha->attribute;
     * @returns Campanha instance
     */
    public function get_campanha()
    {
        // loads the associated object
        if (empty($this->campanha))
            $this->campanha = new Campanha($this->campanha_id);
    
        // returns the associated object
        return $this->campanha;
    }
    
    
    /**
     * Method set_cliente
     * Sample of usage: $voucher->cliente = $object;
     * @param $object Instance of Cliente
     */
    public function set_cliente(Cliente $object)
    {
        $this->cliente = $object;
        $this->cliente_id = $object->id;
    }
    
    /**
     * Method get_cliente
     * Sample of usage: $voucher->cliente->attribute;
     * @returns Cliente instance
     */
    public function get_cliente()
    {
        // loads the associated object
        if (empty($this->cliente))
            $this->cliente = new Cliente($this->cpf_cliente);
    
        // returns the associated object
        return $this->cliente;
    }
    


}
