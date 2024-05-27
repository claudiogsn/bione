<?php
/**
 * MaterialForm Form
 * @author  <your name here>
 */
class MaterialForm extends TPage
{
    protected $form; // form
    
    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_Material');
        $this->form->setFormTitle('Material');
        

        // create the form fields
        $id = new THidden('id');
        $nome = new TEntry('nome');
        $modelo = new TEntry('modelo');
        $fabricante_id = new TDBUniqueSearch('fabricante_id', 'communication', 'Fabricante', 'id', 'nome');
        $categoria_id = new TDBUniqueSearch('categoria_id', 'communication', 'Categoria', 'id', 'nome');
        $saldo_estoque = new TEntry('saldo_estoque');
        $custo_material = new TEntry('custo_material');
        $valor_locacao = new TEntry('valor_locacao');
        $patrimonio = new TEntry('patrimonio');
        $sublocado = new TCombo('sublocado');
        $custo_locacao = new TEntry('custo_locacao');
        $fornecedor_id = new TDBUniqueSearch('fornecedor_id', 'communication', 'FinanceiroFornecedor', 'id', 'codigo');
        
        
        $options = ['Sim' => 'Sim', 'Nao' => 'Nao']; 
        $sublocado->addItems($options);
        $sublocado->enableSearch();
        $custo_material->setNumericMask(2, ',', '.', true);
        $valor_locacao->setNumericMask(2, ',', '.', true);
        $custo_locacao->setNumericMask(2, ',', '.', true);


        // add the fields
        $this->form->addFields( [ new TLabel('') ], [ $id ] );
        $this->form->addFields( [ new TLabel('Nome : ') ], [ $nome ] );
        $this->form->addFields( [ new TLabel('Modelo : ') ], [ $modelo ] );
        $this->form->addFields( [ new TLabel('Fabricante : ') ], [ $fabricante_id ] );
        $this->form->addFields( [ new TLabel('Categoria : ') ], [ $categoria_id ] );
        $this->form->addFields( [ new TLabel('Saldo Estoque : ') ], [ $saldo_estoque ] );
        $this->form->addFields( [ new TLabel('Custo Material : ') ], [ $custo_material ] );
        $this->form->addFields( [ new TLabel('Valor Locacao : ') ], [ $valor_locacao ] );
        $this->form->addFields( [ new TLabel('Patrimonio : ') ], [ $patrimonio ] );
        $this->form->addFields( [ new TLabel('Sublocado : ') ], [ $sublocado ] );
        $this->form->addFields( [ new TLabel('Custo Locacao : ') ], [ $custo_locacao ] );
        $this->form->addFields( [ new TLabel('Fornecedor : ') ], [ $fornecedor_id ] );

        $nome->addValidation('Nome', new TRequiredValidator);
        $modelo->addValidation('Modelo', new TRequiredValidator);
        $fabricante_id->addValidation('Fabricante', new TRequiredValidator);
        $categoria_id->addValidation('Categoria', new TRequiredValidator);
        

        

        // set sizes
        $id->setSize('50%');
        $nome->setSize('50%');
        $modelo->setSize('50%');
        $fabricante_id->setSize('50%');
        $categoria_id->setSize('50%');
        $saldo_estoque->setSize('50%');
        $custo_material->setSize('50%');
        $valor_locacao->setSize('50%');
        $patrimonio->setSize('50%');
        $sublocado->setSize('50%');
        $custo_locacao->setSize('50%');
        $fornecedor_id->setSize('50%');



        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        
         
        // create the form actions
        $btn = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave']), 'fa:save');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('New'),  new TAction([$this, 'onEdit']), 'fa:eraser red');
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        
        parent::add($container);
        
        
    }

    /**
     * Save form data
     * @param $param Request
     */
    public function onSave( $param )
    {
        try
        {
            TTransaction::open('communication'); // open a transaction
            
            
            // Enable Debug logger for SQL operations inside the transaction
            TTransaction::setLogger(new TLoggerSTD); // standard output
            TTransaction::setLogger(new TLoggerTXT('log.txt')); // file
            
            
            $this->form->validate(); // validate form data
            $data = $this->form->getData(); // get form data as array
            
            $object = new Material;  // create an empty object
            $object->fromArray( (array) $data); // load the object with data
            $object->store(); // save the object
            
            // get the generated id
            $data->id = $object->id;
            
            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction
            
            new TMessage('info', AdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * Clear form data
     * @param $param Request
     */
    public function onClear( $param )
    {
        $this->form->clear(TRUE);
    }
    
    /**
     * Load object to form data
     * @param $param Request
     */
    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open('communication'); // open a transaction
                $object = new Material($key); // instantiates the Active Record
                $this->form->setData($object); // fill the form
                TTransaction::close(); // close the transaction
            }
            else
            {
                $this->form->clear(TRUE);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
}
